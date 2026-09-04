<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->string('client_reference');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->timestamps();

            $table->unique('client_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};