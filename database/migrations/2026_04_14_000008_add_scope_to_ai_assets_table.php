<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ai_assets', 'scope')) {
            Schema::table('ai_assets', function (Blueprint $table) {
                $table->json('scope')->nullable()->after('tags');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ai_assets', 'scope')) {
            Schema::table('ai_assets', function (Blueprint $table) {
                $table->dropColumn('scope');
            });
        }
    }
};
