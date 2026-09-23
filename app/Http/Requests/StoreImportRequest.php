<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'supplier' => ['required', 'string', Rule::exists(Supplier::class, 'code')],
            'external_import_id' => ['required', 'string', 'max:191'],
            'sent_at' => ['required', 'date'],

            'offers' => ['required', 'array', 'min:1'],

            'offers.*.external_id' => ['required', 'string'],

            'offers.*.property' => ['required', 'array'],
            'offers.*.property.code' => ['required', 'string'],
            'offers.*.property.name' => ['required', 'string'],
            'offers.*.property.city' => ['required', 'string'],

            'offers.*.check_in' => ['required', 'date'],
            'offers.*.check_out' => ['required', 'date', 'after:check_in'],

            'offers.*.max_guests' => ['required', 'integer', 'min:1'],
            'offers.*.price' => ['required', 'integer', 'min:0'],
            'offers.*.currency' => ['required', 'string', 'size:3'],
            'offers.*.available_units' => ['required', 'integer', 'min:0'],
            'offers.*.expires_at' => ['required', 'date'],
        ];
    }
}