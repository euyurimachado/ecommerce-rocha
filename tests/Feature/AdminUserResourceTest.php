<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_users_table(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@rochasports.com.br',
            'role' => User::ROLE_ADMIN,
        ]);

        User::factory()->create([
            'name' => 'Operador Rocha',
            'email' => 'operador@rochasports.com.br',
            'role' => User::ROLE_OPERATOR,
        ]);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Operador Rocha')
            ->assertSee('Operador');
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $this->actingAs($manager)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_inactive_user_cannot_access_admin_panel(): void
    {
        $inactive = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => false,
        ]);

        $this->assertFalse($inactive->canAccessPanel(filament()->getPanel('admin')));
    }

    public function test_admin_can_create_user_with_role(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Gerente Rocha',
                'email' => 'gerente@rochasports.com.br',
                'password' => 'senha-segura-123',
                'role' => User::ROLE_MANAGER,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'gerente@rochasports.com.br')->first();

        $this->assertNotNull($user);
        $this->assertSame(User::ROLE_MANAGER, $user->role);
        $this->assertTrue(Hash::check('senha-segura-123', $user->password));
    }

    public function test_admin_can_edit_user_without_overwriting_password(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
        ]);

        $originalPassword = $operator->password;

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $operator->getKey()])
            ->fillForm([
                'name' => 'Operador Atualizado',
                'email' => $operator->email,
                'password' => null,
                'role' => User::ROLE_MANAGER,
                'is_active' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $operator->refresh();

        $this->assertSame('Operador Atualizado', $operator->name);
        $this->assertSame(User::ROLE_MANAGER, $operator->role);
        $this->assertSame($originalPassword, $operator->password);
    }
}
