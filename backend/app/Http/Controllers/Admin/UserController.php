<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->orderByDesc('created_at');

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where('email', 'like', '%'.$term.'%');
        }

        return view('admin.users.index', [
            'users' => $query->paginate(25)->withQueryString(),
        ]);
    }
}
