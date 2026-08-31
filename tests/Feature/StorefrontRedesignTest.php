<?php

namespace Tests\Feature;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_only_active_current_hero_banners_with_images(): void
    {
        $visibleBanner = Banner::create([
            'title' => 'Banner visível',
            'image_path' => 'banners/home-visible.webp',
            'placement' => 'home_hero',
            'device' => 'all',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Banner::create([
            'title' => 'Banner inativo',
            'image_path' => 'banners/home-inactive.webp',
            'placement' => 'home_hero',
            'device' => 'all',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(asset('storage/'.$visibleBanner->image_path), false)
            ->assertDontSee('home-inactive.webp', false);
    }

    public function test_search_stays_on_search_page_and_mobile_header_is_shared(): void
    {
        $this->get(route('search'))
            ->assertOk()
            ->assertSee('type="search"', false)
            ->assertSee('placeholder="O que você está procurando?"', false)
            ->assertDontSee('id="site-search"', false)
            ->assertSee('h-[70px] items-center justify-between bg-white px-4 md:hidden', false)
            ->assertSee('fa-cart-shopping size-7', false);
    }
}
