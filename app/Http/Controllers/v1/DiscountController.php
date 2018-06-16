<?php

namespace App\Http\Controllers\v1;

use App\Discount;
use App\Exceptions\GeneralException;
use App\Repository\Helper\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DiscountController extends Controller
{
    /**
     * PackageController constructor.
     */
    public function __construct()
    {
        $this->middleware('can:create,App\Package')->only(['create', 'all', 'delete']);
    }


    /**
     * @param Request $request
     * @return array
     */
    public function all(Request $request)
    {
        $discounts = Discount::orderBy('_id', 'DESC')->get();
        return Response::body(['discounts' => $discounts]);
    }


    /**
     * @param Request $request
     * @return array
     * @throws GeneralException
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|integer',
        ], [
            'value.required' => 'لطفا مقدار تخفیف را وارد کنید', 'value.integer' => 'لطفا مقدار تخفیف را درست وارد کنید',
        ]);
        if ($validator->fails())
            throw new GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);

        return Response::body(['discount' => Discount::create([
            'value' => $request->get('value'),
            'expired' => false,
            'user_id' => Auth::user()['_id'],
            'code' => substr(md5(uniqid()), 10, 10)
        ])]);

    }


    public function delete(Discount $discount)
    {
        $discount->delete();
        return Response::body(['success' => true]);
    }

}
