<?php

namespace App\Http\Controllers\Master;

use App\Models\plate;
use App\Models\item;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlateResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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

        // Search by RFID UID or item name
        if ($search) {
            $query->join('items', 'plates.item_id', '=', 'items.id')
                ->where(function ($q) use ($search) {
                    $q->where('plates.rfid_uid', 'like', "%{$search}%")
                        ->orWhere('items.nama_item', 'like', "%{$search}%");
                })
                ->select('plates.*');
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
            $name = $request->name ? ucwords(strtolower($request->name)) : null;
            $price = $request->price;

            // If item_id is not provided but name is, check if item with that name already exists
            if (!$itemId && $name) {
                $existingItem = item::where('nama_item', $name)->first();
                if ($existingItem) {
                    $itemId = $existingItem->id;
                }
            }

            // Auto-create item if name/price provided and no item_id found
            if (!$itemId && $name) {
                // Using 'item' model (lowercase per file scan)
                $newItem = item::create([
                    'nama_item' => $name,
                    'harga' => $price,
                    'kategori' => 'tambahan', // Changed from 'General' to valid ENUM value
                    'status' => '1'
                ]);
                $itemId = $newItem->id;
            }

            $plate = plate::create([
                'item_id' => $itemId,
                'rfid_uid' => $request->rfid_uid,
                'status' => '1'
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'model' => 'Plate',
                'model_id' => $plate->id,
                'description' => "Menambahkan plate baru dengan RFID: {$plate->rfid_uid}",
                'properties' => ['rfid_uid' => $plate->rfid_uid]
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

        $validator = Validator::make($request->all(), [
            'rfid_uid' => 'sometimes|unique:plates,rfid_uid,' . $id,
            'item_id' => 'sometimes|exists:items,id',
            'name' => 'sometimes|string',
            'price' => 'sometimes|numeric',
            'status' => 'sometimes|in:0,1'
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

            // 1. Update RFID and Status if provided
            if ($request->has('rfid_uid'))
                $plate->rfid_uid = $request->rfid_uid;
            if ($request->has('status'))
                $plate->status = $request->status;

            // 2. Handle Item Linking Re-logic
            $itemId = $request->item_id;
            $name = $request->has('name') ? ucwords(strtolower($request->name)) : null;
            $price = $request->price;

            // Option A: Direct item_id link
            if ($itemId) {
                $plate->item_id = $itemId;
            }
            // Option B: Name/Price provided (Handle correction or new item creation)
            else if ($name) {
                // Check if item with this name already exists
                $existingItem = item::where('nama_item', $name)->first();

                if ($existingItem) {
                    // Update its price if it's the target and price is provided
                    if ($price) {
                        $existingItem->update(['harga' => $price]);
                    }
                    $plate->item_id = $existingItem->id;
                } else {
                    // Create new item for this plate (mistake correction/custom)
                    $newItem = item::create([
                        'nama_item' => $name,
                        'harga' => $price ?? 0,
                        'kategori' => 'tambahan',
                        'status' => '1'
                    ]);
                    $plate->item_id = $newItem->id;
                }
            }

            $plate->save();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'model' => 'Plate',
                'model_id' => $plate->id,
                'description' => "Memperbarui data plate RFID: {$plate->rfid_uid}",
                'properties' => $request->all()
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Plate berhasil diperbarui!',
                'data' => new PlateResource($plate)
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui plate: ' . $e->getMessage()
            ], 500);
        }
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

        $rfid = $plate->rfid_uid;
        $plate->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'model' => 'Plate',
            'model_id' => $id,
            'description' => "Menghapus plate RFID: {$rfid}"
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Plate berhasil dihapus!'
        ], 200);
    }
}
