<?php

use App\Models\User;
use App\Modules\MasterData\Actions\CreateProduct;
use App\Modules\MasterData\Actions\CreateUnit;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('a product detail page exposes prev/next ids ordered the same way as the index', function () {
    $unit = app(CreateUnit::class)->execute(['name' => 'Piece']);
    $a = app(CreateProduct::class)->execute(['sku' => 'A1', 'name' => 'Apple', 'unit_id' => $unit->id]);
    $b = app(CreateProduct::class)->execute(['sku' => 'B1', 'name' => 'Banana', 'unit_id' => $unit->id]);
    $c = app(CreateProduct::class)->execute(['sku' => 'C1', 'name' => 'Cherry', 'unit_id' => $unit->id]);

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);

    $this->get("/products/{$b->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('products/Show')
        ->where('prev', $a->id)
        ->where('next', $c->id)
        ->where('product.name', 'Banana')
    );

    $this->get("/products/{$a->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('prev', null)
        ->where('next', $b->id)
    );

    $this->get("/products/{$c->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('prev', $b->id)
        ->where('next', null)
    );
});

test('a role without product view permission is denied the product detail page', function () {
    $unit = app(CreateUnit::class)->execute(['name' => 'Piece']);
    $product = app(CreateProduct::class)->execute(['sku' => 'A1', 'name' => 'Apple', 'unit_id' => $unit->id]);

    $accountant = User::factory()->create();
    $accountant->assignRole('accountant');

    $this->actingAs($accountant)->get("/products/{$product->id}")->assertForbidden();
});
