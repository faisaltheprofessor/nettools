<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // text columns with sane defaults
            $table->string('bookmark_view')->default('grid');
            $table->string('bookmark_sort')->default('asc');
        });

        // Add CHECK constraints (SQLite-friendly)
        // Note: SQLite supports CHECK but not altering easily; do it only if driver is sqlite.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // SQLite can’t easily add named CHECK after table creation; so we simulate by trusting app layer.
            // Nothing else to do here.
        } else {
            Schema::table('users', function (Blueprint $table) {
                // For MySQL/Postgres you can uncomment if you want strict checks:
                // $table->check("bookmark_view in ('grid','list')");
                // $table->check("bookmark_sort in ('asc','desc')");
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bookmark_view', 'bookmark_sort']);
        });
    }
};

