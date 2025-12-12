<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserVerification;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class UserVerificationController extends Controller
{
    // Mengajukan Verifikasi (Farmer/Buyer)
    public function submit(Request $request)
    {
        // ... (function submit remains the same)
        $request->validate([
            'full_name' => 'required|string|max:255',
            'nik'       => 'required|string|max:20',
            'address'   => 'required|string',
            'ktp_image' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $user = $request->user();

        $oldVerification = UserVerification::where('user_id', $user->id)->first();
        if ($oldVerification && $oldVerification->ktp_image) {
            Storage::disk('public')->delete($oldVerification->ktp_image);
        }

        $path = $request->file('ktp_image')->store('ktp', 'public');

        $verification = UserVerification::updateOrCreate(
            ['user_id' => $user->id],
            [
                'full_name'  => $request->full_name,
                'nik'        => $request->nik,
                'address'    => $request->address,
                'ktp_image'  => $path,
                'status'     => 'pending', 
                'submitted_at' => now(),
                'verified_at' => null,
            ]
        );

        return response()->json([
            "message" => "Pengajuan verifikasi dikirim",
            "data"    => $verification
        ]);
    }

    // Cek status verifikasi user (Untuk Profile User)
    public function status(Request $request)
    {
        $user = $request->user();
        $v = UserVerification::where('user_id', $user->id)->first(); 

        return response()->json([
            "status" => $v->status ?? 'belum mengajukan',
            "role" => $user->role, 
            "is_verified" => $user->verified
        ]);
    }
    
    // 🔥 PERBAIKAN: Mendapatkan daftar pengajuan pending (untuk Admin)
    public function getPendingSubmissions(Request $request)
    {
        // 🔥 KRITIS: Cek role Admin manual
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Akses ditolak. Anda bukan admin.'], 403);
        }
        
        $pending = UserVerification::where('status', 'pending')->with('user')->get();
        
        return response()->json(["data" => $pending]);
    }
    
    // 🔥 FUNGSI BARU: Admin mengubah status verifikasi
    public function updateStatus(Request $request, $userId)
    {
        // 🔥 KRITIS: Cek role Admin manual
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Akses ditolak. Anda bukan admin.'], 403);
        }
        
        $request->validate([
            'status' => 'required|in:verified,rejected',
        ]);
        
        $user = User::findOrFail($userId);
        $verification = UserVerification::where('user_id', $userId)->firstOrFail();
        
        $verification->status = $request->status;
        $verification->note = $request->note;
        
        if ($request->status === 'verified') {
            $verification->verified_at = now();
            $user->role = 'farmer'; 
            $user->save();
        } 
        
        $verification->save();

        return response()->json([
            "message" => "Status verifikasi diperbarui.",
            "user_new_role" => $user->role,
            "verification_status" => $verification->status
        ], 200);
    }
}