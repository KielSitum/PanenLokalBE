<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    /**
     * Toggle favorite status for a listing
     * If favorited -> unfavorite
     * If not favorited -> favorite
     */
    public function toggle(Request $request)
    {
        try {
            $request->validate([
                'listing_id' => 'required|exists:listings,id',
            ]);

            $userId = Auth::id();
            $listingId = $request->listing_id;

            // Check if already favorited
            $favorite = Favorite::where('user_id', $userId)
                               ->where('listing_id', $listingId)
                               ->first();

            if ($favorite) {
                // Already favorited, so unfavorite
                $favorite->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Dihapus dari favorit',
                    'is_favorited' => false,
                ], 200);
            } else {
                // Not favorited yet, so add to favorites
                Favorite::create([
                    'user_id' => $userId,
                    'listing_id' => $listingId,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Ditambahkan ke favorit',
                    'is_favorited' => true,
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status favorit: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all favorites for the authenticated user
     */
    public function index(Request $request)
    {
        try {
            $userId = Auth::id();

            $favorites = Favorite::where('user_id', $userId)
                ->with(['listing' => function($query) {
                    $query->where('is_sold', false); // Only get active listings
                }])
                ->get()
                ->filter(function($favorite) {
                    return $favorite->listing !== null; // Remove favorites with deleted/sold listings
                })
                ->map(function($favorite) {
                    $listing = $favorite->listing;
                    
                    return [
                        'id' => $listing->id,
                        'title' => $listing->title,
                        'location' => $listing->location,
                        'area' => $listing->area,
                        'price' => $listing->price,
                        'stock' => $listing->stock,
                        'contact_name' => $listing->contact_name,
                        'contact_number' => $listing->contact_number,
                        'category' => $listing->category,
                        'type' => $listing->type,
                        'description' => $listing->description,
                        'is_sold' => $listing->is_sold,
                        'images' => $listing->images ? json_decode($listing->images) : [],
                        'favorited_at' => $favorite->created_at->toDateTimeString(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $favorites->values(), // Re-index array
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat favorit: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if a listing is favorited by the authenticated user
     */
    public function check(Request $request, $listingId)
    {
        try {
            $userId = Auth::id();

            $isFavorited = Favorite::where('user_id', $userId)
                                  ->where('listing_id', $listingId)
                                  ->exists();

            return response()->json([
                'success' => true,
                'is_favorited' => $isFavorited,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memeriksa status favorit: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get favorite IDs for the authenticated user
     * Returns array of listing IDs that are favorited
     */
    public function getFavoriteIds(Request $request)
    {
        try {
            $userId = Auth::id();

            $favoriteIds = Favorite::where('user_id', $userId)
                                  ->pluck('listing_id')
                                  ->toArray();

            return response()->json([
                'success' => true,
                'favorite_ids' => $favoriteIds,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat ID favorit: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a favorite
     */
    public function destroy(Request $request, $listingId)
    {
        try {
            $userId = Auth::id();

            $favorite = Favorite::where('user_id', $userId)
                               ->where('listing_id', $listingId)
                               ->first();

            if (!$favorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Favorit tidak ditemukan',
                ], 404);
            }

            $favorite->delete();

            return response()->json([
                'success' => true,
                'message' => 'Favorit berhasil dihapus',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus favorit: ' . $e->getMessage(),
            ], 500);
        }
    }
}