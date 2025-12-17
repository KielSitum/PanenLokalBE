<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NotificationController extends Controller
{
    // SIMPAN FCM TOKEN
    public function saveToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        auth()->user()->update([
            'fcm_token' => $request->token
        ]);

        return response()->json([
            'message' => 'FCM token saved'
        ]);
    }

    // KIRIM NOTIFIKASI
    public function sendNotification(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title'   => 'required|string',
            'message' => 'required|string',
        ]);

        $user = User::findOrFail($request->user_id);

        if (!$user->fcm_token) {
            return response()->json([
                'message' => 'User belum memiliki FCM token'
            ], 400);
        }

        // 1. Simpan ke database
        Notification::create([
            'user_id' => $user->id,
            'title'   => $request->title,
            'message' => $request->message,
        ]);

        // 2. Kirim popup ke HP
        Http::withHeaders([
            'Authorization' => 'key=' . env('FIREBASE_SERVER_KEY'),
            'Content-Type'  => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $user->fcm_token,
            'notification' => [
                'title' => $request->title,
                'body'  => $request->message,
            ],
        ]);

        return response()->json(['success' => true]);
    }
}
