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
            if (! Schema::hasColumn('event_cards', 'cardTypePositionX')) {
                $table->decimal('cardTypePositionX', 8, 2)->nullable()->after('guestPositionY');
            }

            if (! Schema::hasColumn('event_cards', 'cardTypePositionY')) {
                $table->decimal('cardTypePositionY', 8, 2)->nullable()->after('cardTypePositionX');
            }

            if (! Schema::hasColumn('event_cards', 'qrCodePositionX')) {
                $table->decimal('qrCodePositionX', 8, 2)->nullable()->after('cardTypePositionY');
            }

            if (! Schema::hasColumn('event_cards', 'qrCodePositionY')) {
                $table->decimal('qrCodePositionY', 8, 2)->nullable()->after('qrCodePositionX');
            }

            /*
            |--------------------------------------------------------------------------
            | Permanent percentage-based positioning fields
            |--------------------------------------------------------------------------
            | These fields make preview and downloaded card match exactly,
            | even when the preview is scaled on screen.
            */

            if (! Schema::hasColumn('event_cards', 'guestPositionXPercent')) {
                $table->decimal('guestPositionXPercent', 8, 4)->nullable()->after('qrCodePositionY');
            }

            if (! Schema::hasColumn('event_cards', 'guestPositionYPercent')) {
                $table->decimal('guestPositionYPercent', 8, 4)->nullable()->after('guestPositionXPercent');
            }

            if (! Schema::hasColumn('event_cards', 'guestFontSizePercent')) {
                $table->decimal('guestFontSizePercent', 8, 4)->nullable()->after('guestPositionYPercent');
            }

            if (! Schema::hasColumn('event_cards', 'cardTypePositionXPercent')) {
                $table->decimal('cardTypePositionXPercent', 8, 4)->nullable()->after('guestFontSizePercent');
            }

            if (! Schema::hasColumn('event_cards', 'cardTypePositionYPercent')) {
                $table->decimal('cardTypePositionYPercent', 8, 4)->nullable()->after('cardTypePositionXPercent');
            }

            if (! Schema::hasColumn('event_cards', 'cardTypeWidthPercent')) {
                $table->decimal('cardTypeWidthPercent', 8, 4)->nullable()->after('cardTypePositionYPercent');
            }

            if (! Schema::hasColumn('event_cards', 'cardTypeHeightPercent')) {
                $table->decimal('cardTypeHeightPercent', 8, 4)->nullable()->after('cardTypeWidthPercent');
            }

            if (! Schema::hasColumn('event_cards', 'cardTypeFontSizePercent')) {
                $table->decimal('cardTypeFontSizePercent', 8, 4)->nullable()->after('cardTypeHeightPercent');
            }

            if (! Schema::hasColumn('event_cards', 'qrCodePositionXPercent')) {
                $table->decimal('qrCodePositionXPercent', 8, 4)->nullable()->after('cardTypeFontSizePercent');
            }

            if (! Schema::hasColumn('event_cards', 'qrCodePositionYPercent')) {
                $table->decimal('qrCodePositionYPercent', 8, 4)->nullable()->after('qrCodePositionXPercent');
            }

            if (! Schema::hasColumn('event_cards', 'qrCodeSizePercent')) {
                $table->decimal('qrCodeSizePercent', 8, 4)->nullable()->after('qrCodePositionYPercent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_cards', function (Blueprint $table) {
            $columns = [
                'cardTypePositionX',
                'cardTypePositionY',
                'qrCodePositionX',
                'qrCodePositionY',

                'guestPositionXPercent',
                'guestPositionYPercent',
                'guestFontSizePercent',

                'cardTypePositionXPercent',
                'cardTypePositionYPercent',
                'cardTypeWidthPercent',
                'cardTypeHeightPercent',
                'cardTypeFontSizePercent',

                'qrCodePositionXPercent',
                'qrCodePositionYPercent',
                'qrCodeSizePercent',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('event_cards', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};