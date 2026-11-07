<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('genres')->restrictOnDelete();
            $table->string('name', 60)->unique();
            $table->string('slug', 80)->unique();
            $table->text('description')->nullable();
            $table->char('primary_color', 7);
            $table->char('secondary_color', 7);
            $table->char('accent_color', 7);
            $table->string('heading_font', 60);
            $table->string('body_font', 60);
            $table->string('banner_url', 255)->nullable();
            $table->string('mood', 100)->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE genres ADD CONSTRAINT genres_parent_not_self CHECK (parent_id <> id)');
        DB::statement("ALTER TABLE genres ADD CONSTRAINT genres_colors_hex CHECK (
            primary_color ~ '^#[0-9A-Fa-f]{6}$'
            AND secondary_color ~ '^#[0-9A-Fa-f]{6}$'
            AND accent_color ~ '^#[0-9A-Fa-f]{6}$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('genres');
    }
};
