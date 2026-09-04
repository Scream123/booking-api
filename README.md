# Booking API

A RESTful API built with Laravel 12, PHP 8.3, and MySQL 8 for asynchronous accommodation offer imports, optimized
property searches, and race-condition-safe bookings.

> **Branch:** `dev`  
> **Repository:** https://github.com/Scream123/booking-api.git

## Installation & Setup

```bash
composer install
cp .env.example .env
php artisan key:generate

# 1. Create a MySQL database and configure its name and credentials in your .env file
# 2. If using Docker environment, run: docker compose up -d

php artisan migrate --seed
php artisan queue:work
php artisan serve
```

### Running Tests

```bash
php artisan test
```

*Note: Tests run via an isolated sync queue connection, processing imports immediately without a separate worker
running.*

## Architecture

The project strictly adheres to standard Laravel conventions (**"Laravel Way"**) without overengineering (no custom
Actions, DTOs, or Enums):

* **Controllers:** Thin controllers handling requests and formatting responses (`ImportController`,
  `PropertyController`, `ReservationController`).
* **Validation & Resources:** Handled via dedicated Form Requests and API Resources.
* **Background Processing:** Processed asynchronously inside a single `ProcessImportJob` using chunks (size: 200).

## Technical Notes

### Idempotency (Sections 4 & 9)

* **Imports:** Handled via `Import::firstOrCreate()` on `[supplier_id, external_import_id]` paired with a database
  composite unique index. The background job dispatches only if `wasRecentlyCreated` is true.
* **Offers:** Updated in place using `Offer::updateOrCreate()` on `[supplier_id, external_id]` to update existing
  records instead of duplicating them.

### Race Condition Protection (Section 7)

* **Bookings:** `ReservationController@store` wraps execution in a `DB::transaction()` and uses `lockForUpdate()` on the
  `Offer` row. Parallel requests are blocked at the MySQL engine level and execute sequentially, preventing
  double-booking of the last remaining unit.

### Optimized Property Search (Section 6)

* **Search:** `Property::scopeSearchAvailable()` encapsulates the search logic into a single SQL query. It utilizes a
  window function `ROW_NUMBER() OVER (PARTITION BY property_id ORDER BY price ASC)` to filter and rank offers, ensuring
  all heavy lifting (filtering, sorting, and `simplePaginate` pagination) happens strictly inside MySQL.

## API Endpoints Reference

* `POST /api/imports` - Accept import payload (returns `202 Accepted`).
* `GET /api/imports/{id}` - Check async import status.
* `GET /api/properties` - Search cheapest properties (`city`, `check_in`, `check_out`, `guests`).
* `POST /api/offers/{offer}/reservations` - Securely book an offer (returns `201 Created`).

## License
This project is distributed under the MIT license. See the [LICENSE](LICENSE) file for details.