@extends('layouts.app', ['title' => 'AGPIM • Daftar User'])

@section('content')
<x-ui.panel>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-ui.section-heading eyebrow="Manajemen User" title="Daftar Pengguna AGPIM" />
        <span class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-600">{{ $users->count() }} user</span>
    </div>

    <div class="mt-5 overflow-x-auto rounded-[1.5rem] border border-slate-200 bg-white md:mt-6">
        <table class="min-w-full text-sm text-slate-700">
            <thead>
                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-[0.15em] text-slate-500">
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">OPD</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ $user->role->label() }}</td>
                        <td class="px-4 py-3">{{ $user->opd?->name ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-ui.panel>
@endsection
