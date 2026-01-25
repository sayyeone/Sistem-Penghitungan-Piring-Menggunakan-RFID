<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // mengambil semua data berdasarkan terbaru
        $item = item::orderBy('status', 'desc')->orderBy('kategori', 'asc')->orderBy('nama_item', 'asc')->get();

        return response()->json([
            'status' => true,
            'message' => 'Data Berhasil Diambil!',
            'data' => ItemResource::collection($item) // collection diguanakn ketika data berupa array
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        // validasi input $request sebelum diinsertkan
        $validator = Validator::make($request->all(),[
            'nama_item' => 'required|string|min:2|max:50',
            'harga' => 'required|integer'
        ]);

        // respon json bila gagal validasi
        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Semua Form wajib diisi!',
                'errors' => $validator->errors(),
            ], 422);
        }

        // menginsertkan item ke tabel database
        $item = item::create($request->all());

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
        if(!$item){
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
        if(!$item){
            return response()->json([
                'status' => false,
                'message' => 'Item tidak ditemukan!'
            ], 404);
        }

        // validasi input $request sebelum diupdate
        $validator = Validator::make($request->all(),[
            'nama_item' => 'required|string|min:2|max:50',
            'harga' => 'required|integer'
        ]);

        // respon json bila gagal validasi
        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Semua Form wajib diisi!',
                'errors' => $validator->errors(),
            ], 422);
        }

        $item->update($request->only([
            'nama_item',
            'harga'
        ]));

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
        if(!$item){
            return response()->json([
                'status' => false,
                'message' => 'Item tidak ditemukan!'
            ], 404);
        }

        $item->delete();

        return response()->json([
            'status'=> true,
            'message' => 'Item Berhasil DIhapus!',
        ], 200);
    }
}
