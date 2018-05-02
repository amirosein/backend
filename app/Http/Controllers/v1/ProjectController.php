<?php

namespace App\Http\Controllers\v1;

use App\Exceptions\GeneralException;
use App\Project;
use App\Repository\Helper\Response;
use App\Repository\Services\CoreService;
use App\Repository\Services\LoraService;
use App\Repository\Services\PermissionService;
use App\Repository\Services\ProjectService;
use App\Permission;
use App\Thing;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Excel;

class ProjectController extends Controller
{
    protected $projectService;
    protected $permissionService;
    protected $coreService;
    protected $loraService;

    /**
     * ProjectController constructor.
     * @param ProjectService $projectService
     * @param PermissionService $permissionService
     * @param CoreService $coreService
     * @param LoraService $loraService
     */
    public function __construct(ProjectService $projectService,
                                PermissionService $permissionService,
                                CoreService $coreService,
                                LoraService $loraService)
    {
        $this->projectService = $projectService;
        $this->permissionService = $permissionService;
        $this->coreService = $coreService;
        $this->loraService = $loraService;

        $this->middleware('can:view,project')->only(['get', 'things']);
        $this->middleware('can:update,project')->only(['update', 'aliases']);
        $this->middleware('can:delete,project')->only(['stop']);
        $this->middleware('can:create,App\Project')->only(['create']);
    }


    /**
     * @param Request $request
     * @return array
     * @throws GeneralException
     * @throws \App\Exceptions\LoraException
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $this->projectService->validateCreateProject($request);

        $project = $this->projectService->insertProject($request);
        $owner_permission = $this->permissionService->get('PROJECT-OWNER');
        $permission = Permission::create([
            'name' => $owner_permission['name'],
            'permission_id' => (string)$owner_permission['_id'],
            'item_type' => 'project',
        ]);
        $project->permissions()->save($permission);
        $user->permissions()->save($permission);

        return Response::body(compact('project'));
    }


    /**
     * @param Project $project
     * @return array
     * @throws GeneralException
     * @throws \Exception
     */
    public function stop(Project $project)
    {
        $things = $project->things()->get();
        if (count($things))
            throw new GeneralException('ابتدا اشیا این پروژه رو پاک کنید', 700);
        $response = $this->coreService->deleteProject($project->container['name']);
        $this->loraService->deleteApp($project['application_id']);
        $project->permissions()->delete();
        $project->delete();
        return Response::body($response);
    }

    /**
     * @return array
     */
    public function all()
    {
        $projects = collect(Auth::user()->projects()->get());
        $projects = $projects->map(function ($item) {
            return $item['project'];
        });
        return Response::body(compact('projects'));
    }

    /**
     * @param Project $project
     * @return array
     */
    public function things(Project $project)
    {
        $things = $project->things()->get();
        return Response::body(compact('things'));
    }

    /**
     * @param Project $project
     * @param Excel $excel
     * @return array
     * @throws \Maatwebsite\Excel\Exceptions\LaravelExcelException
     */
    public function exportThings(Project $project, Excel $excel)
    {
        $things = $project->things()->with('profile')->get();
        $res = [[
            'operation',
            'name',
            'type',
            'description',
            'lat',
            'long',
            'period',
            'devEUI',
            'thing_profile_slug',
            'appSKey',
            'nwkSKey',
            'devAddr'
        ]];
        $res = array_merge($res, $things->map(function ($item) {
            return [
                'add',
                $item['name'],
                'lora',
                $item['description'],
                $item['loc']['coordinates'][0],
                $item['loc']['coordinates'][1],
                $item['period'],
                $item['dev_eui'],
                $item['profile']['thing_profile_slug'],
                isset($item['keys']) ? ($item['type'] == 'ABP' ? $item['keys']['appSKey'] : $item['keys']['appKey']) : '',
                isset($item['keys']) && $item['type'] == 'ABP' ? $item['keys']['nwkSKey'] : '',
                isset($item['keys']) && $item['type'] == 'ABP' ? $item['keys']['devAddr'] : '',
            ];
        })->toArray());

        return response($excel->create('things.csv', function ($excel) use ($res) {
            $excel->sheet('Things', function ($sheet) use ($res) {
                $sheet->fromArray($res, null, 'A1', false, false);
            });
        })->string('csv'))
            ->header('Content-Disposition', 'attachment; filename="things.csv.csv"')
            ->header('Content-Type', 'application/csv; charset=UTF-8');
    }


    /**
     * @param Project $project
     * @return array
     */
    public function get(Project $project)
    {
        $project->load(['things', 'scenarios']);
        $project['scenarios']->forget('code');
        $project['scenarios'] = $project['scenarios']->map(function ($item, $key) {
            unset($item['code']);
            return $item;
        });
        $result = $project->toArray();
        $result['aliases'] = $project['aliases'];

        return Response::body(['project' => $result]);
    }


    /**
     * @param Request $request
     * @param Project $project
     * @return array
     * @throws GeneralException
     */
    public function update(Request $request, Project $project)
    {
        $this->projectService->validateUpdateProject($request);

        $project = $this->projectService->updateProject($request, $project);
        $project->load('things');

        return Response::body(compact('project'));
    }

    /**
     * @param Request $request
     * @param Project $project
     * @return array
     * @throws GeneralException
     */
    public function aliases(Request $request, Project $project)
    {
        $aliases = $request->get('aliases');
        $aliases = json_decode($aliases);
        $this->projectService->setAliases($project, $aliases);

        return Response::body(['success' => 'true']);
    }


    /**
     * @param Request $request
     * @param Project $project
     * @return array
     * @throws GeneralException
     */
    public function log(Request $request, Project $project)
    {
        $limit = intval($request->get('limit')) ?: 10;
        $logs = $this->coreService->projectLogs($project['_id'], $limit);
        return Response::body(['logs' => $logs]);
    }


    /**
     * @param Request $request
     * @param Project $project
     * @return array
     * @throws GeneralException
     */
    public function lint(Project $project, Request $request)
    {
        $code = $request->get('code');
        $result = $this->coreService->lint($project, $code);
        return Response::body(['result' => $result]);
    }

}
