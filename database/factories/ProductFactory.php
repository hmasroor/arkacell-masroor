<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $products = [
            'میز', 'صندلی', 'اداری', 'کامپیوتر', 'لپتاپ', 'مانیتور',
            'کیبورد', 'ماوس', 'پرینتر', 'شبکه', 'دوربین', 'مودم',
            'روتر', 'هارد', 'کابل', 'برق', 'صنعتی', 'خانگی',
            'دیجیتال', 'هوشمند', 'مکانیکی', 'بی‌سیم', 'حرفه‌ای',
            'اقتصادی', 'پیشرفته', 'رومیزی', 'قابل‌حمل', 'سریع',
            'جدید', 'پلاس', 'پرو', 'استاندارد',
        ];

        return [
            'tenant_id' => Tenant::factory(),
            'sku' => fake()->unique()->bothify('SKU-####-??'),
            'name' => collect($products)->random(3)->implode(' '),
            'unit_price' => fake()->numerify('######000'),
        ];
    }
}
