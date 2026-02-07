<?php

namespace App\Models;

use App\Models\plate;
use App\Models\transaction;
use Illuminate\Database\Eloquent\Model;

class transaction_detail extends Model
{
    protected $table = 'transaction_details';

    protected $fillable = [
        'transaction_id',
        'plate_id',
        'harga'
    ];

    public function transaction(){
        return $this->belongsTo(transaction::class);
    }

    public function plate(){
        return $this->belongsTo(plate::class);
    }
}
