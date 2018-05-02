<?php

namespace App\Http\Controllers\v1;

use App\Exceptions\GeneralException;
use App\Exceptions\LoraException;
use App\Gateway;
use App\Repository\Helper\Response;
use App\Repository\Services\CoreService;
use App\Repository\Services\GatewayService;
use App\Repository\Services\LoraService;
use Carbon\Carbon;
use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use MongoDB\BSON\ObjectId;

class GatewayController extends Controller
{
    protected $coreService;
    protected $loraService;
    protected $gatewayService;

    /**
     * ProjectController constructor.
     * @param GatewayService $gatewayService
     * @param CoreService $coreService
     * @param LoraService $loraService
     */
    public function __construct(GatewayService $gatewayService,
                                CoreService $coreService,
                                LoraService $loraService)
    {
        $this->coreService = $coreService;
        $this->loraService = $loraService;
        $this->gatewayService = $gatewayService;

        $this->middleware('can:view,gateway')->only(['info']);
        $this->middleware('can:delete,gateway')->only(['delete']);
        $this->middleware('can:create,App\Gateway')->only(['create']);
    }


    /**
     * @param Request $request
     * @return array
     * @throws GeneralException
     * @throws LoraException
     */
    public function create(Request $request)
    {
        $this->gatewayService->validateCreateGateway($request);
        $data = $request->only(['altitude', 'mac', 'latitude', 'longitude', 'description']);
        $data = array_merge($data, [
            'organizationID' => $this->loraService->getOrganizationId(),
            'networkServerID' => $this->loraService->getNetworkServerID(),
        ]);
        $id = new ObjectId();
        $data['name'] = (string)$id;
        $data['altitude'] = intval($data['altitude']);
        $data['latitude'] = floatval($data['latitude']);
        $data['longitude'] = floatval($data['longitude']);
        $data['ping'] = $request->get('ping') === '1' ? true : false;
        $this->loraService->sendGateway($data);
        $gateway = $this->gatewayService->insertGateway($request, $id);
        return Response::body(compact('gateway'));
    }


    /**
     * @param Gateway $gateway
     * @return array
     */
    public function info(Gateway $gateway)
    {
        //$gateway['firstSeenAt'] = $info->firstSeenAt;
        try {
            $info = $this->loraService->getGW($gateway['mac']);
            $time = lora_time($info->lastSeenAt);
            $last_seen = [
                'time' => (string)lora_time($info->lastSeenAt),
                'status' => Carbon::now()->subHour() > $time ? 'red' : 'green'
            ];
            $gateway['last_seen_at'] = $last_seen;
        } catch (LoraException $e) {
            $gateway['last_seen_at'] = ['time' => '', 'status' => ''];
        }
        return Response::body(compact('gateway'));
    }

    /**
     * @return array
     */
    public function list()
    {
        $gateways = Auth::user()->gateways()->get();
        foreach ($gateways as $gateway) {
            try {
                $info = $this->loraService->getGW($gateway['mac']);
                $time = lora_time($info->lastSeenAt);
                $last_seen = [
                    'time' => (string)lora_time($info->lastSeenAt),
                    'status' => Carbon::now()->subHour() > $time ? 'red' : 'green'
                ];
                $gateway['last_seen_at'] = $last_seen;
            } catch (LoraException $e) {
                $gateway['last_seen_at'] = ['time' => '', 'status' => ''];
            }
            //$gateway['ping'] = $info->ping;
        }
        return Response::body(compact('gateways'));
    }

    /**
     * @param Gateway $gateway
     * @return array
     * @throws LoraException
     * @throws \Exception
     */
    public function delete(Gateway $gateway)
    {
        $gateway->delete();
        $gateways = Gateway::where('mac', $gateway['mac'])->get();
        if (!count($gateways))
            $this->loraService->deleteGateway($gateway['mac']);
        return Response::body(['success' => 'true']);
    }


}
