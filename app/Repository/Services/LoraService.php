<?php
/**
 * Created by PhpStorm.
 * User: sajjad
 * Date: 01/20/18
 * Time: 6:42 PM
 */

namespace App\Repository\Services;


use App\Exceptions\GeneralException;
use App\Exceptions\LoraException;
use App\Thing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Ixudra\Curl\CurlService;

class LoraService
{
    protected $token;
    protected $base_url;
    protected $organization_id;
    protected $curlService;
    protected $networkServerID;
    protected $serviceProfileID;

    public function __construct(CurlService $curlService)
    {
        $this->token = Storage::get('jwt.token');
        $this->base_url = config('iot.lora.serverBaseUrl');
        $this->organization_id = config('iot.lora.organizationID');
        $this->networkServerID = config('iot.lora.networkServerID');
        $this->serviceProfileID = config('iot.lora.serviceProfileID');
        $this->curlService = $curlService;
    }

    /**
     * @param Collection $data
     * @param $application_id
     * @param $deviceProfileID
     * @return string
     * @throws LoraException
     */
    public function postDevice(Collection $data, $application_id, $deviceProfileID)
    {
        Log::debug("Lora Send Device:\t" . $data['devEUI']);
        $url = $this->base_url . '/api/devices';
        $data = $data->only([
            'description',
            'devEUI',
            'name',
        ])->merge([
            'applicationID' => $application_id,
            'deviceProfileID' => $deviceProfileID
        ]);
        if (!$data['description'])
            $data['description'] = '';
        $this->send($url, $data, 'post');
        return $data;
    }

    /**
     * @param $data
     * @return void
     * @throws LoraException
     */
    public function postDeviceProfile($data)
    {
        Log::debug("Lora Send Device Profile");
        $url = $this->base_url . '/api/device-profiles';
        return $this->send($url, $data, 'post');
    }


    /**
     * @param $deviceProfileId
     * @return string
     * @throws LoraException
     */
    public function deleteDeviceProfile($deviceProfileId)
    {
        Log::debug("Lora Delete Device Profile:\t" . $deviceProfileId);
        try {
            $url = $url = $this->base_url . '/api/device-profiles/' . $deviceProfileId;
            return $this->send($url, [], 'delete');
        } catch (LoraException $exception) {
            if ($exception->getCode() == 9)
                throw new LoraException('ابتدا اشیای متصل را حذف کنید.', GeneralException::ACCESS_DENIED);
        }
    }

    /**
     * @param $deviceId
     * @return string
     * @throws LoraException
     */
    public function deleteDevice($deviceId)
    {
        Log::debug("Lora Delete Device:\t" . $deviceId);
        $url = $url = $this->base_url . '/api/devices/' . $deviceId;
        return $this->send($url, [], 'delete');
    }

    /**
     * @param $data
     * @param $dev_eui
     * @return string
     * @throws LoraException
     */
    public function updateDevice($data, $dev_eui)
    {
        Log::debug("Lora Update Device:\t" . $dev_eui);
        $url = $url = $this->base_url . '/api/devices/' . $dev_eui;
        $this->send($url, $data, 'put');
        return true;
    }

    /**
     * @param $data
     * @return string
     * @throws LoraException
     */
    public function sendGateway($data)
    {
        Log::debug("Lora Send Gateway");
        $url = $url = $this->base_url . '/api/gateways';
        $this->send($url, $data, 'post', 409);
        return true;
    }

    /**
     * @param $mac
     * @return string
     * @throws LoraException
     */
    public function deleteGateway($mac)
    {
        Log::debug("Lora Delete Gateway\t" . $mac);
        $url = $url = $this->base_url . '/api/gateways/' . $mac;
        return $this->send($url, [], 'delete');
    }

    /**
     * @param $data
     * @return string
     * @throws LoraException
     */
    public function activateDevice($data)
    {
        Log::debug("Lora Active Device ABP\t" . $data['devEUI']);
        $url = $url = $this->base_url . '/api/devices/' . $data['devEUI'] . '/activate';
        return $this->send($url, $data, 'post');
    }

    /**
     * @param $data
     * @return string
     * @throws LoraException
     */
    public function sendKeys($data)
    {
        Log::debug("Lora Active Device OTAA\t" . $data['devEUI']);
        $url = $url = $this->base_url . '/api/devices/' . $data['devEUI'] . '/keys';
        try {
            return $this->send($url, $data, 'post');
        } catch (\Exception $e) {
            return $this->send($url, $data, 'put');
        }
    }

    /**
     * @return \Illuminate\Config\Repository|mixed
     */
    public function getOrganizationId()
    {
        return $this->organization_id;
    }

    /**
     * @return \Illuminate\Config\Repository|mixed
     */
    public function getNetworkServerID()
    {
        return $this->networkServerID;
    }

    /**
     * @param $description
     * @param $id
     * @return string
     * @throws LoraException
     */
    public function postApp($description, $id)
    {
        Log::debug("Lora Create Project\t" . $id);
        $url = $this->base_url . '/api/applications';
        $data = [
            'organizationID' => $this->organization_id,
            'serviceProfileID' => $this->serviceProfileID,
            'name' => (string)$id,
            'description' => $description
        ];
        $response = $this->send($url, $data, 'post');
        return $response->id;
    }

    /**
     * @param $applicationId
     * @return string
     * @throws LoraException
     */
    public function deleteApp($applicationId)
    {
        Log::debug("Lora Delete Project\t" . $applicationId);
        $url = $url = $this->base_url . '/api/applications/' . $applicationId;
        return $this->send($url, [], 'delete');

    }

    /**
     * @param $mac
     * @return string
     * @throws LoraException
     */
    public function getGW($mac)
    {
        Log::debug("Lora Get Gateway\t" . $mac);
        $url = $url = $this->base_url . '/api/gateways/' . $mac;
        return $this->send($url, [], 'get');

    }

    /**
     * @param $dev_eui
     * @return string
     * @throws LoraException
     */
    public function getDevice($dev_eui)
    {
        Log::debug("Lora Get Device\t" . $dev_eui);
        $url = $url = $this->base_url . '/api/devices/' . $dev_eui;
        return $this->send($url, [], 'get');
    }

    /**
     * @param $dev_eui
     * @return string
     * @throws LoraException
     */
    public function getActivation($dev_eui)
    {
        Log::debug("Lora Get Device Activation\t" . $dev_eui);
        $url = $url = $this->base_url . '/api/devices/' . $dev_eui . '/activation';
        return $this->send($url, [], 'get');
    }

    private function send($url, $data, $method = 'get', $accept = 200)
    {
        if (env('LORA_TEST'))
            return (object)[
                'status' => 200,
                'content' => [
                    'key' => 'value'
                ]
            ];

        $response = $this->curlService->to($url)
            ->withData($data)
            ->withOption('SSL_VERIFYHOST', false)
            ->withHeader('Authorization: ' . $this->token)
            ->returnResponseObject();
        $new_response = $this->sendMethods($method, $response);
        /*
        Log::debug('-----------------------------------------------------');
        Log::debug(print_r($data, true));
        Log::debug(print_r($new_response, true));
        Log::debug('-----------------------------------------------------');
        */
        if ($new_response->status == 401 | $new_response->status == 403) {
            $this->authenticate();
            $response = $response->withHeader('Authorization: ' . $this->token);
            $new_response = $this->sendMethods($method, $response);
        }
        if ($new_response->status == 0) {
            throw new LoraException($new_response->error, 0);
        }
        if ($new_response->status == 200 || $new_response->status == $accept)
            return $new_response->content;
        throw new LoraException($new_response->content->error, $new_response->content->code);
    }

    private function authenticate()
    {
        $response = $this->curlService->to($this->base_url . '/api/internal/login')
            ->withData(['username' => 'admin', 'password' => 'admin'])
            ->asJson()
            ->withOption('SSL_VERIFYHOST', false)
            ->post();;
        $this->token = $response->jwt;
        Storage::put('jwt.token', $this->token);
    }


    /**
     * @param $method string
     * @param $response \Ixudra\Curl\Builder
     * @return mixed
     */
    private function sendMethods($method, $response)
    {

        switch ($method) {
            case 'get':
                $new_response = $response->asJsonResponse()->get();
                break;
            case 'post':
                $new_response = $response->asJson()->post();
                break;
            case 'put':
                $new_response = $response->asJson()->put();
                break;
            case 'delete':
                $new_response = $response->asJsonResponse()->delete();
                break;
            default:
                $new_response = $response->asJsonResponse()->get();
                break;
        }
        return $new_response;
    }


}