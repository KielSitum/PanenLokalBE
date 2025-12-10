<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
public function register(Request $request)
    {
        $request->validate([
            'full_name' => 'required',
            'email'     => 'required|email|unique:users',
            'phone'     => 'required',
            'password'  => 'required|min:6',
        ]);
        
        // Cek apakah address dikirim, jika tidak, atur default
        $addressValue = $request->address ?? '-'; 

        $user = User::create([
            'role'      => 'buyer',
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'full_name' => $request->full_name,
            'phone'     => $request->phone,
            'address'   => $addressValue, // Menggunakan nilai yang disiapkan
            'avatar_url'=> $request->avatar_url ?? null
        ]);

        // 1. BUAT TOKEN BARU
        $token = $user->createToken('auth_token')->plainTextToken; 

        return response()->json([
            'status'=>true,
            'message'=>"Register berhasil!",
            'token'=>$token, // Sertakan token di root
            'user'=>$user,    // Sertakan objek user
        ],201);
    }

    public function login(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['status'=>false,'message'=>'Email atau password salah'],401);
        }

        // 2. BUAT TOKEN BARU DENGAN SANCTUM
        $token = $user->createToken('auth_token')->plainTextToken; 

        return response()->json([
            'status'=>true,
            'message'=>"Login berhasil!",
            'token'=>$token, // Sertakan token di root
            'user'=>[
                'id'=>$user->id,
                'full_name'=>$user->full_name,
                'email'=>$user->email,
                'phone'=>$user->phone,
                'address'=>$user->address,
                'role'=>$user->role,
                'slogan' => $user->slogan,
                'latitude' => $user->latitude,
                'longitude' => $user->longitude,
                'avatar_url'=>$user->avatar_url,
                'verified'=>$user->verified 
            ]
        ]);
    }
}