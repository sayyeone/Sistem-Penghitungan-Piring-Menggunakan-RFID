<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id'); // Foreign yang bener = big integer
            $table->string('rfid_uid');
            $table->string('nama_piring');
            $table->integer('harga');
            $table->enum('status', [0,1]); // 0 = sedang habis | 1 = tersedia
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('items'); // Foreign Key
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plates');
    }
};
