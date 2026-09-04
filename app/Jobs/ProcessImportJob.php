<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    private const CHUNK_SIZE = 200;

    /** @param array<string, mixed> $payload */
    public function __construct(
        public readonly int   $importId,
        public readonly array $payload,
    )
    {
    }

    public function handle(): void
    {
        $import = Import::query()->with('supplier')->findOrFail($this->importId);

        if ($import->status !== 'pending') {
            return;
        }

        $import->update(['status' => 'processing']);

        try {
            $offers = collect($this->payload['offers'] ?? [])
                ->map(fn(array $raw): array => $this->mapOffer($raw));

            $processed = 0;

            foreach ($offers->chunk(self::CHUNK_SIZE) as $chunk) {
                $processed += $this->processChunk($import, $chunk);
            }

            $import->update([
                'status' => 'completed',
                'processed_offers' => $processed,
                'completed_at' => now(),
            ]);
        } catch (Throwable $e) {
            $import->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /** @param array<string, mixed> $raw */
    private function mapOffer(array $raw): array
    {
        return [
            'external_id' => (string)$raw['external_id'],
            'property_code' => (string)$raw['property']['code'],
            'property_name' => (string)$raw['property']['name'],
            'city' => (string)$raw['property']['city'],
            'check_in' => (string)$raw['check_in'],
            'check_out' => (string)$raw['check_out'],
            'max_guests' => (int)$raw['max_guests'],
            'price' => (int)$raw['price'],
            'currency' => strtoupper((string)$raw['currency']),
            'available_units' => (int)$raw['available_units'],
            'expires_at' => (string)$raw['expires_at'],
        ];
    }

    private function processChunk(Import $import, Collection $chunk): int
    {
        return DB::transaction(function () use ($import, $chunk): int {
            foreach ($chunk as $offer) {
                $property = Property::query()->firstOrCreate(
                    ['code' => $offer['property_code']],
                    ['name' => $offer['property_name'], 'city' => $offer['city']],
                );

                Offer::query()->updateOrCreate(
                    [
                        'supplier_id' => $import->supplier_id,
                        'external_id' => $offer['external_id'],
                    ],
                    [
                        'property_id' => $property->id,
                        'last_import_id' => $import->id,
                        'check_in' => $offer['check_in'],
                        'check_out' => $offer['check_out'],
                        'max_guests' => $offer['max_guests'],
                        'price' => $offer['price'],
                        'currency' => $offer['currency'],
                        'available_units' => $offer['available_units'],
                        'expires_at' => $offer['expires_at'],
                    ],
                );
            }

            return $chunk->count();
        });
    }
}