<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedInteger('stock_quantity')->default(0)->after('image_source_url');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->json('variant_selections')->nullable()->after('variant_summary');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn('variant_selections');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('stock_quantity');
        });
    }
};
