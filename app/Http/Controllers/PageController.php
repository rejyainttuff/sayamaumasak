<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function home()
    {
        return view('home');
    }

    public function explore()
    {
        $foods = $this->getCulinaryData();

        return view('explore', compact('foods'));
    }

    public function foodDetail($slug = null)
    {
        $foods = $this->getCulinaryData();
        $food = collect($foods)->firstWhere('slug', $slug) ?? $foods[0];

        return view('food-detail', compact('food', 'foods'));
    }

    public function recipe()
    {
        return view('recipe');
    }

    public function stories()
    {
        return view('stories');
    }

    public function about()
    {
        return view('about');
    }

    private function getCulinaryData()
    {
        return [
            [
                'id' => 1,
                'slug' => 'ayam-betutu-gilimanuk',
                'name' => 'Ayam Betutu Gilimanuk',
                'region' => 'Jembrana',
                'description' => 'Ayam utuh yang dibumbui base genep khas Bali dan dimasak perlahan hingga empuk serta kaya rempah.',
                'image' => 'images/ayam-betutu.jpg',
            ],
            [
                'id' => 2,
                'slug' => 'nasi-campur-bali',
                'name' => 'Nasi Campur Bali',
                'region' => 'Denpasar',
                'description' => 'Sepiring nasi hangat dengan lawar, sate lilit, ayam suwir, sambal matah, dan lauk khas Bali.',
                'image' => 'images/nasi-campur.jpg',
            ],
            [
                'id' => 3,
                'slug' => 'sate-lilit-ikan',
                'name' => 'Sate Lilit Ikan',
                'region' => 'Gianyar',
                'description' => 'Olahan daging ikan cincang yang dipadukan dengan kelapa parut dan bumbu Bali, dililitkan pada batang serai.',
                'image' => 'images/sate-lilit.jpg',
            ],
            [
                'id' => 4,
                'slug' => 'lawar-bali',
                'name' => 'Lawar Bali',
                'region' => 'Badung',
                'description' => 'Campuran sayuran, kelapa parut, dan daging cincang berbumbu rempah segar khas tradisi kuliner Bali.',
                'image' => 'images/lawar.jpg',
            ],
        ];
    }
}
