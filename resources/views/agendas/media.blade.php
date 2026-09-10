@php
    $isPopupMode = request()->query('popup') === '1';
@endphp

@if ($isPopupMode)
    <style>
        body > header.glass-header,
        body > div.fixed.bottom-4.left-1\/2.z-40 {
            display: none !important;
        }

        body {
            background: #f8fafc;
        }

        body > div.mx-auto.max-w-7xl.px-4.py-6.pb-28 {
            max-width: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        main {
            padding: 0 !important;
        }
    </style>
@endif

@extends('layouts.app', ['title' => 'AGPIM • Management File'])

@section('content')
@php
    $typeMeta = [
        'all' => [
            'title' => 'Management File Agenda',
            'description' => 'Kelola seluruh file agenda, baik bahan mentah maupun hasil editing.',
            'addDescription' => 'Upload file ke agenda ini.',
            'badgeClass' => 'bg-slate-100 text-slate-700',
            'shortLabel' => 'FILE',
            'label' => 'Semua',
        ],
        'photo' => [
            'title' => 'Bahan Mentah Foto',
            'description' => 'Daftar file bahan mentah foto agenda.',
            'addDescription' => 'Upload bahan mentah foto ke agenda ini.',
            'badgeClass' => 'bg-amber-100 text-amber-700',
            'shortLabel' => 'IMG',
            'label' => 'Foto',
        ],
        'video' => [
            'title' => 'Bahan Mentah Video',
            'description' => 'Daftar file bahan mentah video agenda.',
            'addDescription' => 'Upload bahan mentah video ke agenda ini.',
            'badgeClass' => 'bg-emerald-100 text-emerald-700',
            'shortLabel' => 'VID',
            'label' => 'Video',
        ],
        'berita' => [
            'title' => 'Hasil Editing Berita',
            'description' => 'Daftar file hasil editing untuk berita agenda.',
            'addDescription' => 'Upload file hasil editing berita ke agenda ini.',
            'badgeClass' => 'bg-sky-100 text-sky-700',
            'shortLabel' => 'BRT',
            'label' => 'Berita',
        ],
        'konten' => [
            'title' => 'Hasil Editing Konten',
            'description' => 'Daftar file hasil editing untuk konten agenda.',
            'addDescription' => 'Upload file hasil editing konten ke agenda ini.',
            'badgeClass' => 'bg-violet-100 text-violet-700',
            'shortLabel' => 'KTN',
            'label' => 'Konten',
        ],
    ];
    $currentMeta = $typeMeta['all'];
    if (is_string($activeType) && array_key_exists($activeType, $typeMeta)) {
        $currentMeta = $typeMeta[$activeType];
    }
@endphp
<x-ui.panel>
    <div x-data="{ modal: @js(old('modal', '')), documentIds: @js($documents->pluck('id')->values()), selectedIds: @js(collect(old('document_ids', []))->map(fn ($id) => (string) $id)->values()), toggleAll(event) { this.selectedIds = event.target.checked ? this.documentIds.map(String) : []; } }" x-init="$watch('modal', value => document.body.classList.toggle('popup-open', Boolean(value)))" @keydown.escape.window="modal = ''">
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50/70 px-5 py-4 md:px-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Media Agenda</p>
                    <h1 class="mt-1 text-xl font-semibold text-slate-900">{{ $agenda->title }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $currentMeta['description'] }}</p>
                </div>
                @if (! $isPopupMode)
                    <div class="flex items-center gap-2">
                        <a href="{{ route('agendas.index') }}" class="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                            Kembali
                        </a>
                    </div>
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="mx-5 mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700 md:mx-6">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mx-5 mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 md:mx-6">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="px-5 py-5 md:px-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('agendas.media.index', ['agenda' => $agenda]) }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ $activeType === 'all' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50' }}">Semua</a>
                    <a href="{{ route('agendas.media.index', ['agenda' => $agenda, 'type' => 'photo']) }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ $activeType === 'photo' ? 'bg-amber-500 text-white shadow-sm' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50' }}">Foto</a>
                    <a href="{{ route('agendas.media.index', ['agenda' => $agenda, 'type' => 'video']) }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ $activeType === 'video' ? 'bg-emerald-500 text-white shadow-sm' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50' }}">Video</a>
                    <a href="{{ route('agendas.media.index', ['agenda' => $agenda, 'type' => 'berita']) }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ $activeType === 'berita' ? 'bg-sky-500 text-white shadow-sm' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50' }}">Berita</a>
                    <a href="{{ route('agendas.media.index', ['agenda' => $agenda, 'type' => 'konten']) }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ $activeType === 'konten' ? 'bg-violet-500 text-white shadow-sm' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50' }}">Konten</a>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-sm text-slate-500"><span x-text="selectedIds.length"></span> dipilih dari {{ $documents->count() }} file</p>
                    <button type="button" @click="modal = 'bulk-edit'" :disabled="selectedIds.length === 0" class="rounded-full border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Ubah Terpilih</button>
                    <button type="button" @click="modal = 'bulk-delete'" :disabled="selectedIds.length === 0" class="rounded-full border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50">Hapus Terpilih</button>
                    <button type="button" @click="modal = 'add'" class="inline-flex items-center rounded-full bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">Tambah File</button>
                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                <div class="hidden grid-cols-15 gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-[0.15em] text-slate-500 md:grid">
                    <div class="col-span-1">
                        <input type="checkbox" @change="toggleAll($event)" :checked="documentIds.length > 0 && selectedIds.length === documentIds.length" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    </div>
                    <div class="col-span-5">File</div>
                    <div class="col-span-2">Jenis</div>
                    <div class="col-span-3">Keterangan</div>
                    <div class="col-span-2">Sumber</div>
                    <div class="col-span-2 text-right">Aksi</div>
                </div>

                @forelse ($documents as $document)
                    @php($documentMeta = $typeMeta[$document->category] ?? $typeMeta['all'])
                    <div class="border-b border-slate-200 px-4 py-4 last:border-b-0">
                        <div class="grid gap-4 md:grid-cols-15 md:items-start">
                            <div class="md:col-span-1">
                                <input type="checkbox" x-model="selectedIds" value="{{ (string) $document->id }}" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            </div>
                            <div class="md:col-span-5">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $documentMeta['badgeClass'] }} text-xs font-bold uppercase">
                                        {{ $documentMeta['shortLabel'] }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ basename($document->file_path) }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $document->created_at?->format('d M Y H:i') }}</p>
                                        <a href="{{ asset('storage/'.$document->file_path) }}" target="_blank" class="mt-1 inline-block text-xs font-semibold text-blue-700 hover:text-blue-800">Buka file</a>
                                    </div>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $documentMeta['badgeClass'] }}">
                                    {{ $documentMeta['label'] }}
                                </span>
                            </div>
                            <div class="md:col-span-3">
                                <p class="text-sm text-slate-600">{{ $document->description ?: '-' }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-sm text-slate-600">{{ $document->source_from ?: '-' }}</p>
                            </div>
                            <div class="md:col-span-2 md:text-right">
                                <button type="button" @click="modal = 'edit-{{ $document->id }}'" class="rounded-full border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</button>

                                <form method="POST" action="{{ route('agendas.media.destroy', [$agenda, $document]) }}" onsubmit="return confirm('Hapus file ini?')" class="mt-2 inline-block md:mt-1 md:ml-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-full border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Hapus</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-sm text-slate-500">Belum ada file pada filter ini.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div x-cloak x-show="modal === 'add'" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/45 p-4" @click.self="modal = ''">
        <div class="card-panel max-h-[90vh] w-full max-w-2xl overflow-y-auto p-5 md:p-6">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Tambah File</h2>
                    <p class="text-sm text-slate-500">{{ $currentMeta['addDescription'] }}</p>
                </div>
                <button type="button" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:text-slate-700" @click="modal = ''">✕</button>
            </div>

            <form method="POST" action="{{ route('agendas.media.store', $agenda) }}" enctype="multipart/form-data" class="space-y-4" x-data="{ selectedCategory: @js(in_array($activeType, ['photo', 'video', 'berita', 'konten'], true) ? $activeType : old('category', 'photo')) }">
                @csrf
                <input type="hidden" name="modal" value="add">
                @php($lockedCategory = in_array($activeType, ['photo', 'video', 'berita', 'konten'], true) ? $activeType : null)
                <div class="grid gap-4">
                    @if ($lockedCategory)
                        <input type="hidden" name="category" value="{{ $lockedCategory }}">
                        <input type="hidden" x-model="selectedCategory" value="{{ $lockedCategory }}">
                    @else
                        <label class="form-field">
                            <span>Jenis file *</span>
                            <select name="category" required x-model="selectedCategory">
                                <option value="photo" @selected(old('category') === 'photo')>Foto</option>
                                <option value="video" @selected(old('category') === 'video')>Video</option>
                                <option value="berita" @selected(old('category') === 'berita')>Berita</option>
                                <option value="konten" @selected(old('category') === 'konten')>Konten</option>
                            </select>
                        </label>
                    @endif
                </div>

                <label class="block rounded-xl border border-dashed border-slate-300 bg-white px-4 py-4 text-sm text-slate-600">
                    <span class="font-medium">Pilih file</span>
                    <input type="file" name="files[]" multiple :accept="selectedCategory === 'video' ? 'video/*' : (selectedCategory === 'photo' ? 'image/*' : '*')" required class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                </label>

                <label class="form-field">
                    <span>Sumber file (dari siapa)</span>
                    <input type="text" name="source_from" value="{{ old('source_from') }}" placeholder="Contoh: Humas OPD A">
                </label>

                <label class="form-field">
                    <span>Keterangan</span>
                    <textarea name="description" rows="3" placeholder="Isi keterangan file">{{ old('description') }}</textarea>
                </label>

                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-secondary" @click="modal = ''">Batal</button>
                    <button type="submit" class="btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <div x-cloak x-show="modal === 'bulk-edit'" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/45 p-4" @click.self="modal = ''">
        <div class="card-panel max-h-[90vh] w-full max-w-xl overflow-y-auto p-5 md:p-6">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Ubah Banyak File</h2>
                    <p class="text-sm text-slate-500">Ubah sumber file dan keterangan untuk banyak file sekaligus.</p>
                </div>
                <button type="button" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:text-slate-700" @click="modal = ''">✕</button>
            </div>

            <form method="POST" action="{{ route('agendas.media.bulk-update', $agenda) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="modal" value="bulk-edit">
                <template x-for="id in selectedIds" :key="'bulk-edit-' + id">
                    <input type="hidden" name="document_ids[]" :value="id">
                </template>

                <p class="rounded-xl bg-slate-100 px-3 py-2 text-sm text-slate-600">
                    <span class="font-semibold" x-text="selectedIds.length"></span> file dipilih.
                </p>

                <label class="form-field">
                    <span>Sumber file (dari siapa)</span>
                    <input type="text" name="source_from" value="{{ old('modal') === 'bulk-edit' ? old('source_from') : '' }}" placeholder="Contoh: Humas OPD A">
                </label>
                <label class="form-field">
                    <span>Keterangan</span>
                    <textarea name="description" rows="4" placeholder="Keterangan yang akan diterapkan ke file terpilih">{{ old('modal') === 'bulk-edit' ? old('description') : '' }}</textarea>
                </label>

                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-secondary" @click="modal = ''">Batal</button>
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <div x-cloak x-show="modal === 'bulk-delete'" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/45 p-4" @click.self="modal = ''">
        <div class="card-panel max-h-[90vh] w-full max-w-lg overflow-y-auto p-5 md:p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Hapus Banyak File</h2>
                <button type="button" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:text-slate-700" @click="modal = ''">✕</button>
            </div>

            <p class="text-sm text-slate-600">Anda yakin ingin menghapus <span class="font-semibold" x-text="selectedIds.length"></span> file sekaligus? Tindakan ini tidak bisa dibatalkan.</p>

            <form method="POST" action="{{ route('agendas.media.bulk-destroy', $agenda) }}" class="mt-5 space-y-4" onsubmit="return confirm('Hapus semua file terpilih?')">
                @csrf
                @method('DELETE')
                <input type="hidden" name="modal" value="bulk-delete">
                <template x-for="id in selectedIds" :key="'bulk-delete-' + id">
                    <input type="hidden" name="document_ids[]" :value="id">
                </template>

                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-secondary" @click="modal = ''">Batal</button>
                    <button type="submit" class="rounded-full bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Hapus Terpilih</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($documents as $document)
        <div x-cloak x-show="modal === 'edit-{{ $document->id }}'" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/45 p-4" @click.self="modal = ''">
            <div class="card-panel max-h-[90vh] w-full max-w-xl overflow-y-auto p-5 md:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Ubah File</h2>
                        <p class="text-sm text-slate-500">Perbarui keterangan file.</p>
                    </div>
                    <button type="button" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:text-slate-700" @click="modal = ''">✕</button>
                </div>

                <form method="POST" action="{{ route('agendas.media.update', [$agenda, $document]) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="modal" value="edit-{{ $document->id }}">
                    <label class="form-field">
                        <span>Sumber file (dari siapa)</span>
                        <input type="text" name="source_from" value="{{ old('modal') === 'edit-'.$document->id ? old('source_from') : $document->source_from }}" placeholder="Contoh: Humas OPD A">
                    </label>
                    <label class="form-field">
                        <span>Keterangan</span>
                        <textarea name="description" rows="4" placeholder="Isi keterangan file">{{ old('modal') === 'edit-'.$document->id ? old('description') : $document->description }}</textarea>
                    </label>

                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-secondary" @click="modal = ''">Batal</button>
                        <button type="submit" class="btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
    </div>
</x-ui.panel>
@endsection
