<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('users')->orderByDesc('created_at');

        if ($request->filled('role') && in_array($request->role, ['patient', 'doctor', 'admin'], true)) {
            $query->where('role', $request->role);
        }

        $users = $query->get();
        $roleFilter = $request->role;

        return view('admin.users.index', compact('users', 'roleFilter'));
    }

    public function edit($id)
    {
        $user = DB::table('users')->where('id', $id)->first();

        if (!$user) {
            abort(404);
        }

        $roleFilter = request('role');

        return view('admin.users.edit', compact('user', 'roleFilter'));
    }

    public function update(Request $request, $id)
    {
        $user = DB::table('users')->where('id', $id)->first();

        if (!$user) {
            abort(404);
        }

        $request->merge([
            'avatar_url' => $request->input('avatar_url') ?: null,
            'phone' => $request->input('phone') ?: null,
            'gender' => $request->input('gender') ?: null,
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'role' => 'required|in:patient,doctor,admin',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female,other',
            'avatar_url' => 'nullable|url|max:500',
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:8',
        ]);

        try {
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'phone' => $validated['phone'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'avatar_url' => $validated['avatar_url'] ?? null,
                'is_active' => $request->boolean('is_active'),
                'updated_at' => now(),
            ];

            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            DB::table('users')->where('id', $id)->update($updateData);

            return redirect()
                ->route('admin.users.index', $request->only('role'))
                ->with('success', 'User has been successfully updated.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to update user. Please try again.'])
                ->withInput();
        }
    }
}
