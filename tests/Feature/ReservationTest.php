<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(string $reference = 'web-order-9f782b1c'): array
    {
        return [
            'client_reference' => $reference,
            'customer_name' => 'John Smith',
            'customer_email' => 'john@example.com',
        ];
    }

    public function test_it_creates_a_reservation_and_decrements_available_units(): void
    {
        $this->withoutExceptionHandling();
        $offer = Offer::factory()->create(['available_units' => 2]);

        $response = $this->postJson("/api/offers/{$offer->id}/reservations", $this->payload());

        $response->assertStatus(201)
            ->assertJsonPath('data.client_reference', 'web-order-9f782b1c');
        $this->assertSame(1, $offer->fresh()->available_units);
    }

    public function test_it_refuses_to_reserve_an_offer_with_no_units_left(): void
    {
        $offer = Offer::factory()->create(['available_units' => 0]);

        $response = $this->postJson("/api/offers/{$offer->id}/reservations", $this->payload());

        $response->assertStatus(422);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_the_last_unit_cannot_be_double_booked(): void
    {
        $offer = Offer::factory()->create(['available_units' => 1]);

        $first = $this->postJson("/api/offers/{$offer->id}/reservations", $this->payload('order-1'));
        $second = $this->postJson("/api/offers/{$offer->id}/reservations", $this->payload('order-2'));

        $first->assertStatus(201);
        $second->assertStatus(422);
        $this->assertSame(0, $offer->fresh()->available_units);
        $this->assertDatabaseCount('reservations', 1);
    }
}
