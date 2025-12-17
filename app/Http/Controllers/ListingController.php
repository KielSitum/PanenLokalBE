<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ListingController extends Controller
{
    // 4. MARK AS SOLD - ✅ FIXED VERSION
    public function markAsSold(Request $request, $id)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'sold_price' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $listing = Listing::where('id', $id)
                ->where('farmer_id', $user->id)
                ->firstOrFail();

            // ✅ Update listing
            $listing->update([
                'is_sold' => true,
                'sold_price' => $request->sold_price
            ]);

            // ✅ Update semua transaksi terkait listing ini menjadi success
            $updatedCount = Transaction::where('listing_id', $id)
                ->where('status', 'negotiating')
                ->update([
                    'status' => 'success',
                    'completed_at' => now(),
                ]);

            DB::commit();

            \Log::info("Listing $id marked as sold. Updated $updatedCount transactions to success.");

            return response()->json([
                'success' => true,
                'message' => "Listing ditandai sebagai terjual. $updatedCount transaksi diselesaikan.",
                'data' => $listing->load('images'),
                'updated_transactions' => $updatedCount
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Mark as sold failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menandai laku: ' . $e->getMessage()
            ], 500);
        }
    }

    // ============================================
    // METHODS LAINNYA TETAP SAMA
    // ============================================

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'area' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'category' => ['nullable', Rule::in(['sayur', 'buah', 'organik'])],
            'type' => ['required', Rule::in(['Timbang', 'Borong'])],
            'contact_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:255',
            'description' => 'nullable|string',
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $listing = Listing::create([
                'farmer_id' => $user->id,
                'title' => $request->title,
                'location' => $request->location,
                'area' => $request->area,
                'price' => $request->price,
                'stock' => $request->stock,
                'category' => $request->category ?? 'sayur',
                'type' => $request->type,
                'contact_name' => $request->contact_name,
                'contact_number' => $request->contact_number,
                'description' => $request->description,
            ]);

            foreach ($request->file('images') as $image) {
                $filename = 'listing_' . $listing->id . '_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('listings', $filename, 'public');
                
                ListingImage::create([
                    'listing_id' => $listing->id,
                    'image_url' => '/storage/' . $path,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Listing berhasil dipublikasikan!',
                'data' => $listing->load('images'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Listing creation failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat listing: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getActiveListings()
    {
        try {
            $listings = Listing::where('is_sold', false)
                ->with(['images', 'farmer'])
                ->orderBy('created_at', 'desc')
                ->get();

            $listings = $listings->map(function ($listing) {
                $images = [];
                if ($listing->images) {
                    $images = $listing->images->map(function ($image) {
                        return url('/api/image/' . basename($image->image_url));
                    })->toArray();
                }
                
                return [
                    'id' => $listing->id,
                    'title' => $listing->title,
                    'description' => $listing->description,
                    'location' => $listing->location,
                    'area' => $listing->area,
                    'price' => (float) $listing->price,
                    'stock' => (float) $listing->stock,
                    'category' => $listing->category,
                    'type' => $listing->type,
                    'contact_name' => $listing->contact_name,
                    'contact_number' => $listing->contact_number,
                    'images' => $images,
                    'farmer_name' => $listing->farmer ? $listing->farmer->full_name : null,
                    'created_at' => $listing->created_at,
                    'updated_at' => $listing->updated_at,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $listings
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Get active listings failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            $listings = Listing::where('farmer_id', $user->id)
                ->with('images')
                ->orderBy('created_at', 'desc')
                ->get();

            $listings = $listings->map(function ($listing) {
                $images = [];
                if ($listing->images) {
                    $images = $listing->images->map(function ($image) {
                        $url = $image->image_url;
                        if (!str_starts_with($url, 'http')) {
                            $url = url($image->image_url);
                        }
                        return $url;
                    })->toArray();
                }
                
                return [
                    'id' => $listing->id,
                    'title' => $listing->title,
                    'description' => $listing->description,
                    'location' => $listing->location,
                    'area' => $listing->area,
                    'price' => $listing->price,
                    'stock' => $listing->stock,
                    'category' => $listing->category,
                    'type' => $listing->type,
                    'contact_name' => $listing->contact_name,
                    'contact_number' => $listing->contact_number,
                    'is_sold' => $listing->is_sold ?? false,
                    'sold_price' => $listing->sold_price,
                    'images' => $images,
                    'created_at' => $listing->created_at,
                    'updated_at' => $listing->updated_at,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $listings
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Get listings failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $listing = Listing::where('id', $id)
                ->where('farmer_id', $user->id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'location' => 'nullable|string|max:255',
                'area' => 'nullable|string|max:255',
                'contact_number' => 'nullable|string|max:255',
                'price' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [];
            if ($request->has('location')) $updateData['location'] = $request->location;
            if ($request->has('area')) $updateData['area'] = $request->area;
            if ($request->has('contact_number')) $updateData['contact_number'] = $request->contact_number;
            if ($request->has('price')) $updateData['price'] = $request->price;

            $listing->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Listing berhasil diupdate',
                'data' => $listing->load('images')
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Update listing failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal update listing: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $listing = Listing::where('id', $id)
                ->where('farmer_id', $user->id)
                ->firstOrFail();

            foreach ($listing->images as $image) {
                $path = str_replace('/storage/', '', $image->image_url);
                Storage::disk('public')->delete($path);
            }

            $listing->delete();

            return response()->json([
                'success' => true,
                'message' => 'Listing berhasil dihapus'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Delete listing failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal hapus listing: ' . $e->getMessage()
            ], 500);
        }
    }
}