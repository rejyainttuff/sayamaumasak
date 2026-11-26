<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Food;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BaliCulinarySeeder extends Seeder
{
    public function run(): void
    {
        $categoriesData = [
            'Hidangan Utama',
            'Sate & Olahan Daging',
            'Sayur & Pelengkap',
            'Jajanan & Kue Tradisional',
            'Minuman Khas',
        ];

        $categories = [];
        foreach ($categoriesData as $catName) {
            $categories[$catName] = Category::firstOrCreate(
                ['slug' => Str::slug($catName)],
                ['name' => $catName]
            );
        }

        $ingredientNames = [
            'Base Genep',
            'Daging Ayam',
            'Daging Ikan Laut',
            'Kelapa Parut',
            'Kacang Panjang',
            'Kangkung',
            'Gula Merah',
            'Cincau Hijau',
            'Santan',
            'Daun Pisang',
            'Jeruk Limau',
            'Cabai Rawit',
            'Bawang Merah',
            'Kunyit',
            'Serai',
        ];

        $ingredients = [];
        foreach ($ingredientNames as $ingName) {
            $ingredients[$ingName] = Ingredient::firstOrCreate(
                ['name' => $ingName]
            );
        }

        $foodsData = [
            [
                'category' => 'Hidangan Utama',
                'name' => 'Ayam Betutu Gilimanuk',
                'origin_area' => 'Jembrana',
                'short_description' => 'Ayam berbumbu base genep kaya rempah yang dimasak perlahan hingga empuk dan meresap.',
                'thumbnail' => null,
                'recipe' => [
                    'title' => 'Resep Ayam Betutu Gilimanuk Bumbu Genep',
                    'prep_time_minutes' => 30,
                    'cook_time_minutes' => 90,
                    'servings' => 4,
                    'difficulty' => 'sedang',
                    'approval_status' => 'approved',
                    'ingredients' => [
                        ['name' => 'Daging Ayam', 'amount' => '1', 'unit' => 'ekor', 'notes' => 'Potong sesuai selera'],
                        ['name' => 'Base Genep', 'amount' => '150', 'unit' => 'gram', 'notes' => 'Tumis hingga harum'],
                        ['name' => 'Serai', 'amount' => '2', 'unit' => 'batang', 'notes' => 'Memarkan'],
                        ['name' => 'Daun Pisang', 'amount' => '4', 'unit' => 'lembar', 'notes' => 'Untuk melapisi saat mengukus'],
                    ],
                    'steps' => [
                        ['step_number' => 1, 'instruction' => 'Bersihkan daging ayam dan lumuri dengan air jeruk limau agar tidak amis.'],
                        ['step_number' => 2, 'instruction' => 'Campurkan base genep dan serai memar, lalu balurkan merata ke seluruh bagian ayam.'],
                        ['step_number' => 3, 'instruction' => 'Bungkus atau alasi daun pisang lalu masak dengan api kecil hingga daging empuk dan bumbu meresap.'],
                    ],
                ],
            ],
            [
                'category' => 'Sate & Olahan Daging',
                'name' => 'Sate Lilit Ikan',
                'origin_area' => 'Klungkung',
                'short_description' => 'Adonan ikan cincang yang dicampur kelapa parut dan bumbu Bali, dililitkan pada batang serai lalu dipanggang.',
                'thumbnail' => null,
                'recipe' => [
                    'title' => 'Resep Sate Lilit Ikan Khas Klungkung',
                    'prep_time_minutes' => 25,
                    'cook_time_minutes' => 20,
                    'servings' => 4,
                    'difficulty' => 'mudah',
                    'approval_status' => 'approved',
                    'ingredients' => [
                        ['name' => 'Daging Ikan Laut', 'amount' => '500', 'unit' => 'gram', 'notes' => 'Giling halus'],
                        ['name' => 'Kelapa Parut', 'amount' => '100', 'unit' => 'gram', 'notes' => 'Kelapa agak muda'],
                        ['name' => 'Base Genep', 'amount' => '100', 'unit' => 'gram', 'notes' => null],
                        ['name' => 'Serai', 'amount' => '15', 'unit' => 'batang', 'notes' => 'Sebagai tusukan lilit'],
                    ],
                    'steps' => [
                        ['step_number' => 1, 'instruction' => 'Aduk rata daging ikan giling, kelapa parut, dan base genep hingga tercampur rata.'],
                        ['step_number' => 2, 'instruction' => 'Ambil secukupnya adonan, kepal dan lilitkan pada bagian ujung batang serai.'],
                        ['step_number' => 3, 'instruction' => 'Panggang di atas bara atau pan sambil sesekali diolesi sedikit minyak hingga matang kecokelatan.'],
                    ],
                ],
            ],
            [
                'category' => 'Sayur & Pelengkap',
                'name' => 'Lawar Kuwir',
                'origin_area' => 'Gianyar',
                'short_description' => 'Hidangan sayur dan daging suwir khas Gianyar dengan paduan kelapa, bumbu Bali, serta perasan jeruk limau.',
                'thumbnail' => null,
                'recipe' => [
                    'title' => 'Resep Lawar Kuwir Khas Gianyar',
                    'prep_time_minutes' => 30,
                    'cook_time_minutes' => 35,
                    'servings' => 4,
                    'difficulty' => 'sedang',
                    'approval_status' => 'approved',
                    'ingredients' => [
                        ['name' => 'Kacang Panjang', 'amount' => '200', 'unit' => 'gram', 'notes' => 'Iris tipis lalu rebus sebentar'],
                        ['name' => 'Kelapa Parut', 'amount' => '150', 'unit' => 'gram', 'notes' => null],
                        ['name' => 'Base Genep', 'amount' => '100', 'unit' => 'gram', 'notes' => null],
                        ['name' => 'Jeruk Limau', 'amount' => '2', 'unit' => 'buah', 'notes' => 'Ambil air perasannya'],
                    ],
                    'steps' => [
                        ['step_number' => 1, 'instruction' => 'Rebus kacang panjang sebentar lalu tiriskan agar tetap renyah dan segar.'],
                        ['step_number' => 2, 'instruction' => 'Campurkan base genep dengan kelapa parut hingga bumbu merata.'],
                        ['step_number' => 3, 'instruction' => 'Satukan kacang panjang dan campuran bumbu, beri perasan jeruk limau, lalu aduk rata sebelum disajikan.'],
                    ],
                ],
            ],
            [
                'category' => 'Hidangan Utama',
                'name' => 'Nasi Jinggo',
                'origin_area' => 'Denpasar',
                'short_description' => 'Porsi nasi hangat dibungkus daun pisang dengan lauk suwir, sambal pedas, dan pelengkap khas Denpasar.',
                'thumbnail' => null,
                'recipe' => [
                    'title' => 'Resep Nasi Jinggo Rumahan Khas Denpasar',
                    'prep_time_minutes' => 20,
                    'cook_time_minutes' => 30,
                    'servings' => 4,
                    'difficulty' => 'mudah',
                    'approval_status' => 'approved',
                    'ingredients' => [
                        ['name' => 'Daging Ayam', 'amount' => '300', 'unit' => 'gram', 'notes' => 'Rebus lalu suwir'],
                        ['name' => 'Cabai Rawit', 'amount' => '25', 'unit' => 'buah', 'notes' => 'Untuk sambal'],
                        ['name' => 'Bawang Merah', 'amount' => '10', 'unit' => 'siung', 'notes' => null],
                        ['name' => 'Daun Pisang', 'amount' => '4', 'unit' => 'lembar', 'notes' => 'Untuk membungkus nasi'],
                    ],
                    'steps' => [
                        ['step_number' => 1, 'instruction' => 'Suwir daging ayam rebus lalu bumbui gurih pedas.'],
                        ['step_number' => 2, 'instruction' => 'Haluskan cabai rawit dan bawang merah, kemudian tumis menjadi sambal.'],
                        ['step_number' => 3, 'instruction' => 'Tata nasi hangat, ayam suwir, dan sambal di atas daun pisang lalu bungkus rapi.'],
                    ],
                ],
            ],
            [
                'category' => 'Hidangan Utama',
                'name' => 'Tipat Blayag',
                'origin_area' => 'Buleleng',
                'short_description' => 'Tipat khas Buleleng yang disajikan dengan kuah kental berbumbu rempah serta sayuran pelengkap.',
                'thumbnail' => null,
                'recipe' => [
                    'title' => 'Resep Tipat Blayag Kuah Kental Buleleng',
                    'prep_time_minutes' => 25,
                    'cook_time_minutes' => 45,
                    'servings' => 4,
                    'difficulty' => 'sedang',
                    'approval_status' => 'approved',
                    'ingredients' => [
                        ['name' => 'Daging Ayam', 'amount' => '400', 'unit' => 'gram', 'notes' => 'Potong dan suwir'],
                        ['name' => 'Santan', 'amount' => '500', 'unit' => 'ml', 'notes' => null],
                        ['name' => 'Kunyit', 'amount' => '3', 'unit' => 'ruas', 'notes' => 'Haluskan bersama bumbu'],
                        ['name' => 'Kangkung', 'amount' => '1', 'unit' => 'ikat', 'notes' => 'Rebus dan tiriskan'],
                    ],
                    'steps' => [
                        ['step_number' => 1, 'instruction' => 'Tumis bumbu halus bersama kunyit hingga matang dan harum.'],
                        ['step_number' => 2, 'instruction' => 'Tuang santan dan masukkan ayam, masak dengan api sedang sambil diaduk hingga kuah mengental.'],
                        ['step_number' => 3, 'instruction' => 'Potong tipat, tambahkan kangkung dan suwiran ayam, lalu siram dengan kuah blayag.'],
                    ],
                ],
            ],
            [
                'category' => 'Jajanan & Kue Tradisional',
                'name' => 'Jaja Laklak',
                'origin_area' => 'Tabanan',
                'short_description' => 'Kue tradisional berpori dari adonan tepung dan santan yang dipanggang lalu disajikan dengan gula merah cair.',
                'thumbnail' => null,
                'recipe' => [
                    'title' => 'Resep Jaja Laklak Gula Merah Khas Tabanan',
                    'prep_time_minutes' => 20,
                    'cook_time_minutes' => 25,
                    'servings' => 6,
                    'difficulty' => 'mudah',
                    'approval_status' => 'approved',
                    'ingredients' => [
                        ['name' => 'Santan', 'amount' => '400', 'unit' => 'ml', 'notes' => null],
                        ['name' => 'Gula Merah', 'amount' => '150', 'unit' => 'gram', 'notes' => 'Sisir halus untuk kuah kinca'],
                        ['name' => 'Kelapa Parut', 'amount' => '100', 'unit' => 'gram', 'notes' => 'Kukus sebentar'],
                    ],
                    'steps' => [
                        ['step_number' => 1, 'instruction' => 'Rebus gula merah dengan sedikit air hingga larut dan sedikit mengental, lalu saring.'],
                        ['step_number' => 2, 'instruction' => 'Panaskan cetakan kecil, tuang adonan hingga berpori dan matang.'],
                        ['step_number' => 3, 'instruction' => 'Sajikan laklak dengan taburan kelapa parut dan siraman gula merah cair.'],
                    ],
                ],
            ],
            [
                'category' => 'Minuman Khas',
                'name' => 'Es Daluman',
                'origin_area' => 'Badung',
                'short_description' => 'Minuman penyegar berbahan cincau hijau dengan kuah santan dan gula merah yang manis gurih.',
                'thumbnail' => null,
                'recipe' => [
                    'title' => 'Resep Es Daluman Segar Khas Badung',
                    'prep_time_minutes' => 15,
                    'cook_time_minutes' => 15,
                    'servings' => 4,
                    'difficulty' => 'mudah',
                    'approval_status' => 'approved',
                    'ingredients' => [
                        ['name' => 'Cincau Hijau', 'amount' => '250', 'unit' => 'gram', 'notes' => 'Potong dadu'],
                        ['name' => 'Santan', 'amount' => '300', 'unit' => 'ml', 'notes' => 'Masak hingga matang lalu dinginkan'],
                        ['name' => 'Gula Merah', 'amount' => '150', 'unit' => 'gram', 'notes' => 'Larutkan dengan sedikit air'],
                    ],
                    'steps' => [
                        ['step_number' => 1, 'instruction' => 'Rebus larutan gula merah hingga mendidih dan sedikit mengental, lalu biarkan dingin.'],
                        ['step_number' => 2, 'instruction' => 'Masukkan potongan cincau hijau ke dalam gelas saji.'],
                        ['step_number' => 3, 'instruction' => 'Tambahkan santan, sirup gula merah, dan es batu secukupnya sebelum disajikan.'],
                    ],
                ],
            ],
        ];

        foreach ($foodsData as $item) {
            $category = $categories[$item['category']];
            $foodSlug = Str::slug($item['name']);

            $food = Food::firstOrCreate(
                ['slug' => $foodSlug],
                [
                    'category_id' => $category->id,
                    'name' => $item['name'],
                    'origin_area' => $item['origin_area'],
                    'short_description' => $item['short_description'],
                    'thumbnail' => $item['thumbnail'],
                ]
            );

            $recipeData = $item['recipe'];
            $recipe = Recipe::firstOrCreate(
                [
                    'food_id' => $food->id,
                    'title' => $recipeData['title'],
                ],
                [
                    'prep_time_minutes' => $recipeData['prep_time_minutes'],
                    'cook_time_minutes' => $recipeData['cook_time_minutes'],
                    'servings' => $recipeData['servings'],
                    'difficulty' => $recipeData['difficulty'],
                    'approval_status' => $recipeData['approval_status'],
                ]
            );

            foreach ($recipeData['ingredients'] as $ingItem) {
                $ingredient = $ingredients[$ingItem['name']];
                RecipeIngredient::firstOrCreate(
                    [
                        'recipe_id' => $recipe->id,
                        'ingredient_id' => $ingredient->id,
                    ],
                    [
                        'amount' => $ingItem['amount'],
                        'unit' => $ingItem['unit'],
                        'notes' => $ingItem['notes'],
                    ]
                );
            }

            foreach ($recipeData['steps'] as $stepItem) {
                RecipeStep::firstOrCreate(
                    [
                        'recipe_id' => $recipe->id,
                        'step_number' => $stepItem['step_number'],
                    ],
                    [
                        'instruction' => $stepItem['instruction'],
                        'image' => null,
                    ]
                );
            }
        }
    }
}