<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('products')
            ->select(['id', 'flavor', 'variations'])
            ->orderBy('id')
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    $variations = $this->normalizeVariations($product->variations ? json_decode($product->variations, true) : []);
                    $flavor = trim((string) $product->flavor);

                    if ($flavor !== '' && ! $this->hasVariation($variations, 'Sabor')) {
                        $variations[] = [
                            'name' => 'Sabor',
                            'options' => [
                                [
                                    'value' => $flavor,
                                    'image_path' => null,
                                ],
                            ],
                        ];
                    }

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([
                            'variations' => $variations === [] ? null : json_encode($variations),
                        ]);
                }
            });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('flavor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('flavor')->nullable()->after('weight');
        });

        DB::table('products')
            ->select(['id', 'variations'])
            ->orderBy('id')
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    $variations = $product->variations ? json_decode($product->variations, true) : [];
                    $flavorVariation = collect($this->normalizeVariations($variations))
                        ->first(fn (array $variation): bool => strcasecmp($variation['name'], 'Sabor') === 0);

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['flavor' => $flavorVariation['options'][0]['value'] ?? null]);
                }
            });
    }

    private function normalizeVariations(?array $variations): array
    {
        return collect($variations ?? [])
            ->map(function (array $variation): ?array {
                $name = trim((string) ($variation['name'] ?? ''));
                $rawOptions = $variation['options'] ?? $variation['values'] ?? [];
                $options = collect($rawOptions)
                    ->map(function ($option): ?array {
                        if (is_array($option)) {
                            $value = trim((string) ($option['value'] ?? $option['name'] ?? ''));
                            $imagePath = trim((string) ($option['image_path'] ?? '')) ?: null;
                        } else {
                            $value = trim((string) $option);
                            $imagePath = null;
                        }

                        if ($value === '') {
                            return null;
                        }

                        return [
                            'value' => $value,
                            'image_path' => $imagePath,
                        ];
                    })
                    ->filter()
                    ->unique('value')
                    ->values()
                    ->all();

                if ($name === '' || $options === []) {
                    return null;
                }

                return [
                    'name' => $name,
                    'options' => $options,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function hasVariation(array $variations, string $name): bool
    {
        return collect($variations)
            ->contains(fn (array $variation): bool => strcasecmp($variation['name'], $name) === 0);
    }
};
