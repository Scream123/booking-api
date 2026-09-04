<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_the_cheapest_valid_offer_per_property(): void
    {
        $supplierA = Supplier::factory()->create(['code' => 'supplier-a']);
        $supplierB = Supplier::factory()->create(['code' => 'supplier-b']);
        $property = Property::factory()->create(['city' => 'Barcelona']);

        $cheap = Offer::factory()->for($property)->for($supplierA, 'supplier')->create([
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'max_guests' => 4,
            'price' => 50000,
            'available_units' => 2,
            'expires_at' => now()->addDays(10),
        ]);

        Offer::factory()->for($property)->for($supplierB, 'supplier')->create([
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'max_guests' => 4,
            'price' => 90000,
            'available_units' => 2,
            'expires_at' => now()->addDays(10),
        ]);

        Offer::factory()->for($property)->for($supplierA, 'supplier')->create([
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'max_guests' => 4,
            'price' => 10000,
            'available_units' => 0,
            'expires_at' => now()->addDays(10),
        ]);

        $response = $this->getJson(
            '/api/properties?city=Barcelona&check_in=2026-10-10&check_out=2026-10-15&guests=2',
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.best_offer.id', $cheap->id);
        $response->assertJsonPath('data.0.best_offer.price', 50000);
        $response->assertJsonPath('meta.per_page', 15);
    }


    public function test_it_excludes_properties_with_no_matching_dates(): void
    {
        $property = Property::factory()->create(['city' => 'Barcelona']);
        Offer::factory()->for($property)->create([
            'check_in' => '2026-11-01',
            'check_out' => '2026-11-05',
            'max_guests' => 4,
            'available_units' => 2,
            'expires_at' => now()->addDays(10),
        ]);

        $response = $this->getJson(
            '/api/properties?city=Barcelona&check_in=2026-10-10&check_out=2026-10-15&guests=2',
        );

        $response->assertOk()->assertJsonCount(0, 'data');
    }
}