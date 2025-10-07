<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('domain_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('files_path'); // e.g. 'public/domains/news'
            $table->timestamp('updated_from_fs_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('domain_categories'); }
};
