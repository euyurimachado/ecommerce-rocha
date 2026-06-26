<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('nutrition_facts')->nullable()->after('ingredients');
            $table->string('serving_size')->nullable()->after('nutrition_facts');
            $table->text('allergen_info')->nullable()->after('serving_size');
            $table->string('manufacturer_url')->nullable()->after('allergen_info');
            $table->string('image_source_url')->nullable()->after('manufacturer_url');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'nutrition_facts',
                'serving_size',
                'allergen_info',
                'manufacturer_url',
                'image_source_url',
            ]);
        });
    }
};
