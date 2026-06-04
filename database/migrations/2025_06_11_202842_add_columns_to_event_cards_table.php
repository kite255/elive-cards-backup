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
            $table->string('guest_name_font_size')->nullable();
            $table->string('guest_name_color')->nullable();
            $table->string('guest_cardtype_font_size')->nullable();
            $table->string('guest_cardtype_color')->nullable();
            $table->string('guest_cardtype_background_color')->nullable();
            $table->string('qr_code_size')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_cards', function (Blueprint $table) {
            $table->dropColumn('guest_name_font_size');
            $table->dropColumn('guest_name_color');
            $table->dropColumn('guest_cardtype_font_size');
            $table->dropColumn('guest_cardtype_color');
            $table->dropColumn('guest_cardtype_background_color');
            $table->dropColumn('qr_code_size');
        });
    }
};
