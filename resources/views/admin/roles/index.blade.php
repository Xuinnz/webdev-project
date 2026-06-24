@extends('admin.common.main')

@section('title', 'Roles')

@section('content')
    <h1 class="page-title animate-unicare-in stagger-1">Roles</h1>
    <p class="mb-6 opacity-75 animate-unicare-in stagger-2">Summary of users by role.</p>

    <div class="glass-panel glass-panel--padded animate-unicare-scale-in stagger-3">
        <table class="unicare-table">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>User Count</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($counts as $role => $total)
                    <tr class="animate-unicare-in stagger-{{ $loop->iteration + 1 }}">
                        <td>{{ ucfirst($role) }}</td>
                        <td>{{ $total }}</td>
                        <td>
                            <a href="{{ route('admin.users.index', ['role' => $role]) }}" class="underline text-sm">
                                View users
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
