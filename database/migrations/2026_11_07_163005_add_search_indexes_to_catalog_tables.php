<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // unaccent() est STABLE : PostgreSQL refuse de l'utiliser dans un index.
        // On l'enveloppe dans une fonction déclarée IMMUTABLE.
        DB::unprepared(<<<'SQL'
        CREATE OR REPLACE FUNCTION f_unaccent(text) RETURNS text
        LANGUAGE sql IMMUTABLE PARALLEL SAFE STRICT
        AS $$ SELECT public.unaccent('public.unaccent', $1) $$
        SQL);

        // Index GIN trigramme : accélère les recherches ILIKE '%mot%'
        DB::statement('CREATE INDEX books_title_trgm_idx ON books USING gin (f_unaccent(title) gin_trgm_ops)');
        DB::statement('CREATE INDEX books_publisher_trgm_idx ON books USING gin (f_unaccent(publisher) gin_trgm_ops)');
        DB::statement('CREATE INDEX authors_name_trgm_idx ON authors USING gin (f_unaccent(name) gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS books_title_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS books_publisher_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS authors_name_trgm_idx');
        DB::statement('DROP FUNCTION IF EXISTS f_unaccent(text)');
    }
};
