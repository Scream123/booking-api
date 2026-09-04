<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessImportJob;
use App\Models\Offer;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-2026-09-01-001',
            'sent_at' => '2026-09-01T10:00:00Z',
            'offers' => [
                [
                    'external_id' => 'offer-a-10001',
                    'property' => [
                        'code' => 'BCN-0001',
                        'name' => 'Apartment near Sagrada Familia',
                        'city' => 'Barcelona',
                    ],
                    'check_in' => '2026-10-10',
                    'check_out' => '2026-10-15',
                    'max_guests' => 4,
                    'price' => 72500,
                    'currency' => 'EUR',
                    'available_units' => 2,
                    'expires_at' => '2026-09-10T23:59:59Z',
                ],
            ],
        ], $overrides);
    }

    public function test_resubmitting_the_same_import_does_not_duplicate_or_redispatch(): void
    {
        Queue::fake();
        Supplier::factory()->create(['code' => 'supplier-a']);

        $first = $this->postJson('/api/imports', $this->payload());
        $second = $this->postJson('/api/imports', $this->payload());

        $first->assertStatus(202);
        $second->assertStatus(202);
        $this->assertSame($first->json('data.id'), $second->json('data.id'));

        $this->assertDatabaseCount('imports', 1);
        Queue::assertPushed(ProcessImportJob::class, 1);
    }

    public function test_job_processing_creates_property_and_offer_and_marks_import_completed(): void
    {
        config(['queue.default' => 'sync']);

        Supplier::factory()->create(['code' => 'supplier-a']);

        $response = $this->postJson('/api/imports', $this->payload());

        $importId = $response->json('data.id');

        $responseStatus = $this->getJson("/api/imports/{$importId}");

        $responseStatus->assertJsonPath('data.status', 'completed');
        $responseStatus->assertJsonPath('data.processed_offers', 1);

        $this->assertDatabaseHas('properties', ['code' => 'BCN-0001']);

        $offer = Offer::query()->where('external_id', 'offer-a-10001')->firstOrFail();
        $this->assertSame(72500, $offer->price);
        $this->assertSame(2, $offer->available_units);
    }


    public function test_reimporting_an_existing_offer_updates_it_in_place(): void
    {
        Supplier::factory()->create(['code' => 'supplier-a']);
        $this->postJson('/api/imports', $this->payload());

        $updated = $this->payload(['external_import_id' => 'import-2026-09-02-001']);
        $updated['offers'][0]['price'] = 65000;
        $updated['offers'][0]['available_units'] = 1;
        $this->postJson('/api/imports', $updated);

        $this->assertDatabaseCount('offers', 1);
        $offer = Offer::query()->where('external_id', 'offer-a-10001')->firstOrFail();
        $this->assertSame(65000, $offer->price);
        $this->assertSame(1, $offer->available_units);
    }
}
