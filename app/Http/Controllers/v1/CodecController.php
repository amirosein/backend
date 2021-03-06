<?php

namespace App\Http\Controllers\v1;

use App\Codec;
use App\Exceptions\GeneralException;
use App\Project;
use App\Repository\Helper\Response;
use App\Repository\Services\CodecService;
use App\Repository\Services\CoreService;
use App\Thing;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CodecController extends Controller
{
    protected $codecService;
    protected $coreService;

    public function __construct(CodecService $codecService,
                                CoreService $coreService)
    {
        $this->codecService = $codecService;
        $this->coreService = $coreService;

        $this->middleware('can:update,codec')->only(['update']);
        $this->middleware('can:delete,codec')->only(['delete']);
        $this->middleware('can:create,App\Codec')->only(['create']);
        $this->middleware('can:send,App\Codec')->only(['send']);
    }

    /**
     * @param Project $project
     * @param Request $request
     * @return array
     * @throws GeneralException
     */
    public function create(Project $project, Request $request)
    {
        $this->codecService->validateCreateCodec($request);
        $codec = $this->codecService->insertCodec($request, $project);
        return Response::body(compact('codec'));
    }


    /**
     * @param Thing $thing
     * @param Request $request
     * @return array
     * @throws GeneralException
     */
    public function send(Thing $thing, Request $request)
    {
        $codec = null;
        if ($request->get('codec_id')) {
            $codec = Codec::where('global', true)->where('_id', $request->get('codec_id'))->first();
            $thing['codec_id'] = $request->get('codec_id');
            $thing['codec'] = '';
            if ($codec)
                $codec = $codec['code'];
        }
        if (!$codec) {
            $codec = $request->get('codec');
            $thing['codec_id'] = '';
            $thing['codec'] = $codec;
        }
        $project = $thing->project()->first();
        $this->coreService->sendCodec($project, $thing, $codec);
        $thing->save();
        return Response::body(['success' => 'true']);
    }

    /**
     * @param Thing $thing
     * @param Request $request
     * @return array
     * @throws GeneralException
     */
    public function test(Thing $thing, Request $request)
    {
        $project = $thing->project()->first();
        $decode = $request->get('decode') ? true : false;
        if ($decode)
            $response = $this->coreService->decode($project, $thing, $request->get('data'));
        else
            $response = $this->coreService->encode($project, $thing, $request->get('data'));
        return Response::body($response);
    }

    /**
     * @param Thing $thing
     * @param Request $request
     * @return array
     */
    public function getThing(Thing $thing, Request $request)
    {
        $codec = $thing['codec'];
        $codec_id = $thing['codec_id'];
        return Response::body($codec_id ? compact('codec_id') : compact('codec'));
    }


    /**
     * @param Project $project
     * @return array
     */
    public function list(Project $project)
    {
        $codecs = $project->codecs()->get();
        $globals = Codec::where('global', true)->select('name', '_id')->get();
        return Response::body(['codecs' => $codecs, 'globals' => $globals]);
    }

    /**
     * @param Project $project
     * @param Codec $codec
     * @return array
     * @throws \Exception
     */
    public function delete(Project $project, Codec $codec)
    {
        $codec->delete();
        return Response::body(['success' => true]);
    }

    /**
     * @param Project $project
     * @param Codec $codec
     * @return array
     * @throws \Exception
     */
    public function get(Project $project, Codec $codec)
    {
        return Response::body(compact('codec'));
    }

    /**
     * @param Project $project
     * @param Codec $codec
     * @param Request $request
     * @return array
     * @throws GeneralException
     */
    public function update(Project $project, Codec $codec, Request $request)
    {
        $this->codecService->validateCreateCodec($request);
        $codec = $this->codecService->updateCodec($request, $codec);
        return Response::body(compact('codec'));
    }

}
