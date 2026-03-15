<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FacebookPage;


class DashboardController extends Controller
{
    public function index()
    {

        $pagesCount = FacebookPage::count();

        return view('dashboard',[
            'connected' => $pagesCount > 0,
            'pagesCount' => $pagesCount
        ]);

    }
}
