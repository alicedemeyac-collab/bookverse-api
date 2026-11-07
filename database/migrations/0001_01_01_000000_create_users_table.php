<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 30)->unique();
            $table->string('email', 255)->unique();
            $table->string('password');
            $table->string('role', 10)->default('USER');
            $table->string('status', 10)->default('ACTIVE');
            $table->string('avatar_url', 255)->nullable();
            $table->string('bio', 500)->nullable();
            $table->date('birth_date');
            $table->boolean('is_library_public')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        // Unicité insensible à la casse : Marie@x.fr et marie@x.fr sont le même compte
        DB::statement('CREATE UNIQUE INDEX users_email_lower_unique ON users (LOWER(email))');
        DB::statement('CREATE UNIQUE INDEX users_username_lower_unique ON users (LOWER(username))');

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('USER','MODERATOR','ADMIN'))");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ('ACTIVE','SUSPENDED'))");
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_username_length CHECK (char_length(username) >= 3)');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
