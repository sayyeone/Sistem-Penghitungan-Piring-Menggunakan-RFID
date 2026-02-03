<?php

namespace App\Models;

use App\Models\User;
use App\Models\payment;
use App\Models\transaction_detail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class transaction extends Model
{
    use HasFactory;
    protected $table = 'transactions';

    protected $fillable = [
        'user_id',
        'total_harga',
        'status',
        'payment_type'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function details(){
        return $this->hasMany(transaction_detail::class);
    }

    public function payment(){
        return $this->hasOne(payment::class);
    }
}
