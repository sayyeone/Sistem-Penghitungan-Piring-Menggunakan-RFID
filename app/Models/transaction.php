<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'user_id',
        'total_harga',
        'status',
        'payment_type'
    ];
}
