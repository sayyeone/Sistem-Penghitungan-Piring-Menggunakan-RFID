<?php

namespace App\Http\Controllers\Master;

use App\Models\plate;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlateResource;
use Illuminate\Support\Facades\Validator;

class plateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $plate = plate::orderby('status', 'desc')->orderBy('rfid_uid', 'asc')->get();

        return response()->json([
            'status' => true,
            'message' => 'Data Berhasil diambil semua!',
            'data' => PlateResource::collection($plate)
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id'    => 'required|exists:items,id',
            'rfid_uid' => 'required|unique:plates,rfid_uid'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal!',
                'errors' => $validator->errors(),
            ], 422);
        }

        $plate = plate::create([
            'item_id' => $request->item_id,
            'rfid_uid' => $request->rfid_uid,
            'status' => "1"
        ]);

        return response()->json([
            'status' => true,
            'messaage' => 'Plate berhasil ditambahkan!',
            'data' => new PlateResource($plate)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $plate = plate::find($id);

        if(!$plate){
            return response()->json([
                'status'=> false,
                'message' => 'Plate tidak ditemukan!'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Plate ditemukan!',
            'data' => new PlateResource($plate)
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $plate = plate::find($id);

        if(!$plate){
            return response()->json([
                'status'=> false,
                'message' => 'Plate tidak ditemukan!'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'item_id'    => 'required|exists:items,id',
            'rfid_uid' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal!',
                'errors' => $validator->errors(),
            ], 422);
        }

        $plate->update($request->only([
            'item_id',
            'rfid_uid',
            'status'
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Plate berhasil Diperbarui!',
            'data' => new PlateResource($plate)
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $plate = plate::find($id);

        if(!$plate){
            return response()->json([
                'status'=> false,
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
