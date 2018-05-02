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

        $this->middleware('can:update,project')->only(['create']);
        $this->middleware('can:view,project')->only(['list']);
        $this->middleware('can:update,thing')->only(['send']);
        $this->middleware('can:view,thing')->only(['getThing']);
        $this->middleware('can:delete,codec')->only(['delete']);
        $this->middleware('can:view,codec')->only(['get']);
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
        $codec = $request->get('codec');
        $project = $thing->project()->first();
        $this->coreService->sendCodec($project, $thing, $codec);
        $thing->codec = $codec;
        $thing->save();
        return Response::body(['success' => 'true']);
    }

    /**
     * @param Project $project
     * @param Thing $thing
     * @param Request $request
     * @return array
     */
    public function getThing(Project $project, Thing $thing, Request $request)
    {
        $codec = $thing->codec;
        return Response::body(compact('codec'));
    }


    /**
     * @param Project $project
     * @return array
     */
    public function list(Project $project)
    {
        $codecs = $project->codecs()->get();
        return Response::body(compact('codecs'));
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
