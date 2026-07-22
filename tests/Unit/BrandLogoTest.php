<?php

namespace Tests\Unit;

use App\Models\Brand;
use Tests\TestCase;

class BrandLogoTest extends TestCase
{
    public function test_custom_logo_takes_precedence_over_packaged_logo(): void
    {
        $brand = new Brand([
            'name' => 'DUX',
            'slug' => 'dux',
            'logo_path' => 'brands/custom/dux-personalizada.webp',
        ]);

        $this->assertSame(
            asset('storage/brands/custom/dux-personalizada.webp'),
            $brand->logo_url,
        );
    }

    public function test_packaged_logo_takes_precedence_over_legacy_seed_path(): void
    {
        $brand = new Brand([
            'name' => 'DUX',
            'slug' => 'dux',
            'logo_path' => 'brands/dux-antiga.png',
        ]);

        $this->assertSame(asset('images/brands/dux.svg'), $brand->logo_url);
    }
}
