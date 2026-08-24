<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ein Druckauftrag darf ohne Benutzer entstehen.
 *
 * Bis hierher war user_id Pflicht, und createJob() holte ihn aus auth(). Das
 * ging so lange gut, wie ein Mensch auf einen Knopf drückte. Löst dagegen ein
 * Vorgang den Druck aus – eine eingehende Zahlung über einen Webhook, ein
 * geplanter Auftrag –, gibt es keinen angemeldeten Benutzer, und das Anlegen
 * brach ab.
 *
 * Der Auftrag gehört weiterhin zu einem Team; nur die Person ist optional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_jobs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('print_jobs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
