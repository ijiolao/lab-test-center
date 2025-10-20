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
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('lab_submission_id')->nullable()->constrained()->onDelete('set null');
            $table->json('raw_data'); // Original HL7/API response
            $table->json('parsed_data'); // Normalized result data
            $table->string('pdf_path')->nullable(); // Storage path
            $table->timestamp('result_date');
            $table->boolean('has_critical_values')->default(false);
            $table->boolean('is_reviewed')->default(false);
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('patient_notified_at')->nullable();
            $table->timestamp('patient_viewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
