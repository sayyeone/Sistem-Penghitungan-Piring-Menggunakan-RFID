<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\plate;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class item extends Model
{
    use HasFactory;
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
