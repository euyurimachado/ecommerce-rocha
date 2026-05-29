<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCouponResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_coupons_table(): void
    {
        $admin = User::factory()->create([
            'email' => 'gestor@rochasports.com.br',
        ]);

        Coupon::create([
            'code' => 'ROCHA10',
            'name' => 'Primeira compra',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/coupons')
            ->assertOk()
            ->assertSee('ROCHA10')
            ->assertSee('Primeira compra');
    }
}
