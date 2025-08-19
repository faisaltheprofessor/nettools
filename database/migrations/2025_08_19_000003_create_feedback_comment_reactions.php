<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('feedback_comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comment_id')->index();
            $table->uuid('user_guid')->index();
            $table->string('emoji', 20);
            $table->timestamps();

            $table->foreign('comment_id')
                ->references('id')->on('feedback_comments')
                ->cascadeOnDelete();

            $table->unique(['comment_id','user_guid','emoji']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('feedback_comment_reactions');
    }
};
