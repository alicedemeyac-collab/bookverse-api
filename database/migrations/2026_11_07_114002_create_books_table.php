<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('subtitle', 255)->nullable();
            $table->text('synopsis')->nullable();
            $table->char('isbn10', 10)->nullable()->unique();
            $table->char('isbn13', 13)->nullable()->unique();
            $table->date('publication_date')->nullable()->index();
            $table->string('publisher', 150)->nullable();
            $table->unsignedSmallInteger('page_count')->nullable();
            $table->char('language', 2)->nullable()->index();
            $table->string('cover_url', 500)->nullable();
            $table->string('google_books_id', 20)->nullable()->unique();
            $table->string('open_library_id', 20)->nullable()->unique();
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('ratings_count')->default(0);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE books ADD CONSTRAINT books_page_count_check CHECK (page_count > 0)');
        DB::statement('ALTER TABLE books ADD CONSTRAINT books_avg_rating_check CHECK (avg_rating BETWEEN 0 AND 5)');
        DB::statement("ALTER TABLE books ADD CONSTRAINT books_isbn13_check CHECK (isbn13 ~ '^[0-9]{13}$')");
        DB::statement("ALTER TABLE books ADD CONSTRAINT books_isbn10_check CHECK (isbn10 ~ '^[0-9]{9}[0-9X]$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
