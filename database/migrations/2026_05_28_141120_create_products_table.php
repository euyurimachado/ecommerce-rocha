<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->string('image_path')->nullable();
            $table->string('weight')->nullable();
            $table->string('flavor')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->json('benefits')->nullable();
            $table->text('usage_instructions')->nullable();
            $table->text('ingredients')->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('compare_at_price_cents')->nullable();
            $table->decimal('rating', 2, 1)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('sales_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_offer')->default(false);
            $table->boolean('allows_pickup')->default(true);
            $table->boolean('allows_local_delivery')->default(true);
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_featured']);
            $table->index(['is_active', 'is_offer']);
            $table->index(['category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
