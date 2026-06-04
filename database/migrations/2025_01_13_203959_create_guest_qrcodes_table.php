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
        Schema::create('guest_qrcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_guests_id')->constrained()->onDelete('cascade')->onUpdate('cascade');   
            $table->String('qrcode_name');
            $table->String('has_qrcode')->nullable()->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qrcodes');
    }
};
