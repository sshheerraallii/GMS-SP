<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;

class ReportsHomeController extends Controller
{
    public function index()
    {
        return view('reports.home');
    }
}
