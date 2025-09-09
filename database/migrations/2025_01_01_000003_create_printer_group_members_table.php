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
        Schema::create('printer_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('printer_group_id')->constrained()->onDelete('cascade');
            $table->foreignId('printer_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['printer_group_id', 'printer_id']);
            $table->index('printer_group_id');
            $table->index('printer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('printer_group_members');
    }
};
