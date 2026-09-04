<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Offer> */
class OfferFactory extends Factory
{
    protected $model = Offer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $checkIn = $this->faker->dateTimeBetween('+1 week', '+2 months');
        $checkOut = (clone $checkIn)->modify('+' . $this->faker->numberBetween(2, 7) . ' days');

        return [
            'supplier_id' => Supplier::factory(),
            'property_id' => Property::factory(),
            'last_import_id' => null,
            'external_id' => 'offer-' . $this->faker->unique()->uuid(),
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
            'max_guests' => $this->faker->numberBetween(1, 6),
            'price' => $this->faker->numberBetween(5000, 200000),
            'currency' => 'EUR',
            'available_units' => $this->faker->numberBetween(1, 5),
            'expires_at' => now()->addDays($this->faker->numberBetween(5, 60)),
        ];
    }
}
