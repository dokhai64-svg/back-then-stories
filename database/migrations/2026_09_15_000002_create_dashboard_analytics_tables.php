<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('article_daily_views')) {
            Schema::create('article_daily_views', function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('article_id')
                    ->constrained('articles')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('site_id')
                    ->constrained('sites')
                    ->cascadeOnDelete();

                $table->date('view_date');
                $table->unsignedBigInteger('views')->default(0);
                $table->timestamps();

                $table->unique(
                    ['article_id', 'view_date'],
                    'article_daily_views_article_date_unique'
                );

                $table->index(
                    ['site_id', 'view_date'],
                    'article_daily_views_site_date_index'
                );
            });
        }

        if (!Schema::hasTable('ad_slot_audits')) {
            Schema::create('ad_slot_audits', function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('ad_slot_id')
                    ->nullable()
                    ->constrained('ad_slots')
                    ->nullOnDelete();

                $table
                    ->foreignId('site_id')
                    ->constrained('sites')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('action', 40)->default('updated');
                $table->json('changed_fields')->nullable();
                $table->timestamps();

                $table->index(
                    ['site_id', 'created_at'],
                    'ad_slot_audits_site_created_index'
                );

                $table->index(
                    ['ad_slot_id', 'created_at'],
                    'ad_slot_audits_slot_created_index'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_slot_audits');
        Schema::dropIfExists('article_daily_views');
    }
};
