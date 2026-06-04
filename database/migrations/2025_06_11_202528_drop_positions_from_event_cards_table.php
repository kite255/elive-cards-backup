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
        Schema::table('event_cards', function (Blueprint $table) {
            $table->dropColumn('statusPositionX');
            $table->dropColumn('statusPositionY');
            $table->dropColumn('qrcodePositionX');
            $table->dropColumn('qrcodePositionY');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_cards', function (Blueprint $table) {
            $table->string('statusPositionX');
            $table->string('statusPositionY');
            $table->string('qrcodePositionX');
            $table->string('qrcodePositionY');
        });
    }
};
