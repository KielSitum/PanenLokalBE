<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserVerification;
use Illuminate\Support\Facades\Storage;

class UserVerificationController extends Controller
{
    public function submit(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'nik'       => 'required|string|max:20',
            'address'   => 'required|string',
            'ktp_image' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $path = $request->file('ktp_image')->store('ktp', 'public');

        $verification = UserVerification::updateOrCreate(
            ['user_id' => auth()->id()],    // Jika sudah pernah submit → update
            [
                'full_name'  => $request->full_name,
                'nik'        => $request->nik,
                'address'    => $request->address,
                'ktp_image'  => $path,
                'status'     => 'pending',
                'submitted_at' => now(),
            ]
        );

        return response()->json([
            "message" => "Pengajuan verifikasi dikirim",
            "data"    => $verification
        ]);
    }

    // Cek status verifikasi user
    public function status()
    {
        $v = UserVerification::where('user_id', auth()->id())->first();

        return response()->json([
            "status" => $v->status ?? 'belum mengajukan',
            "data" => $v
        ]);
    }
}
