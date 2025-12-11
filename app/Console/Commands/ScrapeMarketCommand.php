<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use App\Models\MarketPrice;

class ScrapeMarketCommand extends Command
{
    protected $signature = 'scrape:market';
    protected $description = 'Scrape harga komoditas otomatis dari Karosatuklik';

    public function handle()
    {
        $client = HttpClient::create();

        // 1. URL topic list
        $topicUrl = 'https://karosatuklik.com/topic/daftar-harga-komoditas/';

        // 2. Ambil halaman TOPIC
        $response = $client->request('GET', $topicUrl);
        $html = $response->getContent();
        $crawler = new Crawler($html);

        // 3. Ambil artikel terbaru
        $latestUrl = $crawler
            ->filter('article h2.entry-title a')
            ->first()
            ->attr('href');

        if (!$latestUrl) {
            $this->error("Tidak ditemukan artikel harga komoditas terbaru.");
            return;
        }

        $this->info("Scraping artikel: " . $latestUrl);

        // 4. Ambil halaman artikel
        $response = $client->request('GET', $latestUrl);
        $html = $response->getContent();
        $crawler = new Crawler($html);

        // 5. Ambil tabel harga
        $rows = $crawler->filter('table tbody tr');

        if ($rows->count() === 0) {
            $this->error("Tidak ada data tabel di halaman artikel.");
            return;
        }

        // 6. Reset data lama
        MarketPrice::truncate();

        // 7. Ambil data ke array dulu
        $prices = [];

        $rows->each(function ($row) {
            $cols = $row->filter('td');

            if ($cols->count() >= 3) {
                MarketPrice::create([
                    'commodity' => trim($cols->eq(1)->text()),  // Nama komoditas
                    'price'     => trim($cols->eq(2)->text()),  // Harga
                    'unit'      => $cols->count() > 3 ? trim($cols->eq(3)->text()) : '-', // Satuan
                ]);
            }
        });

        // 8. Insert data ke database
        foreach ($prices as $item) {
            MarketPrice::create([
                'commodity' => $item['commodity'],
                'price'     => $item['price'],
                'unit'      => $item['unit'],
            ]);
        }

        $this->info("Update harga komoditas selesai.");
    }
}
