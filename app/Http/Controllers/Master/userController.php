<?php

namespace App\Http\Controllers\Master;

use App\Models\User;
use Nette\Utils\Json;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class userController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = User::where('status', '1')->get();

        return response()->json(
            [
                'status' => true,
                'message' => 'Data berhasil didapatkan',
                'data' => UserResource::collection($user),
            ],
            200,
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Validasi gagal!',
                    'errors' => $validator->errors(),
                ],
                422,
            );
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'status' => '1',
        ]);

        return response()->json(
            [
                'status' => true,
                'message' => 'user berhasil Ditambahkan',
                'data' => new UserResource($user),
            ],
            201,
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::where('id', $id)->where('status', '1')->first();

        if (!$user) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'User tidak ditemukan!',
                ],
                404,
            );
        }

        return response()->json(
            [
                'status' => true,
                'message' => 'User ditemukan!',
                'data' => new UserResource($user),
            ],
            200,
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::where('id', $id)->where('status', '1')->first();

        if (!$user) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'User tidak ditemukan!',
                ],
                404,
            );
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Validasi gagal!',
                    'errors' => $validator->errors(),
                ],
                422,
            );
        }

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('role')) {
            $user->role = $request->role;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json(
            [
                'status' => true,
                'message' => 'User berhasil diperbarui',
                'data' => new UserResource($user),
            ],
            200,
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::where('id', $id)->where('status', '1')->first();

        if (!$user) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'User tidak ditemukan!',
                ],
                404,
            );
        }

        $user->status = '0';
        $user->save();

        return response()->json(
            [
                'status' => true,
                'message' => 'user berhasil Dinonaktifkan!',
            ],
            200,
        );
    }
}
