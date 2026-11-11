<?php

namespace Tests\Feature;

use App\Filament\Resources\MaintenanceActivityCategories\MaintenanceActivityCategoryResource;
use App\Filament\Resources\MaintenanceActivityCategories\Pages\ListMaintenanceActivityCategories;
use App\Models\MaintenanceActivityCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

class MaintenanceActivityCategoryTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_category_has_active_default_and_optional_description(): void
    {
        $category = MaintenanceActivityCategory::factory()->create([
            'name' => '  Mantenimiento preventivo  ',
            'description' => '  Conservación programada  ',
        ])->refresh();

        $this->assertSame('Mantenimiento preventivo', $category->name);
        $this->assertSame('Conservación programada', $category->description);
        $this->assertTrue($category->active);
        $this->assertSame(0, $category->sort_order);

        $withoutDescription = MaintenanceActivityCategory::factory()->create(['description' => null]);
        $this->assertNull($withoutDescription->description);
    }

    public function test_category_validates_name_and_non_negative_sort_order(): void
    {
        $category = MaintenanceActivityCategory::factory()->make(['name' => '', 'sort_order' => -1]);

        try {
            $category->save();
            $this->fail('Invalid category was saved.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('name', $exception->errors());
            $this->assertArrayHasKey('sort_order', $exception->errors());
        }

        $this->assertDatabaseCount('maintenance_activity_categories', 0);
    }

    public function test_name_is_limited_to_one_hundred_characters(): void
    {
        $category = MaintenanceActivityCategory::factory()->make(['name' => str_repeat('x', 101)]);

        $this->expectException(ValidationException::class);
        $category->save();
    }

    public function test_database_rejects_duplicate_category_names(): void
    {
        MaintenanceActivityCategory::factory()->create(['name' => 'Preventivo']);

        $this->expectException(QueryException::class);
        DB::table('maintenance_activity_categories')->insert([
            'name' => 'Preventivo',
            'active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_categories_can_be_activated_deactivated_and_ordered(): void
    {
        $first = MaintenanceActivityCategory::factory()->create(['name' => 'Correctivo', 'sort_order' => 20]);
        $second = MaintenanceActivityCategory::factory()->create(['name' => 'Preventivo', 'sort_order' => 10]);

        $first->update(['active' => false]);
        $first->refresh();

        $this->assertFalse($first->active);
        $this->assertSame([$second->id, $first->id], MaintenanceActivityCategory::query()->orderBy('sort_order')->pluck('id')->all());
        $this->assertSame([$second->id], MaintenanceActivityCategory::query()->where('active', true)->orderBy('sort_order')->pluck('id')->all());
    }

    public function test_category_update_preserves_identity_and_deletion_is_forbidden(): void
    {
        $category = MaintenanceActivityCategory::factory()->create();
        $id = $category->id;
        $category->update(['name' => 'Nueva categoría', 'description' => 'Descripción actualizada']);

        $this->assertSame($id, $category->id);
        $this->assertSame('Nueva categoría', $category->fresh()->name);

        try {
            $category->delete();
            $this->fail('Category deletion was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('category', $exception->errors());
        }

        $this->assertModelExists($category);
    }

    public function test_only_administrator_and_jefe_can_manage_global_categories(): void
    {
        $category = MaintenanceActivityCategory::factory()->create();
        $admin = User::factory()->create()->assignRole('Administrador');
        $jefe = User::factory()->create()->assignRole('Jefe de supervisores');
        $supervisor = User::factory()->create()->assignRole('Supervisor');

        foreach ([$admin, $jefe] as $manager) {
            $this->assertTrue(Gate::forUser($manager)->allows('viewAny', MaintenanceActivityCategory::class));
            $this->assertTrue(Gate::forUser($manager)->allows('create', MaintenanceActivityCategory::class));
            $this->assertTrue(Gate::forUser($manager)->allows('update', $category));
        }

        $this->assertTrue(Gate::forUser($supervisor)->allows('viewAny', MaintenanceActivityCategory::class));
        $this->assertTrue(Gate::forUser($supervisor)->allows('view', $category));
        $this->assertFalse(Gate::forUser($supervisor)->allows('create', MaintenanceActivityCategory::class));
        $this->assertFalse(Gate::forUser($supervisor)->allows('update', $category));
        $this->assertFalse(Gate::forUser($supervisor)->allows('delete', $category));

        $this->assertTrue($admin->can('maintenance.activity.update'));
        $supervisor->givePermissionTo('maintenance.activity.update');
        $this->assertFalse(Gate::forUser($supervisor)->allows('update', $category));
    }

    public function test_supervisor_resource_query_exposes_only_active_categories_and_resource_forbids_deletion(): void
    {
        $supervisor = User::factory()->create()->assignRole('Supervisor');
        $active = MaintenanceActivityCategory::factory()->create(['active' => true]);
        $inactive = MaintenanceActivityCategory::factory()->create(['active' => false]);

        $this->actingAs($supervisor, 'web');

        $this->assertSame([$active->id], MaintenanceActivityCategoryResource::getEloquentQuery()->pluck('id')->all());
        $this->assertFalse(MaintenanceActivityCategoryResource::canDeleteAny());
        $this->assertFalse(MaintenanceActivityCategoryResource::canCreate());
        $this->assertNotContains($inactive->id, MaintenanceActivityCategoryResource::getEloquentQuery()->pluck('id')->all());

        $this->get(MaintenanceActivityCategoryResource::getUrl('create'))->assertForbidden();
        $this->get(MaintenanceActivityCategoryResource::getUrl('edit', ['record' => $active]))->assertForbidden();
    }

    public function test_manager_can_create_and_update_category_from_resource(): void
    {
        $manager = User::factory()->create()->assignRole('Jefe de supervisores');
        $this->actingAs($manager, 'web');

        Livewire::test(ListMaintenanceActivityCategories::class)
            ->callAction('create', [
                'name' => 'Categoría operativa',
                'description' => 'Descripción',
                'active' => true,
                'sort_order' => 5,
            ])
            ->assertHasNoFormErrors();

        $category = MaintenanceActivityCategory::query()->where('name', 'Categoría operativa')->firstOrFail();
        $this->assertSame(5, $category->sort_order);

        Livewire::test(ListMaintenanceActivityCategories::class)
            ->callAction(TestAction::make('edit')->table($category), ['name' => 'Categoría actualizada'])
            ->assertHasNoFormErrors();

        $this->assertSame('Categoría actualizada', $category->fresh()->name);
    }
}
