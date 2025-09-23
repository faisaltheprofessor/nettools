<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_actions', function (Blueprint $table) {
            $table->id();
            $table->string('service');
            $table->string('action');
            $table->string('target')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_username')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['service', 'action']);
            $table->index(['service', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_actions');
    }
};
