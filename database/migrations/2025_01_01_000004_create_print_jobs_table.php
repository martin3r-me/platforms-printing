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
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('printable_type');
            $table->unsignedBigInteger('printable_id');
            $table->string('template')->default('default');
            $table->json('data')->comment('Daten für das Template');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('printer_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('printer_group_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->index(['printable_type', 'printable_id']);
            $table->index(['status', 'created_at']);
            $table->index(['printer_id', 'status']);
            $table->index(['printer_group_id', 'status']);
            $table->index(['team_id', 'status']);
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};
