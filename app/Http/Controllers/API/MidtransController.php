<?php

namespace App\Http\Controllers\API;

use Midtrans\Config;
use Midtrans\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Transactions;

class MidtransController extends Controller
{

    public function callback(Request $request)
    {

        // set konfigurasi midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');


        //buat instance midtrans notifikasi
        $notification = new Notification();

        //assing ke variabel untuk memudahkan coding
        $status = $notification->transaction_status;
        $type = $notification->payment_type;
        $fraud = $notification->fraund_status;
        $order_id = $notification->order_id;

        //cari transaksi midtrans dengan id

        $transaction = Transactions::findOrFail($order_id);


        // handle notifikasi midtrans
        if ($status == 'capture') {

            if ($type == 'credit_card') {

                if ($fraud == 'challenge') {
                    $transaction->status = "PENDING";
                } else {
                    $transaction->status = "SUCCES";
                }
            }
        } else if ($status == 'settlement') {
            $transaction->status = "SUCCES";
        } else if ($status == 'pendding') {
            $transaction->status = "PENDING";
        } else if ($status == 'deny') {
            $transaction->status = "CANCELLED";
        } else if ($status == 'expire') {
            $transaction->status = "CANCELLED";
        } else if ($status == 'cancel') {
            $transaction->status = "CANCELLED";
        }


        //simpan transaksi
        $transaction->save();
    }
    
    public function success(Request $request)
    {
        return view('midtrans.success');
    }

    public function error(Request $request)
    {
        return view('midtrans.error');
    }

    public function unfinish(Request $request)
    {
        return view('midtrans.unfinish');
    }

}
