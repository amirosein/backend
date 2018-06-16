<?php

namespace App\Http\Controllers\v1;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Project;
use App\Repository\Services\CoreService;
use App\Thing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DownLinkController extends Controller
{
    protected $coreService;

    public function __construct(CoreService $coreService)
    {
        $this->coreService = $coreService;
    }

    public function sendThing(Thing $thing, Request $request)
    {
        $project = $thing->project()->first();
        $validator = Validator::make($request->all(), [
            'data' => 'required|json',
            'fport' => 'integer',
        ], [
            'data.required' => 'لطفا داده‌ها را وارد کنید',
            'data.json' => 'لطفا داده‌ها را درست کنید',
            'fport.integer' => 'لطفا پورت را درست وارد کنید',
        ]);
        if ($validator->fails())
            throw new GeneralException('اطلاعات را کامل و درست وارد کنید', 407);
        $this->coreService->downLinkThing($project,
            $thing,
            $request->get('data')
            , $request->get('fport') ?: 2
            , $request->get('confirmed') ? true : false);

    }
}
