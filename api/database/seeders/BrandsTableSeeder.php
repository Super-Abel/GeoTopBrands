<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BrandsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'brand_name' => 'Jackpot BOB',
                'brand_image' => 'https://example.com/jackpot-bob.png',
                'rating' => 5,
                'country_code' => 'FR'
            ],
            [
                'brand_name' => 'Madnix',
                'brand_image' => 'https://example.com/madnix.png',
                'rating' => 4,
                'country_code' => 'FR'
            ],
            [
                'brand_name' => 'Winoui Casino',
                'brand_image' => 'https://example.com/winoui.png',
                'rating' => 4,
                'country_code' => 'FR'
            ],
            [
                'brand_name' => 'Wild Sultan',
                'brand_image' => 'https://example.com/wild-sultan.png',
                'rating' => 4,
                'country_code' => 'FR'
            ],
            [
                'brand_name' => 'Cresus Casino',
                'brand_image' => 'https://example.com/cresus.png',
                'rating' => 4,
                'country_code' => 'FR'
            ]
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}
