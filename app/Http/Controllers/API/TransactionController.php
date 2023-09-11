<?php

namespace App\Http\Controllers\Api;

use Exception;
use Midtrans\Snap;
use Midtrans\Config;
use App\Models\Transaction;
use App\Models\Transactions;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $food_id = $request->input('food_id');
        $status = $request->input('status');


        if ($id) {
            $transaction = Transaction::find(['food', 'id']);

            if ($transaction) {
                return ResponseFormatter::success(
                    $transaction,
                    'Data Transaction Berhasil Diambil'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Data Transaction tidak ada',
                    404
                );
            }
        }

        $transaction = Transaction::with(['food', 'user'])->where('user_id', Auth::user()->id);

        if ($food_id) {
            $transaction->where('food_id', $food_id);
        }

        if ($status) {
            $transaction->where('food_id', $status);
        }
        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Data List produk berhasil diambil'
        );
    }

    public function update(Request $request, $id)
    {

        $transaction = Transaction::findOrFail($id);

        $transaction->update($request->all());

        return ResponseFormatter::success($transaction, 'Transaction berhasil di update');
    }

    public function checkout(Request $request)
    {

        $request->validate([
            'food_id' => 'required|exists:food_id',
            'user_id' => 'required|exists:user_id',
            'quantity' => 'required',
            'total' => 'required',
            'status' => 'required'
        ]);
        
        $transaction = Transaction::create([
            'food_id' => $request->food_id,
            'user_id' => $request->user_id,
            'quantity' => $request->quantity,
            'total' => $request->total,
            'status' => $request->status,
            'payment_url' => '',

        ]);

        //konfigurasi midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        //panggil transaksi midtrans yang di buat
        $transaction = Transaction::with(['food', 'user'])->find($transaction->id);


        //membuat transaksi midtrans

        $midtrans = [
            'transaction_details' => [
                'order_id' => $transaction->id,
                'grass_amount' => $transaction->total,
            ],
            'costumer_details' => [
                'frist_name' => $transaction->user->name,
                'email' => $transaction->user->email
            ],
            'enable_payments' => [
                'gopay',
                'bank_tranfer'
            ],
            'vtweb' => []
        ];

        //memangil midtrans
        try {
            $paymert_url = Snap::createTransaction($midtrans)->redirect_url;

            $transaction->payment_url = $paymert_url;
            $transaction->save();

            //mengembalikan Data ke API
            dd($transaction);
            return ResponseFormatter::success($transaction, 'Transaksi Berhasil');
        } catch (Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 'Transaksi Gagal');
        }

        //
    }
}
