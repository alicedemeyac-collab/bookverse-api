<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_genre', function (Blueprint $table) {
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained()->restrictOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->primary(['book_id', 'genre_id']);
            $table->index('genre_id');
        });

        // Au plus un genre principal par livre (index unique partiel PostgreSQL)
        DB::statement('CREATE UNIQUE INDEX book_genre_one_primary ON book_genre (book_id) WHERE is_primary');
    }

    public function down(): void
    {
        Schema::dropIfExists('book_genre');
    }
};
