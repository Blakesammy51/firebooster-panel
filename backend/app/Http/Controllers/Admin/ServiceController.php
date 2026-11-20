<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('admin.services.index', [
            'services' => Service::query()->latest()->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'platform' => ['required', 'string', 'max:40'],
            'description' => ['required', 'string', 'max:2000'],
            'price_per_1000' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'minimum_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'maximum_quantity' => ['required', 'integer', 'gte:minimum_quantity', 'max:1000000'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        Service::query()->create($data);

        return back()->with('status', 'Service created.');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'platform' => ['required', 'string', 'max:40'],
            'description' => ['required', 'string', 'max:2000'],
            'price_per_1000' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'minimum_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'maximum_quantity' => ['required', 'integer', 'gte:minimum_quantity', 'max:1000000'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $service->update($data);

        return back()->with('status', 'Service updated.');
    }
}
