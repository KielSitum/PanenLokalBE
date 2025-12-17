<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Listing;
use App\Models\Review;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    /**
     * ✅ Get farmer's transactions
     */
    public function farmerTransactions(Request $request)
    {
        $user = $request->user();

        $transactions = Transaction::with(['listing.images', 'buyer'])
            ->where('farmer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * ✅ Get buyer's transactions
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $transactions = Transaction::with(['listing.images'])
            ->where('buyer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * ✅ Create new transaction (when buyer contacts farmer)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'listing_id' => 'required|exists:listings,id',
        ]);

        $listing = Listing::findOrFail($request->listing_id);

        // Check for duplicate transaction
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

        $transaction = Transaction::create([
            'buyer_id'     => $user->id,
            'farmer_id'    => $listing->farmer_id,
            'listing_id'   => $listing->id,
            'status'       => 'negotiating',
            'contacted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dibuat',
            'data' => $transaction
        ], 201);
    }

    /**
     * ✅ Update single transaction status (by transaction ID)
     */
    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        
        $request->validate([
            'status' => 'required|in:success,failed,pending,negotiating'
        ]);

        $transaction = Transaction::findOrFail($id);
        
        // Ensure only farmer can update
        if ($transaction->farmer_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengubah transaksi ini'
            ], 403);
        }

        $transaction->status = $request->status;
        
        if (in_array($request->status, ['success', 'failed'])) {
            $transaction->completed_at = now();
        }
        
        $transaction->save();
        $transaction->load(['listing.images', 'buyer']);

        Log::info("Transaction {$id} updated to {$request->status} by farmer {$user->id}");

        return response()->json([
            'success' => true,
            'message' => 'Status transaksi berhasil diperbarui',
            'data' => $transaction
        ]);
    }

    /**
     * ✅ Update all transactions for a listing (by listing ID)
     */
    public function updateTransactionsByListing(Request $request, $listingId)
    {
        $user = $request->user();
        
        $request->validate([
            'status' => 'required|in:success,failed'
        ]);

        // Update all negotiating transactions for this listing
        $updated = Transaction::where('listing_id', $listingId)
            ->where('farmer_id', $user->id)
            ->where('status', 'negotiating')
            ->update([
                'status' => $request->status,
                'completed_at' => now(),
            ]);

        Log::info("Updated $updated transactions for listing $listingId to {$request->status}");

        return response()->json([
            'success' => true,
            'message' => "Berhasil mengupdate $updated transaksi",
            'updated_count' => $updated
        ]);
    }

    /**
     * ✅ Submit review for a transaction
     */
    public function storeReview(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $transaction = Transaction::findOrFail($request->transaction_id);

        // Ensure buyer can only review their own transaction
        if ($transaction->buyer_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak bisa mengulas transaksi ini'
            ], 403);
        }

        // Check if review already exists
        $existingReview = Review::where('transaction_id', $transaction->id)->first();
        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memberikan ulasan untuk transaksi ini'
            ], 400);
        }

        Review::create([
            'transaction_id' => $transaction->id,
            'reviewer_id' => $user->id,
            'seller_id' => $transaction->farmer_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        // Update transaction status to success (if not already)
        if ($transaction->status !== 'success') {
            $transaction->update([
                'status' => 'success',
                'completed_at' => now(),
            ]);
        }

        Log::info("Review submitted for transaction {$transaction->id}");

        return response()->json([
            'success' => true,
            'message' => 'Ulasan berhasil dikirim'
        ]);
    }
}