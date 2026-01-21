<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\plate;

class item extends Model
{
    protected $table = 'items';

    protected $fillable = [
        'nama_item',
        'kategori',
        'harga',
        'status'
    ];

    public function plates(){
        return $this->hasMany(plate::class);
    }
}
