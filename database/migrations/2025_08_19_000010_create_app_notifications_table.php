<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_guid')->index();
            $table->string('type', 40)->default('info')->index(); // info|success|warning|error|anliegen
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->string('url', 1024)->nullable(); // where to go
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('app_notifications');
    }
};

