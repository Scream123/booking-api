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
            'offers.*' => ['array'],
        ];
    }
}