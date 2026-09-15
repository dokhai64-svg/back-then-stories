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
                'content_mode'
            )
        ) {
            Schema::table(
                'articles',
                function (Blueprint $table) {
                    $table
                        ->string(
                            'content_mode',
                            20
                        )
                        ->default('normal');
                }
            );
        }

        if (
            !Schema::hasTable(
                'article_aliases'
            )
        ) {
            Schema::create(
                'article_aliases',
                function (Blueprint $table) {
                    $table->id();

                    $table
                        ->foreignId('article_id')
                        ->constrained('articles')
                        ->cascadeOnDelete();

                    $table
                        ->foreignId('site_id')
                        ->constrained('sites')
                        ->cascadeOnDelete();

                    $table->string('slug', 255);
                    $table->timestamps();

                    $table->unique(
                        [
                            'site_id',
                            'slug',
                        ],
                        'article_aliases_site_slug_unique'
                    );

                    $table->index('article_id');
                }
            );
        }

        if (
            !Schema::hasTable(
                'article_chapters'
            )
        ) {
            Schema::create(
                'article_chapters',
                function (Blueprint $table) {
                    $table->id();

                    $table
                        ->foreignId('article_id')
                        ->constrained('articles')
                        ->cascadeOnDelete();

                    $table
                        ->unsignedInteger(
                            'chapter_number'
                        );

                    $table
                        ->string(
                            'title',
                            255
                        )
                        ->nullable();

                    $table->longText('body');

                    $table
                        ->unsignedBigInteger(
                            'views'
                        )
                        ->default(0);

                    $table->timestamps();

                    $table->unique(
                        [
                            'article_id',
                            'chapter_number',
                        ],
                        'article_chapters_article_number_unique'
                    );

                    $table->index(
                        [
                            'article_id',
                            'chapter_number',
                        ]
                    );
                }
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'article_chapters'
        );

        Schema::dropIfExists(
            'article_aliases'
        );

        if (
            Schema::hasTable('articles') &&
            Schema::hasColumn(
                'articles',
                'content_mode'
            )
        ) {
            Schema::table(
                'articles',
                function (Blueprint $table) {
                    $table->dropColumn(
                        'content_mode'
                    );
                }
            );
        }
    }
};
