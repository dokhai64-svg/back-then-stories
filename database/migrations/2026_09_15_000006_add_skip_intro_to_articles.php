<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('articles') &&
            !Schema::hasColumn(
                'articles',
                'skip_intro'
            )
        ) {
            Schema::table(
                'articles',
                function (Blueprint $table) {
                    $table
                        ->boolean('skip_intro')
                        ->default(false);
                }
            );
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('articles') &&
            Schema::hasColumn(
                'articles',
                'skip_intro'
            )
        ) {
            Schema::table(
                'articles',
                function (Blueprint $table) {
                    $table->dropColumn(
                        'skip_intro'
                    );
                }
            );
        }
    }
};
