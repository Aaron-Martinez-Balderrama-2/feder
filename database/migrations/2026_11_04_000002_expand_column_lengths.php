<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Safe ALTER TABLE commands compatible with PostgreSQL
        DB::statement('ALTER TABLE users ALTER COLUMN avatar TYPE varchar(255)');
        DB::statement('ALTER TABLE users ALTER COLUMN profile_picture TYPE varchar(255)');
        DB::statement('ALTER TABLE users ALTER COLUMN id_document TYPE varchar(100)');
        DB::statement('ALTER TABLE users ALTER COLUMN phone TYPE varchar(100)');
        DB::statement('ALTER TABLE users ALTER COLUMN gender TYPE varchar(50)');

        DB::statement('ALTER TABLE courses ALTER COLUMN meet_url TYPE text');
        DB::statement('ALTER TABLE courses ALTER COLUMN whatsapp_url TYPE text');
        DB::statement('ALTER TABLE courses ALTER COLUMN cover_image TYPE varchar(255)');

        DB::statement('ALTER TABLE course_materials ALTER COLUMN file_path TYPE text');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
