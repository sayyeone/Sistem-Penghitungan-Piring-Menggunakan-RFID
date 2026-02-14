<?php

namespace App\Http\Controllers\Master;

use App\Models\plate;
use App\Models\item;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlateResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class plateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        // Build query with search
        $query = plate::query();

        // Search by RFID UID or plate name
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('rfid_uid', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Order and paginate
        $plates = $query->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Data Berhasil Diambil!',
            'data' => PlateResource::collection($plates->items()),
            'meta' => [
                'current_page' => $plates->currentPage(),
                'last_page' => $plates->lastPage(),
                'per_page' => $plates->perPage(),
                'total' => $plates->total(),
                'from' => $plates->firstItem(),
                'to' => $plates->lastItem(),
            ]
        ], 200);
    }

    public function store(Request $request)
    {
        // Allow creating plate with new Item (name/price) OR existing item_id
        $validator = Validator::make($request->all(), [
            'rfid_uid' => 'required|unique:plates,rfid_uid',
            'name' => 'required_without:item_id',
            'price' => 'required_without:item_id',
            'item_id' => 'sometimes|exists:items,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal!',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $itemId = $request->item_id;

            // Auto-create item if name/price provided
            if (!$itemId) {
                // Using 'item' model (lowercase per file scan)
                $newItem = item::create([
                    'nama_item' => $request->name,
                    'harga' => $request->price,
                    'kategori' => 'General',
                    'status' => '1'
                ]);
                $itemId = $newItem->id;
            }

            $plate = plate::create([
                'item_id' => $itemId,
                'rfid_uid' => $request->rfid_uid,
                'status' => '1'
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'messaage' => 'Plate berhasil ditambahkan!',
                'data' => new PlateResource($plate)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat plate: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        $plate = plate::find($id);

        if (!$plate) {
            return response()->json([
                'status' => false,
                'message' => 'Plate tidak ditemukan!'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Plate ditemukan!',
            'data' => new PlateResource($plate)
        ], 200);
    }

    // New Endpoint: Check RFID directly
    public function getByRfid($uid)
    {
        $plate = plate::with('item')->where('rfid_uid', $uid)->first();

        if (!$plate) {
            return response()->json([
                'status' => false,
                'message' => 'Plate RFID tidak ditemukan!'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Plate ditemukan!',
            'data' => new PlateResource($plate)
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $plate = plate::find($id);

        if (!$plate) {
            return response()->json([
                'status' => false,
                'message' => 'Plate tidak ditemukan!'
            ], 404);
        }

        $plate->update($request->only('rfid_uid', 'status'));

        // Update Item info if name/price provided
        if ($request->has('name') || $request->has('price')) {
            $item = item::find($plate->item_id);
            if ($item) {
                if ($request->has('name'))
                    $item->nama_item = $request->name;
                if ($request->has('price'))
                    $item->harga = $request->price;
                $item->save();
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Plate berhasil diperbarui!',
            'data' => new PlateResource($plate)
        ], 200);
    }

    public function destroy(string $id)
    {
        $plate = plate::find($id);

        if (!$plate) {
            return response()->json([
                'status' => false,
                'message' => 'Plate tidak ditemukan!'
            ], 404);
        }

        $plate->delete();

        return response()->json([
            'status' => true,
            'message' => 'Plate berhasil dihapus!'
        ], 200);
    }
}
