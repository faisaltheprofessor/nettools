<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // category priority
        Schema::table('domain_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('domain_categories', 'priority')) {
                $table->integer('priority')->nullable()->index();
            }
        });

        // pivot: domain_category_domain
        Schema::create('domain_category_domain', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->foreignId('domain_category_id')->constrained('domain_categories')->cascadeOnDelete();
            $table->unique(['domain_id', 'domain_category_id']);
            $table->timestamps();
        });

        // keep domains.category_id as "primary" category (highest priority among its categories)
        if (!Schema::hasColumn('domains', 'normalized_host')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->string('normalized_host')->index()->after('tld');
            });
        }
        // ensure index on category_id already exists through foreign key
    }

    public function down(): void {
        Schema::dropIfExists('domain_category_domain');
        Schema::table('domain_categories', function (Blueprint $table) {
            if (Schema::hasColumn('domain_categories', 'priority')) {
                $table->dropColumn('priority');
            }
        });
    }
};
