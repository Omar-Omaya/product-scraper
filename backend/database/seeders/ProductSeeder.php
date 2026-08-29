<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->products() as $i => $attributes) {
            $product = Product::updateOrCreate(
                ['source_url' => $attributes['source_url']],
                Arr::except($attributes, 'source_url'),
            );

            $product->forceFill(['created_at' => now()->subMinutes($i * 17)])->save();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function products(): array
    {
        return [
            [
                'title' => 'Infinix Hot 40i 8GB RAM 256GB ROM 6.56" Smartphone - Starlit Black',
                'price' => 5499.00,
                'image_url' => 'https://picsum.photos/seed/infinix-hot-40i/400/400',
                'source_url' => 'https://www.jumia.com.eg/infinix-hot-40i.html',
            ],
            [
                'title' => 'Samsung Galaxy A05s 4GB/128GB 6.7" Dual SIM 50MP Camera',
                'price' => 6899.00,
                'image_url' => 'https://picsum.photos/seed/galaxy-a05s/400/400',
                'source_url' => 'https://www.jumia.com.eg/samsung-galaxy-a05s.html',
            ],
            [
                'title' => 'Tecno Spark 20 Pro 8GB RAM 256GB 108MP Camera - Sunset Blush',
                'price' => 7299.00,
                'image_url' => 'https://picsum.photos/seed/tecno-spark-20-pro/400/400',
                'source_url' => 'https://www.jumia.com.eg/tecno-spark-20-pro.html',
            ],
            [
                'title' => 'Xiaomi Redmi 13C 6GB/128GB 50MP Triple Camera 5000mAh',
                'price' => 4999.00,
                'image_url' => 'https://picsum.photos/seed/redmi-13c/400/400',
                'source_url' => 'https://www.jumia.com.eg/xiaomi-redmi-13c.html',
            ],
            [
                'title' => 'Oraimo FreePods 4 ANC True Wireless Earbuds with 4 Mics',
                'price' => 1299.00,
                'image_url' => 'https://picsum.photos/seed/oraimo-freepods-4/400/400',
                'source_url' => 'https://www.jumia.com.eg/oraimo-freepods-4.html',
            ],
            [
                'title' => 'Anker PowerCore 20000mAh Power Bank 20W PD Fast Charging',
                'price' => 2149.00,
                'image_url' => 'https://picsum.photos/seed/anker-powercore-20000/400/400',
                'source_url' => 'https://www.jumia.com.eg/anker-powercore-20000.html',
            ],
            [
                'title' => 'HP Pavilion 15 Core i5 12th Gen 8GB 512GB SSD 15.6" FHD Laptop',
                'price' => 28999.00,
                'image_url' => 'https://picsum.photos/seed/hp-pavilion-15/400/400',
                'source_url' => 'https://www.jumia.com.eg/hp-pavilion-15.html',
            ],
            [
                'title' => 'JBL Tune 510BT Wireless On-Ear Headphones Pure Bass 40h Battery',
                'price' => 1899.00,
                'image_url' => 'https://picsum.photos/seed/jbl-tune-510bt/400/400',
                'source_url' => 'https://www.jumia.com.eg/jbl-tune-510bt.html',
            ],
            [
                'title' => 'Logitech M331 Silent Plus Wireless Mouse - Black',
                'price' => 649.00,
                'image_url' => 'https://picsum.photos/seed/logitech-m331/400/400',
                'source_url' => 'https://www.jumia.com.eg/logitech-m331-silent.html',
            ],
            [
                'title' => 'Redragon K552 Kumara Mechanical Gaming Keyboard RGB Backlit',
                'price' => 1450.00,
                'image_url' => 'https://picsum.photos/seed/redragon-k552/400/400',
                'source_url' => 'https://www.jumia.com.eg/redragon-k552-kumara.html',
            ],
            [
                'title' => 'Xiaomi Mi Smart Band 8 1.62" AMOLED 16-Day Battery Fitness Tracker',
                'price' => 1799.00,
                'image_url' => 'https://picsum.photos/seed/mi-band-8/400/400',
                'source_url' => 'https://www.jumia.com.eg/xiaomi-mi-band-8.html',
            ],
            [
                'title' => 'TP-Link Archer C6 AC1200 Dual Band Gigabit Wi-Fi Router',
                'price' => 1099.00,
                'image_url' => 'https://picsum.photos/seed/tplink-archer-c6/400/400',
                'source_url' => 'https://www.jumia.com.eg/tp-link-archer-c6.html',
            ],
        ];
    }
}
