<?php
/**
 * Created by PhpStorm.
 * User: sajjad
 * Date: 12/20/17
 * Time: 4:50 PM
 */

namespace App\Repository\Services;


use App\Exceptions\GeneralException;
use App\Exceptions\LoraException;
use App\Project;
use App\Thing;
use App\ThingProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ThingService
{
    protected $loraService;
    protected $coreService;
    protected $projectService;

    public function __construct(LoraService $loraService,
                                CoreService $coreService,
                                ProjectService $projectService)
    {
        $this->loraService = $loraService;
        $this->coreService = $coreService;
        $this->projectService = $projectService;
    }

    /**
     * @param $request
     * @return void
     * @throws GeneralException
     */
    public function validateCreateThing($request)
    {
        $messages = [
            'name.required' => 'لطفا نام شی را وارد کنید',
            'type.required' => 'نوع اینترفیس شی را وارد کنید',
            'lat.numeric' => 'لطفا محل سنسور را درست وارد کنید',
            'lat.required' => 'لطفا محل سنسور را درست کنید',
            'long.numeric' => 'لطفا محل سنسور را درست وارد کنید',
            'long.required' => 'لطفا محل سنسور را وارد کنید',
            'period.required' => 'لطفا بازه ارسال داده سنسور را وارد کنید',
            'period.numeric' => 'لطفا بازه ارسال داده سنسور را درست وارد کنید',
            'devEUI.min' => 'لطفا devEUI سنسور را درست وارد کنید',
            'devEUI.max' => 'لطفا devEUI سنسور را درست وارد کنید',
            'devEUI.required' => 'لطفا devEUI سنسور را وارد کنید',
            'thing_profile_slug.required' => 'لطفا شناسه پروفایل شی را وارد کنید',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'devEUI' => 'required|min:16|max:16',
            'type' => 'required|',
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
            'period' => 'required|numeric',
            'thing_profile_slug' => 'required',
        ], $messages);

        if ($validator->fails())
            throw new  GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);
    }


    /**
     * @param Request $request
     * @return void
     * @throws GeneralException
     */
    public function validateExcel(Request $request)
    {
        $messages = [
            'things.required' => 'لطفا فایل را انتخاب کنید',
            'things.mimes' => 'نوع فایل را درست وارد کنید',
        ];

        $validator = Validator::make($request->all(), [
            'things' => 'required|file',
        ], $messages);

        if ($validator->fails())
            throw new  GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);
        if ($request->file('things')->clientExtension() != 'csv')
            throw new  GeneralException('لطفا فایل با فرمت csv انتخاب کنید', GeneralException::VALIDATION_ERROR);
    }

    /**
     * @param $request
     * @param Project $project
     * @param ThingProfile $thingProfile
     * @return void
     * @throws GeneralException
     * @throws LoraException
     */
    public function insertThing($request, Project $project = null, ThingProfile $thingProfile = null)
    {
        if (!$thingProfile)
            throw new GeneralException('پروفایل شی یافت نشد', 700);
        if (!$project)
            throw new GeneralException('پروژه یافت نشد', 700);

        $device = $this->loraService->postDevice(
            $request,
            $project['application_id'],
            $thingProfile['device_profile_id']
        );
        $this->validateInsert($request);
        $thing = Thing::create([
            'name' => $request->get('name'),
            'description' => $request->get('description') ?: '',
            'interface' => $device->toArray(),
            'period' => $request->get('period'),
            'dev_eui' => $request->get('devEUI'),
            'type' => $thingProfile['data']['deviceProfile']['supportsJoin'] ? 'OTAA' : 'ABP',
            'loc' => [
                'type' => 'Point',
                'coordinates' => [$request->get('lat'), $request->get('long')]
            ],
        ]);
        $thing->profile()->associate($thingProfile);
        $this->addToProject($project, $thing);
        return $thing;
    }


    public function activateABP($request, Thing $thing)
    {
        $validator = Validator::make($request->all(), [
            'devAddr' => 'required',
            'nwkSKey' => 'required',
            'appSKey' => 'required',
        ]);
        if ($validator->fails())
            throw new GeneralException('اطلاعات فعال سازی را کامل وارد کنید', 407);
        $data['devAddr'] = (string)$request->get('devAddr');
        $data['nwkSKey'] = (string)$request->get('nwkSKey');
        $data['appSKey'] = (string)$request->get('appSKey');
        $data['fCntUp'] = intval($request->get('fCntUp', 0));
        $data['fCntDown'] = intval($request->get('fCntDown', 0));
        $data['skipFCntCheck'] = $request->get('skipFCntCheck') === '1' ? true : false;
        $data['devEUI'] = $thing['interface']['devEUI'];
        $this->loraService->activateDevice($data);
        return $data;
    }

    public function activateOTAA($request, Thing $thing)
    {
        $key = $request->get('appKey');
        if (!$key)
            $key = $request->get('appSKey');
        $data = ['deviceKeys' => ['appKey' => $key]];
        $data['devEUI'] = $thing['interface']['devEUI'];
        $this->loraService->SendKeys($data);
        return $data['deviceKeys'];
    }

    /**
     * @param $data
     * @return void
     * @throws GeneralException
     */
    public function validateInsert(Collection $data)
    {
        $messages = [
            'name.required' => 'لطفا نام شی را وارد کنید',
            'description.filled' => 'لطفا توضیحات را درست وارد کنید',
            'period.*' => 'لطفا بازه ارسال را درست وارد کنید',
            'lat.*' => 'لطفا محل سنسور را درست وارد کنید',
            'long.*' => 'لطفا محل سنسور را درست وارد کنید',
        ];

        $validator = Validator::make($data->all(), [
            'name' => 'required|string|max:255',
            'description' => 'max:255',
            'period' => 'numeric',
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
            'devEUI' => 'required|size:16',
        ], $messages);

        if ($validator->fails())
            throw new  GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);
    }

    /**
     * @param Collection $request
     * @param Thing $thing
     * @return $this|\Illuminate\Database\Eloquent\Model
     * @throws LoraException
     */
    public function updateThing(Collection $request, Thing $thing)
    {
        $lora_data = [];
        if ($request->get('name')) {
            $thing->name = $request->get('name');
            $lora_data['name'] = $request->get('name');
        }

        if ($request->get('description')) {
            $thing->description = $request->get('description');
            $lora_data['description'] = $request->get('description');
        }

        if ($request->get('period'))
            $thing->period = $request->get('period');

        if ($request->get('thing_profile_slug')) {
            $profile = ThingProfile::where('thing_profile_slug', (int)$request->get('thing_profile_slug'))->first();
            if ($profile && Auth::user()->can('view', $profile)) {
                $lora_data['deviceProfileID'] = $profile['device_profile_id'];
                $thing['profile_id'] = $profile['_id'];
            } else
                $lora_data['deviceProfileID'] = $thing['profile']['device_profile_id'];
        }


        if ($request->get('lat') && $request->get('long'))
            $thing->loc = [
                'type' => 'Point',
                'coordinates' => [$request->get('lat'), $request->get('long')]
            ];
        $this->loraService->updateDevice($lora_data, $thing['dev_eui']);
        $thing->save();

        return $thing;
    }


    /**
     * @param Project $project
     * @param Thing $thing
     * @throws GeneralException
     */
    public function addToProject(Project $project, Thing $thing)
    {
        $thing->project()->associate($project);
        $thing->save();
        $this->coreService->postThing($project, $thing);
    }

}