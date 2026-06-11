<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScannedTimeToEventGuestsTable extends Migration
{
    /**
     * Add missing scanned_time column used by ScancardController.
     */
    public function up(): void
    {
        Schema::table('event_guests', function (Blueprint $table) {
            if (! Schema::hasColumn('event_guests', 'scanned_time')) {
                $table->timestamp('scanned_time')->nullable()->after('status');
            }
        });
    }

    /**
     * Rollback safely.
     */
    public function down(): void
    {
        Schema::table('event_guests', function (Blueprint $table) {
            if (Schema::hasColumn('event_guests', 'scanned_time')) {
                $table->dropColumn('scanned_time');
            }
        });
    }
}