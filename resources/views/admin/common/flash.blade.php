@if (session('success'))
    <div class="mb-4 rounded-xl bg-green-100 px-4 py-3 text-sm text-green-800 animate-unicare-in">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-xl bg-red-100 px-4 py-3 text-sm text-red-800 animate-unicare-in">
        @if ($errors->has('error'))
            <p>{{ $errors->first('error') }}</p>
        @else
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
