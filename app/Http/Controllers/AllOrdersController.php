<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

class AllOrdersController extends Controller
{
    function index(): View {
        return view('allOrders');
    }
}
