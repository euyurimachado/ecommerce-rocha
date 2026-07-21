<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->select(['id', 'variations'])
            ->whereNotNull('variations')
            ->orderBy('id')
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    $variations = json_decode($product->variations, true);

                    if (! is_array($variations)) {
                        continue;
                    }

                    foreach ($variations as &$variation) {
                        foreach ($variation['options'] ?? [] as &$option) {
                            if (is_array($option)) {
                                unset($option['stock_quantity'], $option['reserved_quantity']);
                            }
                        }
                        unset($option);
                    }
                    unset($variation);

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['variations' => json_encode($variations, JSON_UNESCAPED_UNICODE)]);
                }
            });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['stock_quantity', 'reserved_quantity']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
        });
    }
};
