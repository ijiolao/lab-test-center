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
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // Internal code
            $table->string('loinc_code')->nullable(); // Standard LOINC code
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->string('specimen_type'); // blood, urine, saliva, etc.
            $table->integer('turnaround_days')->default(3);
            $table->boolean('fasting_required')->default(false);
            $table->text('preparation_instructions')->nullable();
            $table->json('normal_ranges')->nullable(); // Reference ranges by age/gender
            $table->string('category')->nullable(); // Chemistry, Hematology, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
