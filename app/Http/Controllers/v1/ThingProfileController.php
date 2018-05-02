<?php

namespace App\Http\Controllers\v1;

use App\Exceptions\GeneralException;
use App\Repository\Helper\Counter;
use App\Repository\Helper\Response;
use App\Repository\Services\LoraService;
use App\ThingProfile;
use App\Exceptions\LoraException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ThingProfileController extends Controller
{
    protected $loraService;

    /**
     * ProjectController constructor.
     * @param LoraService $loraService
     */
    public function __construct(LoraService $loraService)
    {
        $this->loraService = $loraService;

        $this->middleware('can:view,thing_profile')->only(['get']);
        $this->middleware('can:delete,thing_profile')->only(['delete']);
        $this->middleware('can:create,App\ThingProfile')->only(['create']);
    }


    /**
     * @param Request $request
     * @return array
     * @throws GeneralException
     * @throws LoraException
     */
    public function create(Request $request)
    {
        $id = Counter::thingProfile();
        $user = Auth::user();
        $data = $this->prepareDeviceProfileData(collect($request->all()));
        if (!$request->get('name'))
            throw new GeneralException('لطفا نام پروفایل شی را وارد کنید', GeneralException::VALIDATION_ERROR);
        $device_profile_id = $this->loraService->postDeviceProfile(collect($data))->deviceProfileID;
        $thing_profile = ThingProfile::create([
            'thing_profile_slug' => $id,
            'device_profile_id' => $device_profile_id,
            'data' => $data,
            'name' => $request->get('name'),
            'type' => $request->get('supportsJoin') === '1' ? 'OTAA' : 'ABP',
            'user_id' => $user['_id'],
        ]);
        return Response::body(compact('thing_profile'));
    }

    /**
     * @return array
     */
    public function all()
    {
        $thing_profiles = Auth::user()->thingProfiles()->get();
        return Response::body(compact('thing_profiles'));
    }


    /**
     * @param ThingProfile $thing_profile
     * @return array
     */
    public function get(ThingProfile $thing_profile)
    {
        $res = $thing_profile->toArray();
        $res['parameters'] = $thing_profile['data']['deviceProfile'];
        return Response::body(['thing_profile' => $res]);
    }

    /**
     * @param ThingProfile $thing_profile
     * @return array
     * @throws LoraException
     * @throws \Exception
     */
    public function delete(ThingProfile $thing_profile)
    {
        $this->loraService->deleteDeviceProfile($thing_profile['device_profile_id']);
        $thing_profile->delete();
        return Response::body(['success' => 'true']);
    }


    private function prepareDeviceProfileData(Collection $data)
    {
        try {
            $factoryPresetFreqs = collect($data->get('factoryPresetFreqs'))->map(function ($item, $key) {
                return (int)$item;
            });
            $res = [
                'deviceProfile' => [
                    'classBTimeout' => (int)$data->get('classBTimeout', 0),
                    'classCTimeout' => (int)$data->get('classCTimeout', 0),
                    'factoryPresetFreqs' => $factoryPresetFreqs,
                    'macVersion' => $data->get('macVersion', ''),
                    'maxDutyCycle' => (int)$data->get('maxDutyCycle', 0),
                    'maxEIRP' => (int)$data->get('maxEIRP', 0),
                    'pingSlotDR' => (int)$data->get('pingSlotDR', 0),
                    'pingSlotFreq' => (int)$data->get('pingSlotFreq', 0),
                    'pingSlotPeriod' => (int)$data->get('pingSlotPeriod', 0),
                    'regParamsRevision' => $data->get('regParamsRevision', ''),
                    'rfRegion' => $data->get('rfRegion', ''),
                    'rxDROffset1' => (int)$data->get('rxDROffset1', 0),
                    'rxDataRate2' => (int)$data->get('rxDataRate2', 0),
                    'rxDelay1' => (int)$data->get('rxDelay1', 0),
                    'rxFreq2' => (int)$data->get('rxFreq2', 0),
                    'supports32bitFCnt' => $data->get('supports32bitFCnt') ? true : false,
                    'supportsClassB' => $data->get('supportsClassB') == 'true' ? true : false,
                    'supportsClassC' => $data->get('supportsClassC') == 'true' ? true : false,
                    'supportsJoin' => $data->get('supportsJoin') === '1' ? true : false
                ],
                'name' => $data->get('name'),
                'networkServerID' => $this->loraService->getNetworkServerID(),
                'organizationID' => $this->loraService->getOrganizationId()
            ];
        } catch (\Exception $e) {
            throw new LoraException('Device Profile Data Invalid', 700);
        }

        return $res;
    }

}
