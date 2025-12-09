<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'full_name' => 'required',
            'email'     => 'required|email|unique:users',
            'phone'     => 'required',
            'address'   => 'required',
            'password'  => 'required|min:6',
        ]);

        $user = User::create([
            'role'      => 'buyer',
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'full_name' => $request->full_name,
            'phone'     => $request->phone,
            'address'   => $request->address,
            'avatar_url'=> $request->avatar_url ?? null
        ]);

        return response()->json([
            'status'=>true,
            'message'=>"Register berhasil!",
            'user'=>$user
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

        return response()->json([
            'status'=>true,
            'message'=>"Login berhasil!",
            'user'=>[
                'id'=>$user->id,
                'full_name'=>$user->full_name,
                'email'=>$user->email,
                'phone'=>$user->phone,
                'address'=>$user->address,
                'role'=>$user->role,
                'avatar_url'=>$user->avatar_url
            ]
        ]);
    }

}

