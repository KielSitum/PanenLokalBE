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
            ->get()
            ->map(function ($transaction) {
                return $this->formatTransaction($transaction);
            });

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

        $transactions = Transaction::with(['listing.images', 'farmer'])
            ->where('buyer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($transaction) use ($user) {
                $formatted = $this->formatTransaction($transaction);
                
                // ✅ Check if user already reviewed this transaction
                $hasReviewed = Review::where('transaction_id', $transaction->id)
                    ->where('reviewer_id', $user->id)
                    ->exists();
                
                $formatted['has_reviewed'] = $hasReviewed;
                
                return $formatted;
            });

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

   private function formatTransaction($transaction)
{
    $listing = $transaction->listing;
    
    return [
        'id' => $transaction->id,
        'buyer_id' => $transaction->buyer_id,
        'farmer_id' => $transaction->farmer_id,
        'listing_id' => $transaction->listing_id,
        'status' => $transaction->status,
        'contacted_at' => $transaction->contacted_at?->toIso8601String(),
        'completed_at' => $transaction->completed_at?->toIso8601String(),
        'created_at' => $transaction->created_at->toIso8601String(),
        'updated_at' => $transaction->updated_at->toIso8601String(),
        
        // ✅ Listing info dengan URL LENGKAP
        'listing' => [
            'id' => $listing->id,
            'title' => $listing->title,
            'price' => (string) $listing->sold_price,
            'stock' => $listing->stock,
            'category' => $listing->category,
            'images' => $listing->images->map(function ($image) {
                // ✅ PENTING: Return URL lengkap dengan domain
                return [
                    'id' => $image->id,
                    'url' => $image->url 
                        ? (str_starts_with($image->url, 'http') 
                            ? $image->url 
                            : url($image->url)) // ✅ Tambah domain jika relatif
                        : ($image->image_url 
                            ? (str_starts_with($image->image_url, 'http')
                                ? $image->image_url
                                : url($image->image_url))
                            : null),
                ];
            })->filter(fn($img) => $img['url'] !== null)->values(), // ✅ Filter null
        ],
        
        // Buyer & Farmer info
        'buyer' => $transaction->buyer ? [
            'id' => $transaction->buyer->id,
            'name' => $transaction->buyer->full_name,
            'phone' => $transaction->buyer->phone,
        ] : null,
        
        'farmer' => $transaction->farmer ? [
            'id' => $transaction->farmer->id,
            'name' => $transaction->farmer->full_name,
            'phone' => $transaction->farmer->phone,
        ] : null,
    ];
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

        $transaction->load(['listing.images', 'farmer']);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dibuat',
            'data' => $this->formatTransaction($transaction)
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
            'data' => $this->formatTransaction($transaction)
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

        // ✅ Check if review already exists
        $existingReview = Review::where('transaction_id', $transaction->id)
            ->where('reviewer_id', $user->id)
            ->first();
            
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