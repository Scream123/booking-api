<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Offer;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function store(StoreReservationRequest $request, Offer $offer): JsonResponse
    {
        $data = $request->validated();

        $reservation = DB::transaction(function () use ($offer, $data): Reservation {
            $locked = Offer::query()->lockForUpdate()->findOrFail($offer->id);

            if ($locked->available_units <= 0) {
                throw ValidationException::withMessages([
                    'offer' => 'This offer no longer has any available units.',
                ]);
            }

            $locked->decrement('available_units');

            return Reservation::query()->create([
                'offer_id' => $locked->id,
                'client_reference' => $data['client_reference'],
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
            ]);
        });

        return (new ReservationResource($reservation))->response()->setStatusCode(201);
    }
}
