<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('bookmark_view', ['grid', 'list'])
                ->default('grid')
                ->after('remember_token');

            $table->enum('bookmark_sort', ['manual', 'name', 'created', 'updated', 'domain'])
                ->default('manual')
                ->after('bookmark_view');

            $table->enum('bookmark_sort_dir', ['asc', 'desc'])
                ->default('asc')
                ->after('bookmark_sort');

            $table->enum('bookmark_search_mode', ['smart', 'exact'])
                ->default('smart')
                ->after('bookmark_sort_dir');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'bookmark_view',
                'bookmark_sort',
                'bookmark_sort_dir',
                'bookmark_search_mode',
            ]);
        });
    }
};
