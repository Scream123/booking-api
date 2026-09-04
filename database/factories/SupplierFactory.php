<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Supplier> */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->slug(2),
            'name' => $this->faker->company(),
        ];
    }
}
