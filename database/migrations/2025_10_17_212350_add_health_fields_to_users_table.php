<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('id');
            $table->string('last_name')->after('first_name');
            $table->date('date_of_birth')->after('email');
            $table->string('phone')->nullable()->after('date_of_birth');
            $table->string('gender')->nullable()->after('phone');
            $table->json('address')->nullable()->after('gender');
            $table->enum('role', ['patient', 'admin', 'technician', 'reviewer'])->default('patient')->after('address');
            $table->boolean('is_active')->default(true)->after('role');
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'last_name', 'date_of_birth', 'phone',
                'gender', 'address', 'role', 'is_active'
            ]);
            $table->dropSoftDeletes();
        });
    }
};