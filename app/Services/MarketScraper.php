<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\MarketPrice;

class MarketScraper
{
    private $url = "https://karosatuklik.com/topic/daftar-harga-komoditas/";

    public function scrape()
    {
        $html = Http::get($this->url)->body();
        $crawler = new Crawler($html);

        $rows = $crawler->filter('table tbody tr');
        $today = now()->toDateString();

        foreach ($rows as $row) {
            $td = (new Crawler($row))->filter('td');

            $name  = trim($td->eq(0)->text());
            $price = trim($td->eq(1)->text());
            $unit  = trim($td->eq(2)->text());

            MarketPrice::updateOrCreate(
                ['name' => $name, 'date' => $today],
                ['price' => $price, 'unit' => $unit]
            );
        }

        return true;
    }
}