<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class transaction_detail extends Model
{
    protected $table = 'transaction_details';

    protected $fillable = [
        'transaction_id',
        'plate_id',
        'harga'
    ];
}
