<?php

namespace App\Http\Controllers\v1;

use App\Exceptions\CodecException;
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
    }

    /**
     * @param Request $request
     * @param Thing $thing
     * @return array
     * @throws CodecException
     * @throws \App\Exceptions\GeneralException
     */
    public function create(Request $request, Thing $thing)
    {
        $user = Auth::user();
        if ($thing->user()->first()['id'] != $user->id)
            abort(404);
        $this->codecService->validateCreateCodec($request, $thing);
        if ($thing->codec()->first())
            $codec = $this->codecService->updateCodec($request, $thing);
        else
            $codec = $this->codecService->insertCodec($request, $thing);

        if($thing->project()->first())
            $this->coreService->sendCodec($thing->project()->first(), $thing, $codec->code);
        return Response::body(compact('codec'));
    }


    /**
     * @param Project $project
     * @return array
     */
    public function get(Project $project)
    {
        $user = Auth::user();
        if ($project['owner']['id'] != $user->id)
            abort(404);
        $codecs = $project->codecs()->get();

        return Response::body(compact('codecs'));
    }

}