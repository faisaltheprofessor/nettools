<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('feedback_id')->index();
            $table->uuid('user_guid')->index();
            $table->text('body');
            $table->timestamps();

            $table->foreign('feedback_id')->references('id')->on('feedback')->cascadeOnDelete();
            // assumes users.guid exists
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_comments');
    }
};
