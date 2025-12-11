<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user(); // Mendapatkan user yang sedang login

        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'slogan' => 'nullable|string|max:255',
            'phone' => 'required|string|max:15',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Untuk gambar
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2. Menangani Upload Gambar
        $avatarUrl = $user->avatar_url; 
        if ($request->hasFile('profile_image')) {
            // Hapus gambar lama (jika ada)
            if ($user->avatar_url) {
                // Asumsi avatar_url menyimpan path setelah 'storage/'
                Storage::disk('public')->delete(str_replace('/storage/', '', $user->avatar_url));
            }

            // Simpan gambar baru
            $path = $request->file('profile_image')->store('avatars', 'public');
            $avatarUrl = url(Storage::url($path));
        }
        
        // 3. Update Data User
        $user->full_name = $request->full_name;
        $user->slogan = $request->slogan;
        $user->phone = $request->phone;
        // Kita simpan lokasi sebagai string
        $user->latitude = $request->latitude;
        $user->longitude = $request->longitude;
        $user->avatar_url = $avatarUrl;

        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $user,
        ], 200);
    }
}