@extends('admin.common.main')

@section('title', 'Edit User')

@section('content')
    <h1 class="page-title animate-unicare-in stagger-1">Edit User</h1>

    <p class="mb-4 animate-unicare-in stagger-2">
        <a href="{{ route('admin.users.index', $roleFilter ? ['role' => $roleFilter] : []) }}" class="underline text-sm opacity-75">
            &larr; Back to Users
        </a>
    </p>

    <form
        action="{{ route('admin.users.update', ['id' => $user->id, 'role' => $roleFilter]) }}"
        method="POST"
        class="glass-panel glass-panel--padded animate-unicare-scale-in stagger-2"
    >
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="id" class="form-label">ID</label>
                <input type="text" id="id" class="form-input w-full opacity-60" value="{{ $user->id }}" disabled>
            </div>
            <div>
                <label for="uuid" class="form-label">UUID</label>
                <input type="text" id="uuid" class="form-input w-full opacity-60" value="{{ $user->uuid }}" disabled>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="name" class="form-label">Name</label>
                <input type="text" id="name" name="name" class="form-input w-full" value="{{ old('name', $user->name) }}" required>
                @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-input w-full" value="{{ old('email', $user->email) }}" required>
                @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="role" class="form-label">Role</label>
                <select id="role" name="role" class="form-select w-full" required>
                    @foreach (['patient', 'doctor', 'admin'] as $role)
                        <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
                @error('role')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="phone" class="form-label">Phone</label>
                <input type="text" id="phone" name="phone" class="form-input w-full" value="{{ old('phone', $user->phone) }}">
                @error('phone')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="gender" class="form-label">Gender</label>
                <select id="gender" name="gender" class="form-select w-full">
                    <option value="">—</option>
                    @foreach (['male', 'female', 'other'] as $gender)
                        <option value="{{ $gender }}" @selected(old('gender', $user->gender) === $gender)>{{ ucfirst($gender) }}</option>
                    @endforeach
                </select>
                @error('gender')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="avatar_url" class="form-label">Avatar URL</label>
                <input type="url" id="avatar_url" name="avatar_url" class="form-input w-full" value="{{ old('avatar_url', $user->avatar_url) }}">
                @error('avatar_url')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-input w-full" placeholder="Leave blank to keep current">
                @error('password')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" class="rounded" @checked(old('is_active', $user->is_active))>
                    <span class="form-label mb-0">Active</span>
                </label>
                @error('is_active')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label for="created_at" class="form-label">Created At</label>
                <input type="text" id="created_at" class="form-input w-full opacity-60" value="{{ $user->created_at }}" disabled>
            </div>
            <div>
                <label for="updated_at" class="form-label">Updated At</label>
                <input type="text" id="updated_at" class="form-input w-full opacity-60" value="{{ $user->updated_at }}" disabled>
            </div>
        </div>

        <button type="submit" class="unicare-btn-primary" style="margin-top: 1rem;">Save User</button>
    </form>
@endsection
