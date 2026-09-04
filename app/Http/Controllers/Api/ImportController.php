<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImportRequest;
use App\Http\Resources\ImportResource;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;

class ImportController extends Controller
{
    public function store(StoreImportRequest $request): JsonResponse
    {
        $data = $request->validated();

        $supplier = Supplier::query()->where('code', $data['supplier'])->firstOrFail();

        $import = Import::query()->firstOrCreate(
            [
                'supplier_id' => $supplier->id,
                'external_import_id' => $data['external_import_id'],
            ],
            [
                'sent_at' => $data['sent_at'],
                'status' => 'pending',
                'total_offers' => count($data['offers'] ?? []),
            ],
        );

        if ($import->wasRecentlyCreated) {
            ProcessImportJob::dispatch($import->id, $data);
        }

        return response()->json([
            'data' => ['id' => $import->id, 'status' => $import->status],
        ], 202);
    }

    public function show(Import $import): ImportResource
    {
        return new ImportResource($import->load('supplier'));
    }
}
