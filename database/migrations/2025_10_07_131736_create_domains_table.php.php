<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('host')->unique();               // e.g. example.com
            $table->string('tld')->nullable();              // e.g. com
            $table->string('normalized_host')->index();     // lowercase / punycode ready
            $table->foreignId('category_id')
    ->constrained('domain_categories')   // 👈 important
    ->cascadeOnDelete();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('domains'); }
};

