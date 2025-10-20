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
        Schema::create('lab_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('lab_partner_id')->constrained()->onDelete('restrict');
            $table->string('lab_order_id')->nullable(); // External lab's order ID
            $table->enum('status', ['pending', 'submitted', 'acknowledged', 'processing', 'completed', 'failed']);
            $table->json('request_payload')->nullable(); // What we sent
            $table->json('response_payload')->nullable(); // What they returned
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_submissions');
    }
};
