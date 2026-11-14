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
        return view('explore');
    }

    public function foodDetail()
    {
        return view('food-detail');
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
}
