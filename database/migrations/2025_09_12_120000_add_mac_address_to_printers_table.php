<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('printers', function (Blueprint $table) {
            $table->string('mac_address')->nullable()->unique()->after('password');
            $table->index('mac_address');
        });
    }

    public function down(): void
    {
        Schema::table('printers', function (Blueprint $table) {
            if (Schema::hasColumn('printers', 'mac_address')) {
                $table->dropIndex(['mac_address']);
                $table->dropUnique(['printers_mac_address_unique']);
                $table->dropColumn('mac_address');
            }
        });
    }
};


