<?php

namespace App\Models;

use App\Models\item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class plate extends Model
{
    use HasFactory;

    protected $table = 'plates';

    protected $fillable = [
        'item_id',
        'rfid_uid',
        'status'
    ];

    public function item(){
        return $this->belongsTo(item::class);
    }
}

