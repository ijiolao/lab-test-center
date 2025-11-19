<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_partners', function (Blueprint $table) {
            $table->string('adapter')->nullable()->after('code');
        });

        DB::table('lab_partners')->update([
            'adapter' => DB::raw('code')
        ]);
    }

    public function down(): void
    {
        Schema::table('lab_partners', function (Blueprint $table) {
            $table->dropColumn('adapter');
        });
    }
};
