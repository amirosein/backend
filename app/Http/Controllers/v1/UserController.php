<?php

namespace App\Http\Controllers\v1;

use App\Repository\Helper\Response;
use App\Repository\Services\ConfigService;
use App\Repository\Services\CoreService;
use App\Repository\Services\FileService;
use App\Repository\Services\UserService;
use App\Http\Controllers\Controller;
use App\Thing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Psy\Exception\FatalErrorException;
use Symfony\Component\Debug\Exception\FatalThrowableError;


class UserController extends Controller
{
    protected $userService;
    protected $fileService;
    protected $configService;
    protected $coreService;

    /**
     * UserController constructor.
     * @param userService $userService
     * @param FileService $fileService
     * @param CoreService $coreService
     * @param ConfigService $configService
     */
    public function __construct(UserService $userService,
                                FileService $fileService,
                                CoreService $coreService,
                                ConfigService $configService)
    {
        $this->userService = $userService;
        $this->fileService = $fileService;
        $this->configService = $configService;
        $this->coreService = $coreService;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function update(Request $request)
    {
        $this->userService->validateUpdateUser($request);

        $user = $this->userService->updateUser($request);


        return Response::body(compact('user'));
    }


    /**
     * @param Request $request
     * @return array
     * @throws \App\Exceptions\GeneralException
     */
    public function changePassword(Request $request)
    {
        $this->userService->changePassword($request);
        return Response::body(['success' => true]);
    }


    /**
     * @return array
     */
    public function profile()
    {
        $user = Auth::user();
        return Response::body(compact('user'));
    }

    /**
     * @param Request $request
     * @return array
     */
    public function upload(Request $request)
    {
        $user = Auth::user();
        $files = $request->allFiles();
        $paths = [];
        foreach ($files as $key => $file) {
            $paths[$key] = $this->fileService->saveFile($key, $file);
        }

        $files = $user['files'] ?: [];
        foreach ($paths as $key => $value) {
            $files[$key] = $value;
        }
        $user->files = $files;

        $user->save();
        return Response::body(compact('paths'));
    }

    public function dashboard()
    {
        $user = Auth::user();
        $config = $user->config()->first();
        $charts = collect($config['widgets'])->map(function ($widget) {
            try {
                $thing = Thing::where('dev_eui', $widget['devEUI'])->first();
                $since = Carbon::now()->subMinute((int)$widget['window'])->getTimestamp();
                $until = Carbon::now()->getTimestamp();
                return [
                    'title' => $widget['title'],
                    'thing' => $thing,
                    'data' => collect($this->coreService->thingData($thing, $since, $until))
                        ->filter(function ($data) use ($widget) {
                            $data = json_decode(json_encode($data), True);
                            return isset($data['data'][$widget['key']]);
                        })
                        ->map(function ($data) use ($widget) {
                            $data = json_decode(json_encode($data), True);
                            return ['timestamp' => $data['timestamp'], 'value' => $data['data'][$widget['key']]];
                        })->values()];
            } catch (\Error $e) {
                return [];
            } catch (\Exception $e) {
                return [];
            }
        });
        return Response::body([
            'charts' => $charts,
            'things_num' => $user->things()->count(),
            'project_num' => $user->projects()->count()
        ]);
    }

    public function setWidgetChart(Request $request)
    {
        $widgets = $this->configService->setWidgetChart(collect($request->all()));
        return Response::body(['widgets' => $widgets]);
    }

    public function deleteWidgetChart(Request $request)
    {
        $this->configService->deleteWidgetChart(collect($request->all()));
        return Response::body(['success' => true]);
    }
}
