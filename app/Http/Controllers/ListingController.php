<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\ListingImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ListingController extends Controller
{
    // Fungsi untuk menambah listing baru
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
        'images.*' => 'image|mimes:jpeg,png,jpg|max:5120', // Max 5MB per gambar
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

        // Simpan gambar
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
            'listing' => $listing->load('images'),
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
}