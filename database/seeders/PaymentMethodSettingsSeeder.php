<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentMethodSetting;

class PaymentMethodSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'payment_method_code' => 'ap',
                'payment_method_name_ar' => 'Apple Pay',
                'payment_method_name_en' => 'Apple Pay',
                'is_enabled' => true,
            ],
            [
                'payment_method_code' => 'stc',
                'payment_method_name_ar' => 'STC Pay',
                'payment_method_name_en' => 'STC Pay',
                'is_enabled' => true,
            ],
            [
                'payment_method_code' => 'md',
                'payment_method_name_ar' => 'مدى',
                'payment_method_name_en' => 'MADA',
                'is_enabled' => false, // Disabled for testing
            ],
            [
                'payment_method_code' => 'uaecc',
                'payment_method_name_ar' => 'بطاقة ائتمان الإمارات',
                'payment_method_name_en' => 'UAE Credit Card',
                'is_enabled' => true,
            ],
            [
                'payment_method_code' => 'ae',
                'payment_method_name_ar' => 'American Express',
                'payment_method_name_en' => 'American Express',
                'is_enabled' => true,
            ],
            [
                'payment_method_code' => 'gp',
                'payment_method_name_ar' => 'Google Pay',
                'payment_method_name_en' => 'Google Pay',
                'is_enabled' => true,
            ],
            [
                'payment_method_code' => 'b',
                'payment_method_name_ar' => 'البنك',
                'payment_method_name_en' => 'Bank',
                'is_enabled' => true,
            ],
            [
                'payment_method_code' => 'kn',
                'payment_method_name_ar' => 'KNET',
                'payment_method_name_en' => 'KNET',
                'is_enabled' => true,
            ],
            [
                'payment_method_code' => 'vm',
                'payment_method_name_ar' => 'Visa/MasterCard',
                'payment_method_name_en' => 'Visa/MasterCard',
                'is_enabled' => true,
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethodSetting::updateOrCreate(
                ['payment_method_code' => $method['payment_method_code']],
                $method
            );
        }
    }
}
