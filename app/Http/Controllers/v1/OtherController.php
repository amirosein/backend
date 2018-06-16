<?php

namespace App\Http\Controllers\v1;

use App\Exceptions\GeneralException;
use App\Repository\Helper\Response;
use App\Repository\Services\CoreService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class OtherController extends Controller
{
    protected $coreService;

    /**
     * OtherController constructor.
     * @param CoreService $coreService
     */
    public function __construct(CoreService $coreService)
    {
        $this->coreService = $coreService;
    }


    public function decryptPhyPayload(Request $request)
    {
        $messages = [
            'appskey.required' => 'لطفا کلید اپلیکسشن(appskey) را وارد کنید',
            'netskey.required' => 'لطفا کلید شبکه(netskey) را وارد کنید',
            'phyPayload.required' => 'لطفا داده رمز شده را وارد کنید',
        ];

        $validator = Validator::make($request->all(), [
            'appskey' => 'required',
            'netskey' => 'required',
            'phyPayload' => 'required',
        ], $messages);

        if ($validator->fails())
            throw new  GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);
        $data = $request->only(['appskey', 'netskey', 'phyPayload']);
        return Response::body($this->coreService->decryptPhyPayload($data['appskey'], $data['netskey'], $data['phyPayload']));
    }
}
