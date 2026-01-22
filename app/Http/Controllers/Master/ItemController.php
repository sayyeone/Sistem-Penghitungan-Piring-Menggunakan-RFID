<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
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
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(),[
            'nama_item' => 'required|string|min:2|max:50',
            'harga' => 'required|integer'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Semua Form wajib diisi!',
                'errors' => $validator->errors(),
            ], 422);
        }

        $item = item::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Item berhasil ditambahkan!',
            'data' => $item
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
