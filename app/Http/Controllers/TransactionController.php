<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Listing;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'listing_id' => 'required|exists:listings,id',
        ]);

        $listing = Listing::findOrFail($request->listing_id);

        // ✅ Cek duplikasi transaksi (opsional, untuk mencegah spam)
        $existingTransaction = Transaction::where('buyer_id', $user->id)
            ->where('listing_id', $listing->id)
            ->where('status', 'negotiating')
            ->first();

        if ($existingTransaction) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah menghubungi penjual ini'
            ], 400);
        }

        // ✅ Buat transaksi baru
        $transaction = Transaction::create([
            'buyer_id'    => $user->id,
            'farmer_id'   => $listing->farmer_id,
            'listing_id'  => $listing->id,
            'status'      => 'negotiating',
            'contacted_at' => now(), // ✅ Catat waktu kontak
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dibuat',
            'data' => $transaction
        ], 201);
    }
}