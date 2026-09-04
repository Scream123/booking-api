<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'client_reference' => ['required', 'string', 'max:191', 'unique:reservations,client_reference'],
            'customer_name' => ['required', 'string', 'max:191'],
            'customer_email' => ['required', 'email', 'max:191'],
        ];
    }
}
