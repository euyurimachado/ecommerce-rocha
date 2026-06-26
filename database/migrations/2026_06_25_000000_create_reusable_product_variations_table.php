<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('normalized_name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_variation_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variation_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('normalized_value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_variation_id', 'normalized_value'], 'variation_option_unique');
        });

        $this->seedFromProducts();
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variation_options');
        Schema::dropIfExists('product_variations');
    }

    private function seedFromProducts(): void
    {
        DB::table('products')
            ->select(['variations'])
            ->whereNotNull('variations')
            ->orderBy('id')
            ->each(function (object $product): void {
                $variations = json_decode($product->variations, true);

                if (! is_array($variations)) {
                    return;
                }

                foreach ($variations as $variation) {
                    if (! is_array($variation)) {
                        continue;
                    }

                    $name = trim((string) ($variation['name'] ?? ''));

                    if ($name === '') {
                        continue;
                    }

                    $variationId = $this->findOrCreateVariation($name);
                    $options = $variation['options'] ?? $variation['values'] ?? [];

                    if (! is_array($options)) {
                        continue;
                    }

                    foreach ($options as $option) {
                        $value = is_array($option)
                            ? trim((string) ($option['value'] ?? $option['name'] ?? ''))
                            : trim((string) $option);

                        if ($value === '') {
                            continue;
                        }

                        $this->findOrCreateOption($variationId, $value);
                    }
                }
            });
    }

    private function findOrCreateVariation(string $name): int
    {
        $normalizedName = $this->normalize($name);
        $existingId = DB::table('product_variations')
            ->where('normalized_name', $normalizedName)
            ->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        return (int) DB::table('product_variations')->insertGetId([
            'name' => $name,
            'normalized_name' => $normalizedName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function findOrCreateOption(int $variationId, string $value): void
    {
        $normalizedValue = $this->normalize($value);
        $exists = DB::table('product_variation_options')
            ->where('product_variation_id', $variationId)
            ->where('normalized_value', $normalizedValue)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('product_variation_options')->insert([
            'product_variation_id' => $variationId,
            'value' => $value,
            'normalized_value' => $normalizedValue,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->squish()->lower()->ascii()->toString();
    }
};
