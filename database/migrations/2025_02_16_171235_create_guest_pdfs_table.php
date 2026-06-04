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
        Schema::create('guest_pdfs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_guests_id')->constrained()->onDelete('cascade')->onUpdate('cascade');   
            $table->String('pdf_name');
            $table->String('has_pdf')->nullable()->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_pdfs');
    }
};
