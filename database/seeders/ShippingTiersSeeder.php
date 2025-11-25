<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CountryShippingRate;
use App\Models\ShippingWeightTier;

class ShippingTiersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // الدول التي سيتم إضافة tiers لها
        $countries = ['KW', 'SA', 'AE', 'BH', 'OM', 'QA', 'EG'];

        // الشرائح الموحدة لجميع الدول
        $tiers = [
            ['max_weight_kg' => 0.5, 'base_price' => 3.52, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 1.0, 'base_price' => 4.32, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 1.5, 'base_price' => 5.12, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 2.0, 'base_price' => 5.92, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 2.5, 'base_price' => 6.72, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 3.0, 'base_price' => 7.52, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 3.5, 'base_price' => 8.32, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 4.0, 'base_price' => 9.12, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 4.5, 'base_price' => 9.92, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 5.0, 'base_price' => 10.72, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 5.5, 'base_price' => 11.52, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 6.0, 'base_price' => 12.32, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 6.5, 'base_price' => 13.12, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 7.0, 'base_price' => 13.92, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 7.5, 'base_price' => 14.72, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 8.0, 'base_price' => 15.52, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 8.5, 'base_price' => 16.32, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 9.0, 'base_price' => 17.12, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 9.5, 'base_price' => 17.92, 'additional_percentage' => 0.15],
            ['max_weight_kg' => 10.0, 'base_price' => 18.72, 'additional_percentage' => 0.15],
        ];

        foreach ($countries as $countryCode) {
            // التأكد من وجود الدولة، وإن لم تكن موجودة يتم إنشاؤها
            CountryShippingRate::firstOrCreate(
                ['country_code' => $countryCode],
                ['is_active' => true]
            );

            // حذف أي tiers قديمة لهذه الدولة
            ShippingWeightTier::where('country_code', $countryCode)->delete();

            // إضافة الشرائح الجديدة
            foreach ($tiers as $tier) {
                ShippingWeightTier::create([
                    'country_code' => $countryCode,
                    'max_weight_kg' => $tier['max_weight_kg'],
                    'base_price' => $tier['base_price'],
                    'additional_percentage' => $tier['additional_percentage'],
                ]);
            }

            $this->command->info("✓ Added " . count($tiers) . " tiers for {$countryCode}");
        }

        $this->command->info("✓ Successfully seeded shipping tiers for " . count($countries) . " countries!");
    }
}
