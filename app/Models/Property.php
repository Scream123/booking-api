<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Property extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'city'];

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function scopeSearchAvailable(
        Builder $query,
        ?string $city,
        string  $checkIn,
        string  $checkOut,
        int     $guests,
    ): Builder
    {
        $ranked = DB::table('offers')
            ->join('properties', 'properties.id', '=', 'offers.property_id')
            ->select([
                'offers.id as offer_id',
                'offers.supplier_id',
                'offers.property_id',
                'offers.price',
                'offers.currency',
                'offers.available_units',
                'offers.expires_at',
            ])
            ->selectRaw('ROW_NUMBER() 
            OVER (PARTITION BY offers.property_id ORDER BY offers.price ASC) as price_rank')
            ->where('offers.check_in', '<=', $checkIn)
            ->where('offers.check_out', '>=', $checkOut)
            ->where('offers.max_guests', '>=', $guests)
            ->where('offers.available_units', '>', 0)
            ->where('offers.expires_at', '>', now()->toDateTimeString())
            ->when($city, fn($q) => $q->where('properties.city', $city));

        $bestOffers = DB::query()->fromSub($ranked, 'ranked')->where('price_rank', 1);

        return $query
            ->joinSub($bestOffers, 'best_offer', 'best_offer.property_id', '=', 'properties.id')
            ->join('suppliers', 'suppliers.id', '=', 'best_offer.supplier_id')
            ->select([
                'properties.id',
                'properties.code',
                'properties.name',
                'properties.city',
                'best_offer.offer_id as best_offer_id',
                'suppliers.code as best_offer_supplier_code',
                'best_offer.price as best_offer_price',
                'best_offer.currency as best_offer_currency',
                'best_offer.available_units as best_offer_available_units',
                'best_offer.expires_at as best_offer_expires_at',
            ])
            ->orderBy('best_offer.price');
    }
}