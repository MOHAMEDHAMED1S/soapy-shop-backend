<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();

        $products = [
            // منتجات العناية بالبشرة
            [
                'title' => 'كريم مرطب للوجه بفيتامين C',
                'slug' => 'vitamin-c-face-moisturizer',
                'description' => 'كريم مرطب غني بفيتامين C يساعد على تجديد خلايا البشرة وإشراقها. مناسب لجميع أنواع البشرة ويوفر ترطيباً عميقاً لمدة 24 ساعة.',
                'short_description' => 'كريم مرطب بفيتامين C للإشراق والترطيب العميق',
                'price' => 25.500,
                'currency' => 'KWD',
                'is_available' => true,
                'category_name' => 'كريمات الوجه',
                'images' => [
                    'https://picsum.photos/800/800?random=1',
                    'https://picsum.photos/800/800?random=2'
                ],
                'meta' => [
                    'brand' => 'Soapy Shop',
                    'size' => '50ml',
                    'ingredients' => ['فيتامين C', 'حمض الهيالورونيك', 'زيت الأرغان']
                ],
            ],
            [
                'title' => 'سيروم الريتينول المضاد للشيخوخة',
                'slug' => 'anti-aging-retinol-serum',
                'description' => 'سيروم قوي بالريتينول يساعد على تقليل التجاعيد وتحسين ملمس البشرة. يحفز تجديد الخلايا ويوحد لون البشرة.',
                'short_description' => 'سيروم ريتينول لمحاربة علامات الشيخوخة',
                'price' => 35.750,
                'currency' => 'KWD',
                'is_available' => true,
                'category_name' => 'سيروم',
                'images' => [
                    'https://picsum.photos/800/800?random=3',
                    'https://picsum.photos/800/800?random=4'
                ],
                'meta' => [
                    'brand' => 'Soapy Shop',
                    'size' => '30ml',
                    'ingredients' => ['ريتينول', 'فيتامين E', 'زيت الجوجوبا']
                ],
            ],
            [
                'title' => 'منظف الوجه بالفحم النشط',
                'slug' => 'activated-charcoal-face-cleanser',
                'description' => 'منظف عميق بالفحم النشط ينظف المسام بعمق ويزيل الشوائب والزيوت الزائدة. مثالي للبشرة الدهنية والمختلطة.',
                'short_description' => 'منظف وجه بالفحم النشط للتنظيف العميق',
                'price' => 18.250,
                'currency' => 'KWD',
                'is_available' => true,
                'category_name' => 'منظفات الوجه',
                'images' => [
                    'https://picsum.photos/800/800?random=5',
                    'https://picsum.photos/800/800?random=6'
                ],
                'meta' => [
                    'brand' => 'Soapy Shop',
                    'size' => '150ml',
                    'ingredients' => ['فحم نشط', 'طين البنتونيت', 'زيت شجرة الشاي']
                ],
            ],
            // منتجات العناية بالجسم
            [
                'title' => 'كريم الجسم بزبدة الشيا',
                'slug' => 'shea-butter-body-cream',
                'description' => 'كريم جسم فاخر بزبدة الشيا الطبيعية يوفر ترطيباً مكثفاً للبشرة الجافة. تركيبة غنية تترك البشرة ناعمة ومرنة.',
                'short_description' => 'كريم جسم مرطب بزبدة الشيا الطبيعية',
                'price' => 22.000,
                'currency' => 'KWD',
                'is_available' => true,
                'category_name' => 'كريمات الجسم',
                'images' => [
                    'https://picsum.photos/800/800?random=7',
                    'https://picsum.photos/800/800?random=8'
                ],
                'meta' => [
                    'brand' => 'Soapy Shop',
                    'size' => '200ml',
                    'ingredients' => ['زبدة الشيا', 'زيت جوز الهند', 'فيتامين E']
                ],
            ],
            [
                'title' => 'زيت الجسم بالأرغان والورد',
                'slug' => 'argan-rose-body-oil',
                'description' => 'زيت جسم طبيعي بخلاصة الأرغان والورد يغذي البشرة ويتركها ناعمة ومتألقة. رائحة عطرة تدوم طويلاً.',
                'short_description' => 'زيت جسم طبيعي بالأرغان والورد',
                'price' => 28.500,
                'currency' => 'KWD',
                'is_available' => true,
                'category_name' => 'زيوت الجسم',
                'images' => [
                    'https://picsum.photos/800/800?random=9',
                    'https://picsum.photos/800/800?random=10'
                ],
                'meta' => [
                    'brand' => 'Soapy Shop',
                    'size' => '100ml',
                    'ingredients' => ['زيت الأرغان', 'زيت الورد', 'فيتامين A']
                ],
            ],
            [
                'title' => 'صابون طبيعي بالعسل واللافندر',
                'slug' => 'honey-lavender-natural-soap',
                'description' => 'صابون طبيعي مصنوع يدوياً بالعسل الطبيعي واللافندر المهدئ. ينظف البشرة بلطف ويتركها ناعمة ومعطرة.',
                'short_description' => 'صابون طبيعي بالعسل واللافندر المهدئ',
                'price' => 12.750,
                'currency' => 'KWD',
                'is_available' => true,
                'category_name' => 'صابون طبيعي',
                'images' => [
                    'https://picsum.photos/800/800?random=11',
                    'https://picsum.photos/800/800?random=12'
                ],
                'meta' => [
                    'brand' => 'Soapy Shop',
                    'size' => '120g',
                    'ingredients' => ['عسل طبيعي', 'زيت اللافندر', 'زيت الزيتون']
                ],
            ],
            // العطور
            [
                'title' => 'عطر زهرة الياسمين الأنثوي',
                'slug' => 'jasmine-flower-womens-perfume',
                'description' => 'عطر أنثوي راقٍ بعبير زهرة الياسمين الطبيعية. تركيبة فاخرة تجمع بين النفحات الزهرية والخشبية لإطلالة مميزة.',
                'short_description' => 'عطر نسائي بعبير الياسمين الطبيعي',
                'price' => 45.000,
                'currency' => 'KWD',
                'is_available' => true,
                'category_name' => 'عطور نسائية',
                'images' => [
                    'https://picsum.photos/800/800?random=13',
                    'https://picsum.photos/800/800?random=14'
                ],
                'meta' => [
                    'brand' => 'Soapy Shop',
                    'size' => '50ml',
                    'notes' => ['ياسمين', 'ورد', 'خشب الصندل']
                ],
            ],
            [
                'title' => 'عطر العود الملكي الرجالي',
                'slug' => 'royal-oud-mens-perfume',
                'description' => 'عطر رجالي فاخر بخلاصة العود الطبيعي. تركيبة قوية وثابتة تناسب الرجل الأنيق والواثق من نفسه.',
                'short_description' => 'عطر رجالي فاخر بالعود الطبيعي',
                'price' => 55.000,
                'currency' => 'KWD',
                'is_available' => true,
                'category_name' => 'عطور رجالية',
                'images' => [
                    'https://picsum.photos/800/800?random=15',
                    'https://picsum.photos/800/800?random=16'
                ],
                'meta' => [
                    'brand' => 'Soapy Shop',
                    'size' => '50ml',
                    'notes' => ['عود', 'عنبر', 'مسك']
                ],
            ],
            // المكياج
            [
                'title' => 'أحمر شفاه مات طويل الثبات',
                'slug' => 'long-lasting-matte-lipstick',
                'description' => 'أحمر شفاه بتركيبة مات فاخرة يدوم طويلاً دون تشقق. متوفر بألوان عصرية تناسب جميع المناسبات.',
                'short_description' => 'أحمر شفاه مات طويل الثبات',
                'price' => 15.500,
                'currency' => 'KWD',
                'is_available' => true,
                'category_name' => 'أحمر الشفاه',
                'images' => [
                    'https://picsum.photos/800/800?random=17',
                    'https://picsum.photos/800/800?random=18'
                ],
                'meta' => [
                    'brand' => 'Soapy Shop',
                    'shade' => 'Ruby Red',
                    'finish' => 'Matte'
                ],
            ],
            [
                'title' => 'كريم أساس طبيعي بتغطية كاملة',
                'slug' => 'natural-full-coverage-foundation',
                'description' => 'كريم أساس بتركيبة طبيعية يوفر تغطية كاملة ومظهراً طبيعياً. مقاوم للماء ويدوم طوال اليوم.',
                'short_description' => 'كريم أساس طبيعي بتغطية كاملة',
                'price' => 32.000,
                'currency' => 'KWD',
                'is_available' => true,
                'category_name' => 'كريم الأساس',
                'images' => [
                    'https://picsum.photos/800/800?random=19',
                    'https://picsum.photos/800/800?random=20'
                ],
                'meta' => [
                    'brand' => 'Soapy Shop',
                    'size' => '30ml',
                    'coverage' => 'Full',
                    'finish' => 'Natural'
                ],
            ],
        ];

        foreach ($products as $productData) {
            $categoryName = $productData['category_name'];
            unset($productData['category_name']);

            $category = $categories->where('name', $categoryName)->first();
            if ($category) {
                $productData['category_id'] = $category->id;
                Product::updateOrCreate(
                    ['slug' => $productData['slug']],
                    $productData
                );
            }
        }
    }
}