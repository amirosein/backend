<?php

namespace App\Http\Controllers\v1;

use App\Exceptions\GeneralException;
use App\Exceptions\LoraException;
use App\Permission;
use App\Project;
use App\Repository\Helper\Response;
use App\Repository\Services\CoreService;
use App\Repository\Services\LoraService;
use App\Repository\Services\PermissionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exceptions\ThingException;
use App\Repository\Services\ThingService;
use App\Thing;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ThingController extends Controller
{
    protected $thingService;
    protected $permissionService;
    protected $coreService;
    protected $loraService;

    /**
     * ProjectController constructor.
     * @param ThingService $thingService
     * @param PermissionService $permissionService
     * @param CoreService $coreService
     * @param LoraService $loraService
     */
    public function __construct(ThingService $thingService,
                                PermissionService $permissionService,
                                CoreService $coreService,
                                LoraService $loraService)
    {
        $this->thingService = $thingService;
        $this->permissionService = $permissionService;
        $this->coreService = $coreService;
        $this->loraService = $loraService;
    }


    /**
     * @param Project $project
     * @param Request $request
     * @return array
     * @throws ThingException
     * @throws GeneralException
     * @throws LoraException
     */
    public function create(Project $project, Request $request)
    {
        $user = Auth::user();
        $this->thingService->validateCreateThing($request);
        $thing = $this->thingService->insertThing($request, $project);
        $user->things()->save($thing);
        $owner_permission = $this->permissionService->get('THING-OWNER');
        $permission = Permission::create([
            'name' => $owner_permission['name'],
            'permission_id' => (string)$owner_permission['_id'],
            'item_type' => 'thing'
        ]);
        $thing->permissions()->save($permission);
        $user->permissions()->save($permission);

        return Response::body(compact('thing'));
    }

    /**
     * @param Project $project
     * @return array
     */
    public function all(Project $project)
    {
        $things = $project->things()->get();
        return Response::body(compact('things'));
    }

    /**
     * @param Project $project
     * @param Thing $thing
     * @return array
     */
    public function get(Project $project, Thing $thing)
    {
        $user = Auth::user();
        if ($thing['user_id'] != $user->id)
            abort(404);
        if($project->things()->where('_id',$thing['_id'])->first())
        $thing->load(['user', 'project', 'codec']);

        return Response::body(compact('thing'));
    }

    /**
     * @param Project $project
     * @param Thing $thing
     * @param Request $request
     * @return array
     * @throws ThingException
     */
    public function update(Project $project, Thing $thing, Request $request)
    {
        $user = Auth::user();
        if ($thing['user_id'] != $user->id)
            abort(404);

        $this->thingService->validateUpdateThing($request);

        $thing = $this->thingService->updateThing($request, $thing);


        return Response::body(compact('thing'));
    }

    /**
     * @param Project $project
     * @param Thing $thing
     * @param Request $request
     * @return array
     * @throws \App\Exceptions\GeneralException
     */
    public function data(Project $project, Thing $thing, Request $request)
    {
        $user = Auth::user();
        if ($thing['user_id'] != $user->id)
            abort(404);

        $offset = $request->get('offset') ? Carbon::createFromTimestamp($request->get('offset')) : 0;
        $count = $request->get('count') ?: 100;
        $data = $this->coreService->getDeviceData($thing, $offset, $count);
        //$data = $thing->data()->where('timestamp', '>', $offset)->take((int)$count)->get();

        return Response::body(compact('data'));
    }

    /**
     * @param Project $project
     * @param Request $request
     * @return array
     * @throws ThingException
     */
    public function fromExcel(Project $project, Request $request)
    {
        $this->thingService->validateExcel($request);
        $file = $request->file('things');
        $res = [];
        Excel::load($file, function ($reader) use (&$res) {
            $user = Auth::user();
            $results = $reader->all();
            foreach ($results as $row) {
                $data = $this->prepareRow($row);
                try {
                    $this->thingService->validateCreateThing($data);
                    $thing = $this->thingService->insertThing($data);
                    $user->things()->save($thing);
                    $owner_permission = $this->permissionService->get('THING-OWNER');
                    $permission = Permission::create([
                        'name' => $owner_permission['name'],
                        'permission_id' => (string)$owner_permission['_id'],
                        'item_type' => 'thing'
                    ]);
                    $thing->permissions()->save($permission);
                    $user->permissions()->save($permission);
                    $res[$data['devEUI']] = $thing;
                } catch (\Exception $e) {
                    if (isset($data['devEUI']))
                        $res[$data['devEUI']] = 'Error';
                }
            }

        });

        return Response::body(compact('res'));
    }

    /**
     * @param Project $project
     * @param Thing $thing
     * @return array
     * @throws \App\Exceptions\LoraException
     * @throws \Exception
     */
    public function delete(Project $project, Thing $thing)
    {
        $this->loraService->deleteDevice($thing['interface']['devEUI']);
        $thing->permissions()->delete();
        $thing->delete();
        return Response::body(['success' => 'true']);
    }

    private function prepareRow($row)
    {
        $row = $row->toArray();
        $row['type'] = 'lora';
        $row['factoryPresetFreqs'] = isset($row['factoryPresetFreqs']) ? [$row['factoryPresetFreqs']] : [];
        return collect($row);

    }


}
