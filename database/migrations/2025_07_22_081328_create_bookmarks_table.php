<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();

            // Ownership / visibility
            $table->foreignId('user_guid')
                ->nullable()
                ->constrained('users', 'guid')
                ->cascadeOnDelete();               // deleting a user removes their bookmarks
            $table->boolean('is_global')->default(false); // admins can mark a bookmark as global

            // Core
            $table->string('name');
            $table->enum('type', ['link', 'folder']);
            $table->string('url')->nullable();            // for links

            // Icon options
            $table->string('icon')->nullable();           // uploaded PNG path
            $table->string('icon_name')->nullable();      // Heroicon name (e.g. "globe-alt")
            $table->string('favicon')->nullable();        // derived site favicon URL

            // Tree
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('bookmarks')
                ->cascadeOnDelete();

            $table->timestamps();

            // Indexes
            $table->index(['user_guid', 'is_global', 'parent_id']);
            $table->index('type');
            $table->index('name');

            // Optional DB rule: links must not be at root
            // Comment out if your DB doesn't support CHECK
            // $table->check("(type = 'folder') OR (type = 'link' AND parent_id IS NOT NULL)");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
