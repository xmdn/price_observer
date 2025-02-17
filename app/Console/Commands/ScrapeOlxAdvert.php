<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Advert;
use Illuminate\Support\Facades\Http;

class ScrapeOlxAdvert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:olx_advert {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape OLX advert data using Selenium and store in DB';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');

        // Start Selenium WebDriver
        $options = new ChromeOptions();
        $options->addArguments([
            '--headless', '--disable-gpu', '--no-sandbox',
            '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36'
        ]);

        $host = 'http://selenium:4444/wd/hub'; // Ensure this matches your Docker service name
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        $driver = RemoteWebDriver::create($host, $capabilities);

        try {
            $driver->get($url);

            $wait = new WebDriverWait($driver, 10);
            try {
                $wait->until(
                    WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('script[type="application/ld+json"]'))
                );
            } catch (\Exception $e) {
                $this->error("Timeout waiting for JSON-LD.");
                return;
            }

            $html = $driver->getPageSource();
            file_put_contents(storage_path('logs/olx_page.html'), $html);
            dump("Page source saved to storage/logs/olx_page.html");
            // Extract JSON-LD using regex (since findElements() isn't working)
            preg_match('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches);

            if (empty($matches)) {
                $this->error("Failed to extract JSON-LD data.");
                return;
            }

            if (isset($matches[1])) {
                $jsonData = trim($matches[1]);
                dump("Extracted JSON-LD:", $jsonData);
                // Decode JSON
                $advertData = json_decode($jsonData, true);
                if (!$advertData) {
                    $this->error("JSON Decoding failed. Possible syntax error in the extracted data.");
                    return;
                }
                dump("Extracted SKU:", $advertData['sku'] ?? 'MISSING');

                $sku = trim($advertData['sku'] ?? '');

                if (!preg_match('/^\d+$/', $sku)) { // Ensure it's only digits
                    $this->error("Invalid SKU detected: " . json_encode($sku));
                    return;
                }

                // Save to database
                $advert = Advert::updateOrCreate(
                    ['advert_id' => $sku],
                    [
                        'link'        => $advertData['url'],
                        'title'       => $advertData['name'],
                        'description' => $advertData['description'],
                        'price'       => $advertData['offers']['price'],
                        'category'    => $advertData['category'] ?? null,
                        'status'      => $advertData['offers']['availability'] ?? 'unknown',
                        'location'    => $advertData['offers']['areaServed']['name'] ?? null,
                        'last_checked_at' => now(),
                    ]
                );
                if ($advert) {
                    // $this->info("Advert {$advert->advert_id} saved successfully!");
                    $this->line(json_encode($advert));
                } else {
                    throw new \Exception("Failed to save advert to the database.");
                }

                // $this->info("Advert {$advertData['sku']} saved successfully!");
            }

            

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        } finally {
            $driver->quit();
        }
    }
}
