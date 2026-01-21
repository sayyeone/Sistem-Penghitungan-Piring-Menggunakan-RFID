<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'transaction_id',
        'midtrans_order_id',
        'snap_token',
        'payment_status'
    ];
}
