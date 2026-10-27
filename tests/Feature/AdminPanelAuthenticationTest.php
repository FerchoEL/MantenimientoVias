<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Filament\Panel;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\DatabaseTestCase;

class AdminPanelAuthenticationTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_guests_can_open_login_but_are_redirected_from_the_panel(): void
    {
        $this->get(route('filament.admin.auth.login'))->assertOk();
        $this->get(route('filament.admin.pages.dashboard'))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    #[DataProvider('authorizedRoles')]
    public function test_authorized_roles_can_log_in(string $role): void
    {
        $user = $this->userWithRole($role);
        config(['app.env' => 'production']);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect(route('filament.admin.pages.dashboard'));

        $this->assertAuthenticatedAs($user, 'web');
    }

    #[DataProvider('authorizedRolesAndEnvironments')]
    public function test_authorized_roles_can_access_the_panel_independently_of_environment(string $role, string $environment): void
    {
        $user = $this->userWithRole($role);
        config(['app.env' => $environment]);

        $this->actingAs($user, 'web')
            ->get(route('filament.admin.pages.dashboard'))
            ->assertOk();
    }

    #[DataProvider('unauthorizedRolesAndEnvironments')]
    public function test_users_without_an_authorized_role_cannot_access_the_panel(?string $role, string $environment): void
    {
        $user = $this->userWithRole($role);
        config(['app.env' => $environment]);

        $this->actingAs($user, 'web')
            ->get(route('filament.admin.pages.dashboard'))
            ->assertForbidden();
    }

    #[DataProvider('unauthorizedRolesAndEnvironments')]
    public function test_valid_credentials_do_not_allow_login_without_an_authorized_role(?string $role, string $environment): void
    {
        $user = $this->userWithRole($role);
        config(['app.env' => $environment]);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertHasFormErrors(['email'])
            ->assertSee(__('filament-panels::auth/pages/login.messages.failed'));

        $this->assertGuest('web');
    }

    public function test_wrong_password_is_rejected_for_an_authorized_user(): void
    {
        $user = $this->userWithRole('Administrador');

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'incorrect-password'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest('web');
    }

    public function test_logout_invalidates_the_session_and_removes_panel_access(): void
    {
        $user = $this->userWithRole('Supervisor');
        $this->actingAs($user, 'web')->withSession(['panel_test_marker' => 'private']);

        $this->post(route('filament.admin.auth.logout'))
            ->assertRedirect(route('filament.admin.auth.login'))
            ->assertSessionMissing('panel_test_marker');

        $this->assertGuest('web');
        $this->get(route('filament.admin.pages.dashboard'))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_removing_the_authorized_role_denies_an_existing_session(): void
    {
        $user = $this->userWithRole('Supervisor');
        $this->actingAs($user, 'web');
        $user->removeRole('Supervisor');

        $this->get(route('filament.admin.pages.dashboard'))->assertForbidden();
    }

    public function test_core_view_permission_alone_does_not_grant_panel_access(): void
    {
        $user = $this->userWithRole('Externo');
        $permission = Permission::findOrCreate('maintenance.core.view', 'web');
        Role::findByName('Externo', 'web')->givePermissionTo($permission);

        $this->actingAs($user, 'web')
            ->get(route('filament.admin.pages.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_role_does_not_grant_access_to_another_panel(): void
    {
        $user = $this->userWithRole('Administrador');

        $this->assertFalse($user->canAccessPanel(Panel::make()->id('other')->authGuard('web')));
    }

    public function test_an_authorized_role_from_another_guard_does_not_grant_panel_access(): void
    {
        $user = User::factory()->create();
        $role = Role::findOrCreate('Administrador', 'api');
        $user->roles()->attach($role);

        $this->actingAs($user, 'web')
            ->get(route('filament.admin.pages.dashboard'))
            ->assertForbidden();
    }

    /** @return array<string, array{string}> */
    public static function authorizedRoles(): array
    {
        return [
            'administrador' => ['Administrador'],
            'jefe' => ['Jefe de supervisores'],
            'supervisor' => ['Supervisor'],
        ];
    }

    /** @return array<string, array{string, string}> */
    public static function authorizedRolesAndEnvironments(): array
    {
        $cases = [];

        foreach (self::authorizedRoles() as $key => [$role]) {
            foreach (['local', 'production'] as $environment) {
                $cases[$key.'-'.$environment] = [$role, $environment];
            }
        }

        return $cases;
    }

    /** @return array<string, array{?string, string}> */
    public static function unauthorizedRolesAndEnvironments(): array
    {
        return [
            'sin-rol-local' => [null, 'local'],
            'sin-rol-production' => [null, 'production'],
            'externo-local' => ['Externo', 'local'],
            'externo-production' => ['Externo', 'production'],
        ];
    }

    private function userWithRole(?string $role): User
    {
        $user = User::factory()->create();

        if ($role !== null) {
            $user->assignRole(Role::findOrCreate($role, 'web'));
        }

        return $user;
    }
}
