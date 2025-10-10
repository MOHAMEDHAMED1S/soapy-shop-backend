<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'العناية بالبشرة',
                'slug' => 'skin-care',
                'image' => 'https://picsum.photos/300/200?random=1',
                'parent_id' => null,
                'children' => [
                    [
                        'name' => 'كريمات الوجه',
                        'slug' => 'face-creams',
                        'image' => 'https://picsum.photos/300/200?random=2',
                    ],
                    [
                        'name' => 'سيروم',
                        'slug' => 'serums',
                        'image' => 'https://picsum.photos/300/200?random=3',
                    ],
                    [
                        'name' => 'منظفات الوجه',
                        'slug' => 'face-cleansers',
                        'image' => 'https://picsum.photos/300/200?random=4',
                    ]
                ]
            ],
            [
                'name' => 'العناية بالجسم',
                'slug' => 'body-care',
                'image' => 'https://picsum.photos/300/200?random=5',
                'parent_id' => null,
                'children' => [
                    [
                        'name' => 'كريمات الجسم',
                        'slug' => 'body-creams',
                        'image' => 'https://picsum.photos/300/200?random=6',
                    ],
                    [
                        'name' => 'زيوت الجسم',
                        'slug' => 'body-oils',
                        'image' => 'https://picsum.photos/300/200?random=7',
                    ],
                    [
                        'name' => 'صابون طبيعي',
                        'slug' => 'natural-soap',
                        'image' => 'https://picsum.photos/300/200?random=8',
                    ]
                ]
            ],
            [
                'name' => 'العطور',
                'slug' => 'perfumes',
                'image' => 'https://picsum.photos/300/200?random=9',
                'parent_id' => null,
                'children' => [
                    [
                        'name' => 'عطور نسائية',
                        'slug' => 'women-perfumes',
                        'image' => 'https://picsum.photos/300/200?random=10',
                    ],
                    [
                        'name' => 'عطور رجالية',
                        'slug' => 'men-perfumes',
                        'image' => 'https://picsum.photos/300/200?random=11',
                    ]
                ]
            ],
            [
                'name' => 'المكياج',
                'slug' => 'makeup',
                'image' => 'https://picsum.photos/300/200?random=12',
                'parent_id' => null,
                'children' => [
                    [
                        'name' => 'أحمر الشفاه',
                        'slug' => 'lipstick',
                        'image' => 'https://picsum.photos/300/200?random=13',
                    ],
                    [
                        'name' => 'كريم الأساس',
                        'slug' => 'foundation',
                        'image' => 'https://picsum.photos/300/200?random=14',
                    ]
                ]
            ]
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);
            
            $category = Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
            
            foreach ($children as $childData) {
                $childData['parent_id'] = $category->id;
                Category::updateOrCreate(
                    ['slug' => $childData['slug']],
                    $childData
                );
            }
        }
    }
}
