# Crypto Wallet Finder — Laravel 11

A Laravel 11 application that fetches cryptocurrency data from CoinMarketCap, stores supported wallets for each coin, and exposes them via a REST API.

---

## Overview

This project consists of two automated console commands that sync currency and wallet data, and a single API endpoint that returns the wallets supporting a given cryptocurrency.

---

## Project Structure

```
app/
├── Console/
│   └── Commands/
│       ├── UpdateCurrencies.php          # Syncs currency list from CoinMarketCap
│       └── GetSupportedWalletsForCoin.php # Fetches wallets for each currency
├── Http/
│   └── Controllers/
│       └── WalletController.php          # API controller
├── Models/
│   ├── Currency.php                      # Currency Eloquent model
│   └── Wallet.php                        # Wallet Eloquent model
routes/
├── api.php                               # API route definitions
└── console.php                           # Scheduled task definitions
```

---

## Models

### `Currency`

Represents a cryptocurrency.

| Field    | Type   | Description                          |
|----------|--------|--------------------------------------|
| `name`   | string | Full name (e.g. Bitcoin)             |
| `symbol` | string | Ticker symbol (e.g. BTC)             |
| `slug`   | string | URL-friendly identifier (e.g. bitcoin) |

**Relationship:** Has many `Wallet` records via a many-to-many pivot.

---

### `Wallet`

Represents a cryptocurrency wallet.

| Field     | Type   | Description                   |
|-----------|--------|-------------------------------|
| `enName`  | string | English name of the wallet    |
| `faName`  | string | Persian name (currently same as English) |
| `icon`    | string | URL to the wallet's 128×128 logo image |
| `website` | string | Official website URL          |

**Relationship:** Belongs to many `Currency` records via a many-to-many pivot.

---

## Console Commands

### `app:update-currencies`

Fetches the full cryptocurrency listing from the CoinMarketCap data API and keeps the local `currencies` table in sync.

**What it does:**
- Paginates through all currencies in batches of 1,000
- Creates or updates each currency record by `slug`
- Deletes any currencies no longer present in the API response

**Run manually:**
```bash
php artisan app:update-currencies
```

---

### `app:get-supported-wallets-for-coin`

Scrapes each currency's CoinMarketCap page and extracts the list of supported wallets from the embedded `__NEXT_DATA__` JSON.

**What it does:**
- Iterates over every currency in the database
- Fetches `https://coinmarketcap.com/currencies/{slug}/`
- Parses wallet data (name, logo, website) from the page's JSON payload
- Creates or updates each `Wallet` record and attaches it to the currency
- Retries up to 3 times on connection failure, with a 2-second delay between attempts

> **Note:** Persian translation via Google Translate is implemented but currently disabled. The `faName` field defaults to the English name. To enable it, set `GOOGLE_TRANSLATE_API_KEY` in your `.env` and uncomment the `translateToPersian()` call in the command.

**Run manually:**
```bash
php artisan app:get-supported-wallets-for-coin
```

---

## Scheduled Tasks

Both commands run automatically on a schedule defined in `routes/console.php`.

| Command                                  | Schedule                        |
|------------------------------------------|---------------------------------|
| `app:update-currencies`                  | 1st and 14th of each month at 00:00 |
| `app:get-supported-wallets-for-coin`     | 1st and 14th of each month at 03:00 |

The wallet command runs 3 hours after the currency sync to ensure the currency table is up to date before scraping begins.

To activate the scheduler, add the following cron entry to your server:

```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## API Endpoints

### `GET /api/v1/wallets/{symbol}`

Returns all wallets that support a given cryptocurrency, looked up by its **symbol** or **slug**.

**Parameters:**

| Parameter | Location | Description                          | Example  |
|-----------|----------|--------------------------------------|----------|
| `symbol`  | URL path | Coin symbol or CoinMarketCap slug    | `BTC` or `bitcoin` |

**Success Response `200`:**
```json
{
    "message": "Wallets retrieved successfully",
    "success": true,
    "data": [
        {
            "enName": "Ledger",
            "faName": "Ledger",
            "icon": "https://s2.coinmarketcap.com/static/img/wallets/128x128/ledger.png",
            "website": "https://www.ledger.com"
        }
    ]
}
```

**Not Found Response `404`:**
```json
{
    "message": "Currency not found",
    "success": false,
    "data": []
}
```

**Example requests:**
```bash
# By symbol
GET /api/v1/wallets/BTC

# By slug
GET /api/v1/wallets/bitcoin
```

---

## Requirements

- PHP 8.2+
- Laravel 11
- A database supported by Laravel (MySQL, PostgreSQL, SQLite, etc.)
- Internet access to reach the CoinMarketCap API and website

**Optional:**
- A Google Cloud Translate API key (for Persian name translation)

---

## Installation

```bash
# 1. Clone the repository
git clone <repository-url>
cd <project-folder>

# 2. Install dependencies
composer install

# 3. Copy and configure environment
cp .env.example .env
php artisan key:generate

# 4. Configure your database in .env, then run migrations
php artisan migrate

# 5. (Optional) Add Google Translate key to .env
GOOGLE_TRANSLATE_API_KEY=your_key_here

# 6. Seed initial data by running the commands manually
php artisan app:update-currencies
php artisan app:get-supported-wallets-for-coin
```

---

## Environment Variables

| Variable                  | Required | Description                          |
|---------------------------|----------|--------------------------------------|
| `DB_*`                    | Yes      | Standard Laravel database config     |
| `GOOGLE_TRANSLATE_API_KEY`| No       | Enables Persian wallet name translation |
