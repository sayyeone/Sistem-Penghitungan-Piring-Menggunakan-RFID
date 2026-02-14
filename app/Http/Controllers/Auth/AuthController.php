<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error_code' => 'validation_failed',
                'message' => 'Validasi gagal!',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if user exists
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'error_code' => 'user_not_found',
                'message' => 'Email tidak terdaftar di sistem',
            ], 404);
        }

        // Check if password is correct
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'error_code' => 'invalid_password',
                'message' => 'Password yang Anda masukkan salah',
            ], 401);
        }

        // Check if account is active
        if ($user->status !== '1') {
            return response()->json([
                'status' => false,
                'error_code' => 'account_inactive',
                'message' => 'Akun Anda tidak aktif. Hubungi administrator.',
            ], 403);
        }

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Login berhasil',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logout berhasil',
        ]);
    }
}
