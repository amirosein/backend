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
use App\ThingProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\Services\ThingService;
use App\Thing;
use Illuminate\Support\Collection;
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

        $this->middleware('can:create,App\Thing')->only(['multiThingData']);
        $this->middleware('can:create,App\Thing')->only(['create']);
        $this->middleware('can:delete,thing')->only(['delete']);
        $this->middleware('can:update,thing')->only(['activate', 'update']);
        $this->middleware('can:view,thing')->only(['get', 'data']);
    }


    /**
     * @param Request $request
     * @return array
     */
    public function create(Request $request)
    {
        $project = Project::where('_id', $request->get('project_id'))->first();
        $thing = $this->createThing(collect($request->all()), $project);
        return Response::body(compact('thing'));
    }

    /**
     * @return array
     */
    public function all()
    {
        $things = Auth::user()->things()->get();
        $things = $things->map(function ($item) {
            return $item['thing'];
        });
        return Response::body(compact('things'));
    }

    /**
     * @param Thing $thing
     * @return array
     */
    public function get(Thing $thing)
    {
        $thing->load(['user', 'project', 'profile']);
        $codec = $thing['codec'];
        $thing = $thing->toArray();
        $thing['codec'] = $codec;

        return Response::body(compact('thing'));
    }

    /**
     * @param Thing $thing
     * @param Request $request
     * @return array
     * @throws LoraException
     */
    public function update(Thing $thing, Request $request)
    {
        $thing = $this->thingService->updateThing(collect($request->all()), $thing);
        $thing->load(['profile', 'project', 'user']);
        return Response::body(compact('thing'));
    }

    /**
     * @param Request $request
     * @return array
     * @throws GeneralException
     */
    public function sampleData(Request $request)
    {
        return $this->data($request, true);
    }

    /**
     * @param Request $request
     * @return array
     * @throws GeneralException
     */
    public function mainData(Request $request)
    {
        return $this->data($request, false);
    }

    /**
     * @param Request $request
     * @return array
     * @throws GeneralException
     */
    public function fromExcel(Request $request)
    {
        $this->thingService->validateExcel($request);
        $file = $request->file('things');
        $res = [];
        Excel::load($file, function ($reader) use (&$res, $request) {
            $project = Project::where('_id', $request->get('project_id'))->first();
            $results = $reader->all();
            $user = Auth::user();
            foreach ($results as $row) {
                $data = $this->prepareRow($row);
                try {
                    $data['devEUI'] = str_repeat("0", 16 - strlen($data['devEUI'])) . $data['devEUI'];
                    $thing = Thing::where('dev_eui', $data['devEUI'])->with('profile')->first();
                    if (!$user->can('update', $project)) {
                        $res[$data['devEUI']] = 'شما دسترسی این کار را ندارید';
                    } elseif ($row['operation'] == 'add') {
                        if (!$thing)
                            $thing = $this->createThing(collect($row), $project);
                        else
                            $thing = $this->thingService->updateThing(collect($row), $thing);
                        $res[$data['devEUI']] = $thing;
                        $this->sendKeys($thing, collect($row));
                    } elseif ($row['operation'] == 'delete') {
                        $deleted = $thing && $user->can('delete', $thing) ? $this->delete($thing) : 0;
                        if ($deleted)
                            $res[$data['devEUI']] = 'حذف شد';
                        else
                            $res[$data['devEUI']] = 'پیدا نشد';
                    } else
                        $res[$data['devEUI']] = 'خطای عملیات';
                } catch (\Exception $e) {
                    $res[$data['devEUI']] = $e->getMessage();
                }
            }

        });

        return Response::body(compact('res'));
    }

    /**
     * @param Thing $thing
     * @return array
     * @throws \App\Exceptions\LoraException
     * @throws \Exception
     */
    public function delete(Thing $thing)
    {
        $this->loraService->deleteDevice($thing['interface']['devEUI']);
        $this->coreService->deleteThing($thing['dev_eui']);
        $thing->permissions()->delete();
        $thing->delete();
        return Response::body(['success' => 'true']);
    }

    /**
     * @param Thing $thing
     * @param Request $request
     * @return array
     */
    public function activate(Thing $thing, Request $request)
    {
        $active = $request->get('active') ? true : false;
        $this->thingService->activate($thing, $active);
        return Response::body(['success' => 'true']);
    }

    /**
     * @param Thing $thing
     * @param Request $request
     * @return array
     */
    public function keys(Thing $thing, Request $request)
    {
        $keys = $this->sendKeys($thing, collect($request->all()));
        return Response::body(['keys' => $keys]);

    }


    /**
     * @param Request $request
     * @param bool $sample
     * @return array
     * @throws GeneralException
     */
    private function data(Request $request, $sample = false)
    {
        $project = Project::where('_id', $request->get('project_id'))->first();
        $aliases = isset($project['aliases']) ? $project['aliases'] : null;
        if ($request->get('window')) {
            $since = Carbon::now()->subMinute((int)$request->get('window'))->getTimestamp();
            $until = Carbon::now()->getTimestamp();
        } else {
            $since = $request->get('since') ?: 0;
            $until = $request->get('until') ?: Carbon::now()->getTimestamp();
        }
        $thing_ids = json_decode($request->get('thing_ids'), true)['ids'] ?: [];
        $thing_ids = $project->things()->whereIn('_id', $thing_ids)->get()->pluck('dev_eui');
        if ($sample) {
            $cluster_number = (int)($request->get('cn')) ?: 200;
            $data = $this->coreService->thingsSampleData($thing_ids, $since, $until, $cluster_number);
        } else {
            $limit = (int)($request->get('limit')) ?: 0;
            $offset = (int)($request->get('offset')) ?: 0;
            $data = $this->coreService->thingsMainData($thing_ids, $since, $until, $limit,$offset);
        }
        $data = $this->alias($data, $aliases);

        return Response::body(compact('data'));
    }

    private function prepareRow($row)
    {
        $row = $row->toArray();
        $row['type'] = 'lora';
        $row['factoryPresetFreqs'] = isset($row['factoryPresetFreqs']) ? [$row['factoryPresetFreqs']] : [];
        return collect($row);

    }

    private function alias($data, $aliases)
    {
        if ($aliases)
            foreach ($data as $d) {
                $res = [];
                foreach ($d->data as $key => $item) {
                    if (isset($aliases[$key]))
                        $res[$aliases[$key]] = $item;
                    else
                        $res[$key] = $item;
                }
                $d->data = $res;
            }
        return $data;
    }

    private function createThing(Collection $data, Project $project)
    {
        $user = Auth::user();
        $this->thingService->validateCreateThing($data);
        $thing_profile = ThingProfile::where('thing_profile_slug', (int)$data->get('thing_profile_slug'))->first();
        $thing = $this->thingService->insertThing($data, $project, $thing_profile);
        $user->things()->save($thing);
        $owner_permission = $this->permissionService->get('THING-OWNER');
        $permission = Permission::create([
            'name' => $owner_permission['name'],
            'permission_id' => (string)$owner_permission['_id'],
            'item_type' => 'thing'
        ]);
        $thing->permissions()->save($permission);
        $user->permissions()->save($permission);
        $this->thingService->addToProject($project, $thing);
        return $thing;
    }

    private function sendKeys(Thing $thing, Collection $data)
    {
        if ($thing['type'] == 'OTAA')
            $keys = $this->thingService->OTAAKeys($data, $thing);
        else
            $keys = $this->thingService->ABPKeys($data, $thing);
        $thing['keys'] = $keys;
        $thing->save();
        return $keys;
    }

}
