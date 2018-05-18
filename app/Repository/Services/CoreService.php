<?php
/**
 * Created by PhpStorm.
 * User: Sajjad
 * Date: 02/7/18
 * Time: 11:42 AM
 */

namespace App\Repository\Services;


use App\Exceptions\GeneralException;
use App\Exceptions\LoraException;
use App\Gateway;
use App\Project;
use App\Scenario;
use App\Thing;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Ixudra\Curl\CurlService;

class CoreService
{
    protected $base_url;
    protected $port;
    protected $dmPort;
    protected $downLinkPort;
    protected $curlService;

    public function __construct(CurlService $curlService)
    {
        $this->base_url = config('iot.core.serverBaseUrl');
        $this->port = config('iot.core.port');
        $this->dmPort = config('iot.core.dmPort');
        $this->downLinkPort = config('iot.core.downLinkPort');
        $this->curlService = $curlService;
    }

    /**
     * @param $id
     * @return array
     * @throws GeneralException
     */
    public function postProject($id)
    {
        Log::debug("Core Send Project\t" . $id);
        $url = '/api/project';
        $response = $this->send($url, ['name' => (string)$id], 'post');
        return $response;
    }

    /**
     * @param $project_id
     * @return array
     * @throws GeneralException
     */
    public function deleteProject($project_id)
    {
        Log::debug("Core Delete Project\t" . $project_id);
        $url = '/api/project/' . $project_id;
        $response = $this->send($url, [], 'delete');
        return $response;
    }


    /**
     * @param Project $project
     * @param Thing $thing
     * @return array
     * @throws GeneralException
     */
    public function postThing(Project $project, Thing $thing)
    {
        Log::debug("Core Send Thing\t" . $project['_id']);
        $url = '/api/project/' . $project['container']['name'] . '/things';
        $data = [
            'name' => $thing['interface']['devEUI'],
        ];
        $response = $this->send($url, $data, 'post');
        return $response;
    }

    /**
     * @param Project $project
     * @param Thing $thing
     * @param $codec
     * @return array
     * @throws GeneralException
     */
    public function sendCodec(Project $project, Thing $thing, $codec)
    {
        Log::debug("Core Send Codec\t" . $project['_id']);
        $url = '/api/codec/' . $thing['interface']['devEUI'];
        $response = $this->send($url, $codec, 'post', $project['container']['runner']['port'], 0);
        return $response;
    }

    /**
     * @param Thing $thing
     * @param $since
     * @param $until
     * @return array
     * @throws GeneralException
     */
    public function thingData(Thing $thing, $since, $until)
    {
        Log::debug("Core Thing Data");
        $url = '/api/things/' . $thing['interface']['devEUI'];
        $response = $this->send($url, ['since' => (int)$since, 'until' => (int)$until], 'get', $this->dmPort);
        return $response;
    }

    /**
     * @param array $ids
     * @param $since
     * @param $until
     * @return array
     * @throws GeneralException
     */
    public function thingsData($ids, $since, $until)
    {
        Log::debug("Core Things Data");
        $url = '/api/things';
        $response = $this->send($url, ['since' => (int)$since, 'until' => (int)$until, 'thing_ids' => $ids], 'post', $this->dmPort);
        return $response;
    }


    /**
     * @param Project $project
     * @param Scenario $scenario
     * @return array
     * @throws GeneralException
     */
    public function sendScenario(Project $project, Scenario $scenario)
    {
        Log::debug("Core Send Scenario\t" . $project['_id']);
        $url = '/api/scenario/' . $project['container']['name'];
        $response = $this->send($url, $scenario->code, 'post', $project['container']['runner']['port'], 0);
        return $response;
    }


    /**
     * @param Project $project
     * @param $code
     * @return array
     * @throws GeneralException
     */
    public function lint(Project $project, $code)
    {
        Log::debug("Core Lint\t" . $project['_id']);
        $url = '/api/lint';
        $response = $this->send($url, $code, 'post', $project['container']['runner']['port'], 0);
        return $response;
    }

    /**
     * @param $mac
     * @return array
     * @throws GeneralException
     */
    public function enableGateway($mac)
    {
        Log::debug("Core Enable Gateway\t" . $mac);
        $url = '/api/gateway/' . $mac . '/enable';
        $response = $this->send($url, [], 'get', $this->dmPort);
        return $response;
    }


    /**
     * @return array
     * @throws GeneralException
     */
    public function projectList()
    {
        Log::debug("Core Project List");
        $url = '/api/project';
        $response = $this->send($url, [], 'get');
        return $response;
    }

    /**
     * @param $project_id
     * @param $limit
     * @return array
     * @throws GeneralException
     */
    public function projectLogs($project_id, $limit)
    {
        //Log::debug("Core Project Log");
        $url = '/api/project/' . $project_id . '/logs?limit=' . $limit;
        $response = $this->send($url, [], 'get');
        return $response;
    }

    /**
     * @param $mac
     * @param $since
     * @return array
     * @throws GeneralException
     */
    public function gatewayFrames($mac, $since)
    {
        //Log::debug("Core Project Log");
        $url = '/api/gateway/' . $mac;
        $response = $this->send($url, ['since' => $since], 'get', $this->dmPort);
        return $response;
    }

    /**
     * @param Project $project
     * @param Thing $thing
     * @param $data
     * @return array
     * @throws GeneralException
     */
    public function downLinkThing(Project $project, Thing $thing, $data)
    {
        Log::debug("DownLink Project List\t" . $thing['dev_eui']);
        $url = '/api/send';
        $data = ['thing' => $thing->toArray(), 'data' => $data, 'project_id' => $project->application_id];
        $response = $this->send($url, $data, 'post', $this->downLinkPort);
        return $response;
    }


    /**
     * @param $url
     * @param $data
     * @param string $method
     * @param string $port
     * @param int $json_request
     * @return array|object
     * @throws GeneralException
     */
    private function send($url, $data, $method = 'get', $port = '', $json_request = 1)
    {
        if (env('CORE_TEST') == 1)
            return $this->fake();

        $port = $port == '' ? $this->port : $port;
        $url = $this->base_url . ':' . $port . $url;

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

        /*
        $code = $new_response->status;
        try {
            if ($code != 200 && gettype($new_response->content) == 'string')
                $content = json_decode($new_response->content);
            else
                $content = $new_response->content;
        } catch (\Exception $e) {
            $content = $new_response->content;
        }
        */

        if ($new_response->status == 0) {
            throw new GeneralException($new_response->error, 0);
        }
        if ($new_response->status == 200)
            return $new_response->content ?: [];
        throw new GeneralException($new_response->content->error ?: '', $new_response->status);

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