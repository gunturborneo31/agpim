@extends('layouts.app', ['title' => 'AGPIM • Login'])

@section('content')
<div class="mx-auto max-w-xl">
    <x-ui.panel>
        <x-ui.section-heading eyebrow="Autentikasi" title="Masuk ke AGPIM" subtitle="Gunakan akun yang sudah terdaftar pada data pengguna." />

        <form method="POST" action="{{ route('login.store') }}" class="mt-5 space-y-3 md:mt-6 md:space-y-4">
            @csrf
            <label class="form-field block">
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </label>

            <label class="form-field block">
                <span>Password</span>
                <input type="password" name="password" required autocomplete="current-password">
            </label>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 bg-white text-blue-700 focus:ring-blue-200">
                Ingat saya
            </label>

            <button type="submit" class="btn-primary w-full">Masuk</button>
        </form>
    </x-ui.panel>
</div>
@endsection
