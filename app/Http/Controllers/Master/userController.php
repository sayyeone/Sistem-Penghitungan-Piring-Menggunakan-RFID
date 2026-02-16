<?php

namespace App\Http\Controllers\Master;

use App\Models\User;
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
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $role = $request->input('role');

        // Build query with search and filter
        $query = User::query(); // Show all users, not just active

        // Search by name or email
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($role && $role !== 'all') {
            $query->where('role', $role);
        }

        $users = $query->paginate($perPage);

        return response()->json(
            [
                'status' => true,
                'message' => 'Data berhasil didapatkan',
                'data' => UserResource::collection($users->items()),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ]
            ],
            200
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
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Validasi gagal!',
                    'errors' => $validator->errors(),
                ],
                422
            );
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role ?? 'kasir',
            'password' => Hash::make($request->password),
            'status' => $request->status ?? '1',
        ]);

        return response()->json(
            [
                'status' => true,
                'message' => 'user berhasil Ditambahkan',
                'data' => new UserResource($user),
            ],
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'User tidak ditemukan!',
                ],
                404
            );
        }

        return response()->json(
            [
                'status' => true,
                'message' => 'User ditemukan!',
                'data' => new UserResource($user),
            ],
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'User tidak ditemukan!',
                ],
                404
            );
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Validasi gagal!',
                    'errors' => $validator->errors(),
                ],
                422
            );
        }

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('role')) {
            $user->role = $request->role;
        }

        if ($request->filled('status')) {
            $user->status = $request->status;
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
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id); // Don't filter by status

        if (!$user) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'User tidak ditemukan!',
                ],
                404
            );
        }

        $user->status = '0';
        $user->save();

        return response()->json(
            [
                'status' => true,
                'message' => 'user berhasil Dinonaktifkan!',
            ],
            200
        );
    }
}
