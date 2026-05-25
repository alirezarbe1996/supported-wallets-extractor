<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Currency;

class UpdateCurrencies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-currencies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and update currencies from CoinMarketCap API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiBaseUrl = 'https://api.coinmarketcap.com/data-api/v3/cryptocurrency/listing';
        $start = 1;
        $limit = 1000;
        $totalCount = null;
        $currencySlugsFromApi = [];

        do {
            $apiUrl = "$apiBaseUrl?start=$start&limit=$limit";
            $response = Http::get($apiUrl);

            if ($response->successful()) {
                $data = $response->json('data');
                $cryptoCurrencyList = $data['cryptoCurrencyList'];
                $totalCount = (int) $data['totalCount'];

                foreach ($cryptoCurrencyList as $currencyData) {
                    $slug = $currencyData['slug'];
                    $currencySlugsFromApi[] = $slug;

                    Currency::updateOrCreate(
                        ['slug' => $slug],
                        [
                            'name' => $currencyData['name'],
                            'symbol' => $currencyData['symbol']
                        ]
                    );
                }

                $start += $limit;

            } else {
                $this->error('Failed to fetch data from the API.');
                return;
            }

        } while ($start <= $totalCount);

        Currency::whereNotIn('slug', $currencySlugsFromApi)->delete();

        $this->info('Currencies have been updated successfully.');
    }
}
