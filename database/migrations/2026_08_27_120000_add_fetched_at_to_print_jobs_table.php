<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wann hat der Drucker den Auftrag tatsächlich abgeholt?
 *
 * Bisher gab es nur "erstellt" und "gedruckt". Liegt zwischen beiden eine
 * Minute, verrät das nicht, ob der Drucker spät gefragt oder spät gedruckt
 * hat - und genau daran hing die Fehlersuche. Der Zeitpunkt stand zwar im
 * Aktivitätsprotokoll, dort aber auf Minuten gerundet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_jobs', function (Blueprint $table) {
            $table->timestamp('fetched_at')->nullable()->after('printed_at');
        });
    }

    public function down(): void
    {
        Schema::table('print_jobs', function (Blueprint $table) {
            $table->dropColumn('fetched_at');
        });
    }
};
