<?php

namespace App\Console\Commands;

use Google\Cloud\Core\Exception\ServiceException;
use Google\Cloud\Translate\V2\TranslateClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Currency;
use App\Models\Wallet;
use Illuminate\Http\Client\ConnectionException;

class GetSupportedWalletsForCoin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-supported-wallets-for-coin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch supported wallets for each currency from CoinMarketCap';


    protected $translateClient;

    public function __construct()
    {
        parent::__construct();
        $this->translateClient = new TranslateClient([
            'key' => env('GOOGLE_TRANSLATE_API_KEY'),
        ]);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currencies = Currency::all();

        foreach ($currencies as $currency) {
            $slug = $currency->slug;
            $url = "https://coinmarketcap.com/currencies/{$slug}/";

            $this->info("Fetching wallets for: {$currency->name} ({$slug})");

            $response = null;
            $retries = 3;
            $timeout = 60;

            for ($attempt = 1; $attempt <= $retries; $attempt++) {
                try {
                    $response = Http::timeout($timeout)->get($url);

                    $this->info("Fetching URL: $url");
                    $this->info("Response Status: " . $response->status());

                    if ($response->successful()) {
                        $htmlContent = $response->body();
                        preg_match('/<script id="__NEXT_DATA__" type="application\/json"[^>]*>(.*?)<\/script>/s', $htmlContent, $matches);

                        if (!empty($matches)) {
                            $jsonData = json_decode($matches[1], true);
                            $wallets = $jsonData['props']['pageProps']['detailRes']['detail']['wallets'] ?? [];

                            foreach ($wallets as $walletData) {
                                $wallet = [
                                    'enName' => $walletData['name'],
//                                    'faName' => $this->translateToPersian($walletData['name']),
                                    'faName' => $walletData['name'],
                                    'icon' => $walletData['logo'] ? "https://s2.coinmarketcap.com/static/img/wallets/128x128/{$walletData['logo']}" : null,
                                    'website' => $walletData['url'] ?? null,
                                ];

                                $walletModel = Wallet::updateOrCreate(
                                    ['enName' => $wallet['enName'], 'website' => $wallet['website']],
                                    $wallet
                                );

                                $currency->wallets()->attach($walletModel->id);
                            }

                            $this->info("Wallets for {$currency->name} fetched and saved.");
                        } else {
                            $this->warn("No wallet data found for {$currency->name}.");
                        }
                        break;
                    } else {
                        $this->warn("Response unsuccessful for {$currency->name}, Status: " . $response->status());
                    }
                } catch (ConnectionException $e) {
                    $this->warn("Attempt {$attempt} failed for {$currency->name}: " . $e->getMessage());

                    if ($attempt === $retries) {
                        $this->error("Failed to fetch data for {$currency->name} after {$retries} attempts.");
                        break;
                    }

                    sleep(2);
                }
            }
        }
    }

    /**
     * Translate the English wallet name to Persian.
     *
     * @param string $name
     * @return string
     * @throws ServiceException
     */
    private function translateToPersian(string $name): string
    {
        $result = $this->translateClient->translate($name, [
            'target' => 'fa'
        ]);

        return $result['text'] ?? $name;
    }
}
