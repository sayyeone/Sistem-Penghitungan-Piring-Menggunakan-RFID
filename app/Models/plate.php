<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class plate extends Model
{
    protected $table = 'plates';

    protected $fillable = [
        'item_id',
        'rfid_uid',
        'nama_piring',
        'harga',
        'status'
    ];
}

