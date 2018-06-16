<?php
/**
 * Created by PhpStorm.
 * User: sajjad
 * Date: 19/1/18
 * Time: 2:50 PM
 */

namespace App\Repository\Services\Payment;


use App\Codec;
use App\Exceptions\GeneralException;
use App\Invoice;
use App\Package;
use App\Project;
use App\Thing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Zarinpal\Zarinpal;

class ZarinPalService
{
    protected $zarinpal;

    public function __construct(Zarinpal $zarinpal)
    {
        $this->zarinpal = $zarinpal;
    }

    /**
     * @param Package $package
     * @param $discount
     * @return void
     */
    public function createInvoice(Package $package, $discount)
    {
        $user = Auth::user();
        $invoice = Invoice::create([
            'user_id' => $user['_id'],
            'price' => $package['price'] - $discount,
            'discount' => $discount,
            'gate' => 'zarinpal',
            'package' => $package->toArray(),
            'status' => false,
        ]);
        $payment = [
            'CallbackURL' => route('payment.verify', $invoice['_id']),
            'Amount' => $invoice['price'],
            'Description' => 'پرداخت بسته ' . $package['name'],
            'Email' => $user['email'],    // Optional
        ];
        $response = $this->zarinpal->request($payment);
        if ($response['Status'] === 100) {
            $authority = $response['Authority'];
            $invoice['authority'] = $authority;
            $invoice->save();
            return $invoice;
        }
        return false;

    }

    public function verify(Invoice $invoice, Request $request)
    {
        $payment = [
            'Authority' => $request->get('Authority'),
            'Status' => $request->get('Status'),
            'Amount' => $invoice['price']
        ];
        $response = $this->zarinpal->verify($payment);
        if ($response['Status'] === 100) {
            $invoice->status = true;
            $invoice->save();
            return true;
        }
        return false;
    }

    public function pay(Invoice $invoice)
    {
        return redirect($this->zarinpal->getRedirectUrl($invoice['authority']));
    }

}