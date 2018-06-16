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
use Ixudra\Curl\CurlService;

class LanService
{
    protected $base_url;
    protected $curlService;

    public function __construct(CurlService $curlService)
    {
        $this->base_url = config('iot.lan.serverBaseUrl');
        $this->curlService = $curlService;
    }

    /**
     * @param Collection $data
     * @param $application_id
     * @param $deviceProfileID
     * @return string
     * @throws GeneralException
     */
    public function postDevice(Collection $data, $application_id, $deviceProfileID)
    {
        Log::debug("LAN Send Device:\t" . $data['devEUI']);
        $url = $this->base_url . '/application/' . $application_id . '/device';
        $data = $data->only([
            'name',
            'devEUI',
        ])->merge([
            'applicationID' => $application_id,
            'deviceProfileID' => $deviceProfileID
        ]);
        try {
            $data['devEUI'] = (int)$data['devEUI'];
        } catch (\Exception $e) {
            throw new GeneralException('خطا در پارامترها', GeneralException::VALIDATION_ERROR);
        }
        $response = $this->send($url, $data, 'post');
        return json_encode($response);
    }

    /**
     * @param $name
     * @return array|object
     * @throws GeneralException
     */
    public function postDeviceProfile($name)
    {
        Log::debug("Lan Send Device Profile");
        $url = $this->base_url . '/device-profile';
        return $this->send($url, ['name' => $name], 'post');
    }

    /**
     * @param $data
     * @return string
     * @throws GeneralException
     */
    public function activateDevice($data)
    {
        Log::debug("Lan Active Device ABP\t" . $data['devEUI']);
        $url = $this->base_url . '/api/device/' . $data['devEUI'] . '/activate';
        return $this->send($url, $data, 'post');
    }

    /**
     * @param $description
     * @param $id
     * @return string
     * @throws GeneralException
     */
    public function postApp($description, $id)
    {
        Log::debug("LAN Create Project\t" . $id);
        $url = $this->base_url . '/application';
        $data = [
            'name' => (string)$id,
            'description' => $description
        ];
        $response = $this->send($url, $data, 'post');
        return $response->ID;
    }


    public function getDevices($application_id)
    {
        Log::debug("LAN Get Device\t" . $application_id);
        $url = $this->base_url . '/application/' . $application_id . '/device';
        return $this->send($url, [], 'get');

    }

    /**
     * @param $url
     * @param $data
     * @param string $method

     * @return array|object
     * @throws GeneralException
     */
    private function send($url, $data, $method)
    {
        if (env('LAN_TEST') == 1) {
            return $this->fake();
        }

        $response = $this->curlService->to($url)
            ->withData($data)
            ->withOption('SSL_VERIFYHOST', false)
            ->returnResponseObject()
            ->asJsonRequest()
            ->asJsonResponse()
            ->withTimeout('5');
        $new_response = null;
        switch ($method) {
            case 'get':
                $new_response = $response->get();
                break;
            case 'post':
                $new_response = $response->post();
                break;
            case 'delete':
                $new_response = $response->delete();
                break;
            default:
                $new_response = $response->get();
                break;
        }
        /*
        Log::debug('-----------------------------------------------------');
        Log::debug(print_r($data, true));
        Log::debug(print_r($new_response, true));
        Log::debug('-----------------------------------------------------');
        */

        if ($new_response->status == 0) {
            throw new GeneralException($new_response->error, 0);
        }
        if ($new_response->status == 200) {
            return $new_response->content ?: [];
        }
        throw new GeneralException(
            $new_response->content->error ?: '',
            $new_response->status
        );

    }

    public function fake()
    {
        return (object)[
            'status' => 200,
            'content' => [
                'key' => 'value'
            ]
        ];
    }
}
