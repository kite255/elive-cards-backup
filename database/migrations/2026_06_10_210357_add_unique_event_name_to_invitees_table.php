<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('event_guests')) {
            return;
        }

        if (
            ! Schema::hasColumn('event_guests', 'event_id') ||
            ! Schema::hasColumn('event_guests', 'guest_name')
        ) {
            return;
        }

        DB::statement("
            DELETE g1 FROM event_guests g1
            INNER JOIN event_guests g2
                ON g1.event_id = g2.event_id
                AND LOWER(TRIM(g1.guest_name)) = LOWER(TRIM(g2.guest_name))
                AND g1.id < g2.id
        ");

        Schema::table('event_guests', function (Blueprint $table) {
            $table->unique(['event_id', 'guest_name'], 'event_guests_event_guest_name_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('event_guests')) {
            return;
        }

        Schema::table('event_guests', function (Blueprint $table) {
            $table->dropUnique('event_guests_event_guest_name_unique');
        });
    }
};