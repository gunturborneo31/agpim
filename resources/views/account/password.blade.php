@extends('layouts.app', ['title' => 'AGPIM • Ganti Password'])

@section('content')
<div class="mx-auto max-w-xl">
    <x-ui.panel>
        <x-ui.section-heading eyebrow="Akun" title="Ganti Password" subtitle="Perbarui password akun Anda secara berkala untuk menjaga keamanan." />

        <form method="POST" action="{{ route('account.password.update') }}" class="mt-5 space-y-3 md:mt-6 md:space-y-4">
            @csrf
            @method('PUT')

            <label class="form-field block">
                <span>Password Saat Ini</span>
                <input type="password" name="current_password" required autocomplete="current-password">
                @error('current_password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </label>

            <label class="form-field block">
                <span>Password Baru</span>
                <input type="password" name="password" required autocomplete="new-password">
                @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </label>

            <label class="form-field block">
                <span>Konfirmasi Password Baru</span>
                <input type="password" name="password_confirmation" required autocomplete="new-password">
            </label>

            <button type="submit" class="btn-primary w-full">Simpan Password Baru</button>
        </form>
    </x-ui.panel>
</div>
@endsection
