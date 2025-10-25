<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'department' => ['required', 'in:ppic,produksi'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Email atau password salah'], 422);
        }
        if ($user->status !== 'active') {
            return response()->json(['message' => 'Akun tidak aktif'], 403);
        }
        if ($user->department !== $data['department']) {
            return response()->json(['message' => 'Departemen tidak sesuai'], 403);
        }

        // single-session (opsional): hapus token lama
        $user->tokens()->delete();

        $token = $user->createToken('api')->plainTextToken;
        $redirect = $user->department === 'ppic' ? '/ppic' : '/produksi';

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department,
                'role' => $user->role,
                'status' => $user->status,
            ],
            'redirect_to' => $redirect,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
