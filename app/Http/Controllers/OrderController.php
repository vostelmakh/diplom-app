<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

class OrderController extends  Controller
{
    function index(): View {
        return view('order');
    }
}
