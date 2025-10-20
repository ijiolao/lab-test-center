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
        Schema::create('lab_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // quest, labcorp, etc.
            $table->enum('connection_type', ['api', 'hl7', 'manual']);
            $table->string('api_endpoint')->nullable();
            $table->text('api_key')->nullable(); // Encrypted
            $table->text('api_secret')->nullable(); // Encrypted
            $table->enum('auth_type', ['api_key', 'oauth', 'basic'])->nullable();
            $table->json('credentials')->nullable(); // Additional auth data
            $table->json('supported_tests')->nullable(); // Array of test codes
            $table->json('field_mapping')->nullable(); // Map our fields to their API
            $table->integer('priority')->default(0); // Higher priority used first
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_partners');
    }
};
