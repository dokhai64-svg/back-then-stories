<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('articles', 'views')) {
            Schema::table('articles', function (Blueprint $table) {
                $table
                    ->unsignedBigInteger('views')
                    ->default(0)
                    ->index();
            });
        }

        if (!Schema::hasColumn('articles', 'deleted_at')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('articles', 'views')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn('views');
            });
        }

        if (Schema::hasColumn('articles', 'deleted_at')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
