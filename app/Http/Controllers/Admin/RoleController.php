<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index()
    {
        $rawCounts = DB::table('users')
            ->select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        $counts = collect(['patient', 'doctor', 'admin'])
            ->mapWithKeys(fn (string $role) => [$role => (int) ($rawCounts[$role] ?? 0)]);

        return view('admin.roles.index', compact('counts'));
    }
}
