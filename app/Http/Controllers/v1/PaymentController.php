<?php

namespace App\Http\Controllers\v1;

use App\Discount;
use App\Exceptions\GeneralException;
use App\Package;
use App\Repository\Services\Payment\ZarinPalService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
    protected $zarinPalService;

    public function __construct(ZarinPalService $zarinPalService)
    {
        $this->zarinPalService = $zarinPalService;
    }

    public function pay(Package $package, Request $request)
    {
        $code = $request->get('code');
        $discount = $this->discount($code);
        $url = $this->zarinPalService->pay($package, $discount);
        if ($code && !$discount)
            throw new GeneralException('کد تخفیف اشتباه است', GeneralException::NOT_FOUND);
        if ($url)
            return $url;
        throw new GeneralException(GeneralException::M_UNKNOWN, GeneralException::UNKNOWN_ERROR);

    }

    private function discount($code)
    {
        $discount = Discount::where('code', $code)->where('expired', false)->first();
        if (!$discount)
            return 0;
        $discount->expired = true;
        $discount->save();
        return $discount->value;
    }
}