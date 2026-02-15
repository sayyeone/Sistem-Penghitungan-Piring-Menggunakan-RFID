<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\item;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $kategori = $request->input('kategori');

        // Build query with search and filter
        $query = item::query();

        // Search by item name
        if ($search) {
            $query->where('nama_item', 'like', "%{$search}%");
        }

        // Filter by kategori
        if ($kategori && $kategori !== 'all') {
            $query->where('kategori', $kategori);
        }

        // Order and paginate
        $items = $query->orderBy('status', 'desc')
            ->orderBy('kategori', 'asc')
            ->orderBy('nama_item', 'asc')
            ->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Data Berhasil Diambil!',
            'data' => ItemResource::collection($items->items()),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ]
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        // Normalize name to Title Case for comparison
        $normalizedName = ucwords(strtolower($request->nama_item));

        // Manually check for unique name to provide better feedback
        $existing = item::where('nama_item', $normalizedName)->first();
        if ($existing) {
            return response()->json([
                'status' => false,
                'message' => "Menu '{$normalizedName}' sudah ada dalam daftar.",
                'existing_id' => $existing->id,
                'errors' => ['nama_item' => ["Nama item ini sudah digunakan."]]
            ], 422);
        }

        // Standard validation for other fields
        $validator = Validator::make($request->all(), [
            'nama_item' => 'required|string|min:2|max:50',
            'harga' => 'required|integer',
            'kategori' => 'required|in:makanan,minuman,dessert,camilan,paket,tambahan'
        ]);

        // respon json bila gagal validasi
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        // Normalize name to Title Case
        $data = $request->all();
        $data['nama_item'] = ucwords(strtolower($request->nama_item));

        // menginsertkan item ke tabel database
        $item = item::create($data);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'created',
            'model' => 'Item',
            'model_id' => $item->id,
            'description' => "Menambahkan item baru: {$item->nama_item} ({$item->kategori})",
            'properties' => $request->all()
        ]);

        // respon bila data berhasil diinsertkan
        return response()->json([
            'status' => true,
            'message' => 'Item berhasil ditambahkan!',
            'data' => new ItemResource($item)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // mencari item berdasarkan id yang cocok
        $item = item::find($id);

        // respon bila item tidak ditemukan
        if (!$item) {
            return response()->json([
                'status' => false,
                'message' => 'Item tidak ditemukan!'
            ], 404);
        }

        // respon bila berhasil ditemukan
        return response()->json([
            'status' => true,
            'message' => 'Item ditemukan',
            'data' => new ItemResource($item)
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // mencari item berdasarkan id
        $item = item::find($id);

        // respon bila tidak ditemukan
        if (!$item) {
            return response()->json([
                'status' => false,
                'message' => 'Item tidak ditemukan!'
            ], 404);
        }

        // Normalize name to Title Case for comparison
        $normalizedName = ucwords(strtolower($request->nama_item));

        // Manually check for unique name (excluding current item)
        $existing = item::where('nama_item', $normalizedName)
            ->where('id', '!=', $id)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => false,
                'message' => "Menu '{$normalizedName}' sudah ada dalam daftar.",
                'existing_id' => $existing->id,
                'errors' => ['nama_item' => ["Nama item ini sudah digunakan."]]
            ], 422);
        }

        // Standard validation for other fields
        $validator = Validator::make($request->all(), [
            'nama_item' => 'required|string|min:2|max:50',
            'harga' => 'required|integer',
            'kategori' => 'required|in:makanan,minuman,dessert,camilan,paket,tambahan'
        ]);

        // respon json bila gagal validasi
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only([
            'nama_item',
            'harga',
            'kategori'
        ]);

        if (isset($data['nama_item'])) {
            $data['nama_item'] = ucwords(strtolower($data['nama_item']));
        }

        $item->update($data);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'updated',
            'model' => 'Item',
            'model_id' => $item->id,
            'description' => "Memperbarui data item: {$item->nama_item}",
            'properties' => $request->all()
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Item Berhasil Diupdate!',
            'data' => new ItemResource($item)
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // mencari item berdasarkan id
        $item = item::find($id);

        // respon bila tidak ditemukan
        if (!$item) {
            return response()->json([
                'status' => false,
                'message' => 'Item tidak ditemukan!'
            ], 404);
        }

        $name = $item->nama_item;
        $item->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'model' => 'Item',
            'model_id' => $id,
            'description' => "Menghapus item: {$name}"
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Item Berhasil DIhapus!',
        ], 200);
    }
}
