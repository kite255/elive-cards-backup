<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_guests', function (Blueprint $table) {
            if (! Schema::hasColumn('event_guests', 'note')) {
                $table->text('note')->nullable()->after('guest_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('event_guests', function (Blueprint $table) {
            if (Schema::hasColumn('event_guests', 'note')) {
                $table->dropColumn('note');
            }
        });
    }
};