<?php

namespace App\Http\Controllers\v1;

use App\Exceptions\GeneralException;
use App\Project;
use App\Repository\Helper\Response;
use App\Repository\Services\CoreService;
use App\Repository\Services\LoraService;
use App\Repository\Services\ProjectService;
use App\Repository\Services\ThingService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel;

class ProjectController extends Controller
{
    protected $projectService;
    protected $coreService;
    protected $loraService;
    protected $thingService;

    /**
     * ProjectController constructor.
     * @param ProjectService $projectService
     * @param CoreService $coreService
     * @param LoraService $loraService
     * @param ThingService $thingService
     */
    public function __construct(ProjectService $projectService,
                                CoreService $coreService,
                                LoraService $loraService,
                                ThingService $thingService)
    {
        $this->projectService = $projectService;
        $this->coreService = $coreService;
        $this->thingService = $thingService;
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
        $user->projects()->save($project);
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
        $project->delete();
        return Response::body($response);
    }

    /**
     * @return array
     */
    public function all()
    {
        $projects = collect(Auth::user()->projects()->get());
        return Response::body(compact('projects'));
    }

    /**
     * @param Project $project
     * @param Request $request
     * @return array
     */
    public function things(Project $project, Request $request)
    {
        $things = $project->things();
        try {
            $data = ['sorted' => json_decode($request->get('sorted'), true), 'filtered' => json_decode($request->get('filtered'), true)];
        } catch (\Error $e) {
            $data = ['sorted' => [], 'filtered' => []];
        }
        if(is_array($data['filtered'])){
        foreach ($data['filtered'] as $item)
            $things->where($item['id'], 'like', '%' . $item['value'] . '%');
        }else{
            return Response::body(['error message' => 'array is empty']);
        }
        if (count($data['sorted']))
            $things->orderBy($data['sorted'][0]['id'], $data['sorted'][0]['desc'] ? 'DESC' : 'ASC');

        $pages = ceil($things->count() / (intval($request->get('limit')) ?: 10));
        $things = $things->skip(intval($request->get('offset')))->take(intval($request->get('limit')) ?: 10)->get();

        return Response::body(['things' => $things, 'pages' => $pages]);
    }

    /**
     * @param Project $project
     * @param Excel $excel
     * @return ThingService|\Illuminate\Database\Eloquent\Model
     */
    public function exportThings(Project $project, Excel $excel)
    {
        $things = $project->things()->with(['profile', 'project'])->get();
        return $this->thingService->toExcel($things);
    }


    /**
     * @param Project $project
     * @return array
     */
    public function get(Project $project, Request $request)
    {
        $project->load(['things', 'scenarios']);
        $project['scenarios']->forget('code');
        if ($request->get('compress'))
            $project->things->each->setAppends([]);
        $project['scenarios'] = $project['scenarios']->map(function ($item, $key) {
            unset($item['code']);
            return $item;
        });
        $result = $project->toArray();
        $result['aliases'] = $project['aliases'];

        return Response::body(['project' => $result]);
    }

    /**
     * @param Project $project
     * @param Request $request
     * @return array
     * @throws GeneralException
     */
    public function activate(Project $project, Request $request)
    {
        $active = $request->get('active') ? true : false;
        $this->coreService->activateProject($project, $active);
        $project->things()->update(['active' => $active]);
        $project['active'] = $active;
        $project->save();

        return Response::body(['success' => true]);
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
        if ($request->get('type') == 'lora')
            $logs = $this->coreService->loraLogs($project['_id'], $limit);
        else
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
