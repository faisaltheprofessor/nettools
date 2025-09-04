<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('firewall_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->json('sources')->nullable();
            $table->json('destinations')->nullable();
            $table->json('ports')->nullable();   // store ["443/tcp","53/udp"] etc.
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('firewall_templates'); }
};
