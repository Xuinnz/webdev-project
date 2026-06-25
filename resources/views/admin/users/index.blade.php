@extends('admin.common.main')

@section('title', 'Users')

@section('content')
    <h1 class="page-title animate-unicare-in stagger-1">Users</h1>

    @if (!empty($roleFilter))
        <p class="mb-4 text-sm opacity-75 animate-unicare-in stagger-2">
            Showing {{ ucfirst($roleFilter) }} users only.
            <a href="{{ route('admin.users.index') }}" class="underline">Clear filter</a>
        </p>
    @endif

    <div class="glass-panel glass-panel--padded animate-unicare-scale-in stagger-2 overflow-x-auto">
        @if ($users->isEmpty())
            <p class="text-sm opacity-75 py-4">No users found.</p>
        @else
            <table class="unicare-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>UUID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Phone</th>
                        <th>Gender</th>
                        <th>Avatar URL</th>
                        <th>Active</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $index => $user)
                        <tr class="animate-unicare-in stagger-{{ min($index + 1, 8) }}">
                            <td>{{ $user->id }}</td>
                            <td class="text-xs">{{ $user->uuid }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ ucfirst($user->role) }}</td>
                            <td>{{ $user->phone ?? '—' }}</td>
                            <td>{{ $user->gender ? ucfirst($user->gender) : '—' }}</td>
                            <td>
                                @if ($user->avatar_url)
                                    <a href="{{ $user->avatar_url }}" target="_blank" rel="noopener" class="underline text-xs">
                                        {{ Str::limit($user->avatar_url, 30) }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $user->is_active ? 'Yes' : 'No' }}</td>
                            <td>{{ $user->created_at }}</td>
                            <td>{{ $user->updated_at }}</td>
                            <td>
                                <a
                                    href="{{ route('admin.users.edit', ['id' => $user->id, 'role' => $roleFilter]) }}"
                                    class="doctor-edit-btn"
                                    title="Edit user"
                                >
                                    &#9998;
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
