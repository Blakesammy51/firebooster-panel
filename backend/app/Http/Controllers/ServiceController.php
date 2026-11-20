<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('services.index', [
            'services' => Service::query()
                ->where('is_active', true)
                ->orderBy('platform')
                ->orderBy('name')
                ->paginate(15),
        ]);
    }
}
