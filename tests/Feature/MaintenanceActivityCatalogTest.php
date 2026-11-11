<?php

namespace Tests\Feature;

use App\Filament\Resources\MaintenanceActivityConcepts\MaintenanceActivityConceptResource;
use App\Filament\Resources\MaintenanceActivitySuboptions\MaintenanceActivitySuboptionResource;
use App\Filament\Resources\MaintenanceUnits\MaintenanceUnitResource;
use App\Models\MaintenanceActivityCategory;
use App\Models\MaintenanceActivityConcept;
use App\Models\MaintenanceActivitySuboption;
use App\Models\MaintenanceUnit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\DatabaseTestCase;

class MaintenanceActivityCatalogTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_concept_belongs_to_category_and_allows_same_name_in_other_categories(): void
    {
        $first = MaintenanceActivityCategory::factory()->create();
        $second = MaintenanceActivityCategory::factory()->create();
        $concept = MaintenanceActivityConcept::factory()->for($first, 'category')->create(['name' => 'Limpieza']);
        $other = MaintenanceActivityConcept::factory()->for($second, 'category')->create(['name' => 'Limpieza']);

        $this->assertTrue($concept->category->is($first));
        $this->assertTrue($first->concepts()->whereKey($concept)->exists());
        $this->assertSame($concept->name, $other->name);
    }

    public function test_concept_normalizes_and_validates_data(): void
    {
        $category = MaintenanceActivityCategory::factory()->create();
        $concept = MaintenanceActivityConcept::factory()->for($category, 'category')->create([
            'name' => '  Inspección  ', 'description' => '  Alcance  ', 'sort_order' => 4,
        ])->refresh();

        $this->assertSame('Inspección', $concept->name);
        $this->assertSame('Alcance', $concept->description);
        $this->assertSame(4, $concept->sort_order);

        $invalid = MaintenanceActivityConcept::factory()->make(['maintenance_activity_category_id' => null, 'name' => '']);
        $this->expectException(ValidationException::class);
        $invalid->save();
    }

    public function test_concept_duplicate_name_is_rejected_only_within_category(): void
    {
        $category = MaintenanceActivityCategory::factory()->create();
        MaintenanceActivityConcept::factory()->for($category, 'category')->create(['name' => 'Revisión']);

        $this->expectException(QueryException::class);
        DB::table('maintenance_activity_concepts')->insert([
            'maintenance_activity_category_id' => $category->id,
            'name' => 'Revisión', 'active' => true, 'sort_order' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_suboptions_are_optional_and_unique_within_concept(): void
    {
        $first = MaintenanceActivityConcept::factory()->create(['name' => 'Limpieza']);
        $second = MaintenanceActivityConcept::factory()->create(['name' => 'Limpieza']);
        $suboption = MaintenanceActivitySuboption::factory()->for($first, 'concept')->create(['name' => 'Manual']);
        $sameName = MaintenanceActivitySuboption::factory()->for($second, 'concept')->create(['name' => 'Manual']);

        $this->assertTrue($suboption->concept->is($first));
        $this->assertTrue($first->suboptions()->whereKey($suboption)->exists());
        $this->assertSame('Manual', $sameName->name);
        $this->assertSame(1, $second->suboptions()->count());
    }

    public function test_suboption_relationship_and_validation_are_enforced(): void
    {
        $suboption = MaintenanceActivitySuboption::factory()->create(['name' => '  Con herramienta  '])->refresh();
        $this->assertSame('Con herramienta', $suboption->name);

        $invalid = MaintenanceActivitySuboption::factory()->make(['maintenance_activity_concept_id' => null]);
        $this->expectException(ValidationException::class);
        $invalid->save();
    }

    public function test_units_require_unique_name_and_symbol_and_are_independent(): void
    {
        $unit = MaintenanceUnit::factory()->create(['name' => 'Pieza', 'symbol' => 'pza'])->refresh();
        $this->assertSame('Pieza', $unit->name);
        $this->assertSame('pza', $unit->symbol);

        $this->expectException(QueryException::class);
        DB::table('maintenance_units')->insert([
            'name' => 'Otra unidad', 'symbol' => 'pza', 'active' => true, 'sort_order' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_inactive_catalog_records_are_preserved_and_operational_scopes_are_active_ordered(): void
    {
        $category = MaintenanceActivityCategory::factory()->create();
        $first = MaintenanceActivityConcept::factory()->for($category, 'category')->create(['name' => 'B', 'sort_order' => 20]);
        $second = MaintenanceActivityConcept::factory()->for($category, 'category')->create(['name' => 'A', 'sort_order' => 10]);
        $inactive = MaintenanceActivityConcept::factory()->for($category, 'category')->create(['active' => false, 'name' => 'C', 'sort_order' => 1]);

        $this->assertSame([$second->id, $first->id], MaintenanceActivityConcept::activeOrdered()->pluck('id')->all());
        $this->assertModelExists($inactive);

        $unit = MaintenanceUnit::factory()->create(['active' => false]);
        $this->assertSame([], MaintenanceUnit::activeOrdered()->pluck('id')->all());
        $this->assertModelExists($unit);
    }

    public function test_all_catalog_models_reject_physical_deletion(): void
    {
        foreach ([MaintenanceActivityConcept::factory()->create(), MaintenanceActivitySuboption::factory()->create(), MaintenanceUnit::factory()->create()] as $record) {
            try {
                $record->delete();
                $this->fail('Historical catalog record was deleted.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
            $this->assertModelExists($record);
        }
    }

    public function test_foreign_keys_protect_category_and_concept_history(): void
    {
        $category = MaintenanceActivityCategory::factory()->create();
        $concept = MaintenanceActivityConcept::factory()->for($category, 'category')->create();
        MaintenanceActivitySuboption::factory()->for($concept, 'concept')->create();

        $this->expectException(QueryException::class);
        DB::table('maintenance_activity_categories')->where('id', $category->id)->delete();
    }

    public function test_roles_and_permissions_protect_all_three_catalogs(): void
    {
        $admin = User::factory()->create()->assignRole('Administrador');
        $jefe = User::factory()->create()->assignRole('Jefe de supervisores');
        $supervisor = User::factory()->create()->assignRole('Supervisor');
        $concept = MaintenanceActivityConcept::factory()->create();
        $suboption = MaintenanceActivitySuboption::factory()->for($concept, 'concept')->create();
        $unit = MaintenanceUnit::factory()->create();

        foreach ([$admin, $jefe] as $manager) {
            $this->assertTrue(Gate::forUser($manager)->allows('create', MaintenanceActivityConcept::class));
            $this->assertTrue(Gate::forUser($manager)->allows('update', $concept));
            $this->assertTrue(Gate::forUser($manager)->allows('update', $suboption));
            $this->assertTrue(Gate::forUser($manager)->allows('update', $unit));
        }

        foreach ([$concept, $suboption, $unit] as $record) {
            $this->assertTrue(Gate::forUser($supervisor)->allows('view', $record));
            $this->assertFalse(Gate::forUser($supervisor)->allows('update', $record));
        }

        $supervisor->givePermissionTo('maintenance.activity-concept.update');
        $this->assertFalse(Gate::forUser($supervisor)->allows('update', $concept));
    }

    public function test_supervisor_sees_only_active_catalog_records_in_resources_and_cannot_open_edit_routes(): void
    {
        $supervisor = User::factory()->create()->assignRole('Supervisor');
        $category = MaintenanceActivityCategory::factory()->create();
        $activeConcept = MaintenanceActivityConcept::factory()->for($category, 'category')->create();
        $inactiveConcept = MaintenanceActivityConcept::factory()->for($category, 'category')->create(['active' => false]);
        $activeSuboption = MaintenanceActivitySuboption::factory()->for($activeConcept, 'concept')->create();
        $inactiveSuboption = MaintenanceActivitySuboption::factory()->for($activeConcept, 'concept')->create(['active' => false]);
        $activeUnit = MaintenanceUnit::factory()->create();
        $inactiveUnit = MaintenanceUnit::factory()->create(['active' => false]);
        $this->actingAs($supervisor, 'web');

        $this->assertSame([$activeConcept->id], MaintenanceActivityConceptResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$activeSuboption->id], MaintenanceActivitySuboptionResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$activeUnit->id], MaintenanceUnitResource::getEloquentQuery()->pluck('id')->all());
        $this->get(MaintenanceActivityConceptResource::getUrl('edit', ['record' => $activeConcept]))->assertForbidden();
        $this->get(MaintenanceActivitySuboptionResource::getUrl('edit', ['record' => $activeSuboption]))->assertForbidden();
        $this->get(MaintenanceUnitResource::getUrl('edit', ['record' => $activeUnit]))->assertForbidden();
        $this->assertNotContains($inactiveConcept->id, MaintenanceActivityConceptResource::getEloquentQuery()->pluck('id')->all());
        $this->assertNotContains($inactiveSuboption->id, MaintenanceActivitySuboptionResource::getEloquentQuery()->pluck('id')->all());
        $this->assertNotContains($inactiveUnit->id, MaintenanceUnitResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_manager_can_create_each_catalog_from_filament_resources(): void
    {
        $manager = User::factory()->create()->assignRole('Administrador');
        $this->actingAs($manager, 'web');

        $this->get(MaintenanceActivityConceptResource::getUrl('create'))->assertOk();
        $this->get(MaintenanceActivitySuboptionResource::getUrl('create'))->assertOk();
        $this->get(MaintenanceUnitResource::getUrl('create'))->assertOk();
    }
}
