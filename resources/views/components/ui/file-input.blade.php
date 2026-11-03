@props([
    'name',
    'label',
    'accept' => '.pdf,.doc,.docx',
    'required' => false,
    'hint' => 'PDF, DOC, atau DOCX · maksimal 5 MB',
    'maxMb' => 5,
])

{{-- Input file dengan pratinjau isi: PDF tampil langsung, DOCX dirender jadi teks. --}}
<div {{ $attributes->class(['form-field']) }} x-data="filePreview({{ (int) $maxMb }})">
    <span>
        {{ $label }}
        @if ($required)<em class="req">Wajib diisi</em>@endif
    </span>

    {{-- Area pilih / seret file --}}
    <label
        x-show="!file"
        class="relative mt-1.5 flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border border-dashed border-[#cfd6ea] px-4 py-6 text-center transition-colors hover:border-[#8fa3e8]"
        :class="dragging && 'border-[#5a7ef1] soft-blue'"
        style="background-image: linear-gradient(120deg, #f7f9ff 0%, #faf8ff 100%)"
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop="dragging = false"
    >
        <span class="material-symbols-outlined text-[26px] text-[#5a7ef1]">upload_file</span>
        <span class="text-sm font-medium text-ink">Pilih file atau seret ke sini</span>
        <span class="text-xs text-muted">{{ $hint }}</span>
        <input
            x-ref="input"
            type="file"
            name="{{ $name }}"
            accept="{{ $accept }}"
            @if ($required) required @endif
            @change="pick($event)"
            class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
        >
    </label>

    {{-- File terpilih + pratinjau --}}
    <div x-cloak x-show="file" class="mt-1.5 overflow-hidden rounded-lg border border-line bg-white">
        <div class="flex items-center gap-3 px-3 py-2.5">
            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-md text-[11px] font-semibold uppercase"
                  :class="kind === 'pdf' ? 'soft-red text-[#c4321f]' : 'soft-blue text-[#2f4fbf]'"
                  x-text="ext"></span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-ink" x-text="file?.name"></p>
                <p class="text-xs text-muted" x-text="sizeLabel"></p>
            </div>
            <a x-show="url" :href="url" target="_blank" rel="noopener" class="hidden rounded-md px-2.5 py-1.5 text-xs font-medium text-[#2f55d4] hover:bg-hover sm:inline-flex">Buka</a>
            <button type="button" class="rounded-md px-2.5 py-1.5 text-xs font-medium text-ink hover:bg-hover" @click="$refs.input.click()">Ganti</button>
            <button type="button" class="rounded-md px-2.5 py-1.5 text-xs font-medium text-[#c4321f] hover:bg-hover" @click="clear()">Hapus</button>
        </div>

        <div class="border-t border-line bg-canvas">
            <div x-show="kind === 'pdf'" class="max-h-[420px] overflow-y-auto p-3 sm:max-h-[560px]">
                <p x-show="loading" class="px-1 py-2 text-sm text-muted">Menyiapkan pratinjau…</p>
                <div x-ref="pdfPages" class="space-y-3"></div>
                <p x-show="!loading && pageCount > pagesShown" class="px-1 pt-3 text-xs text-muted"
                   x-text="`Menampilkan ${pagesShown} dari ${pageCount} halaman. Klik Buka untuk melihat semuanya.`"></p>
            </div>

            <div x-show="kind === 'docx'" class="max-h-[420px] overflow-y-auto bg-white px-5 py-4 sm:max-h-[520px]">
                <p x-show="loading" class="text-sm text-muted">Menyiapkan pratinjau…</p>
                <div x-show="!loading" class="docx-preview text-sm leading-6 text-ink" x-html="html"></div>
            </div>

            <p x-show="kind === 'other'" class="px-4 py-4 text-sm text-muted">
                Pratinjau tidak tersedia untuk format ini, tetapi file tetap akan dikirim.
            </p>
        </div>
    </div>

    <p x-cloak x-show="error" x-text="error" class="mt-2 text-xs text-red-600"></p>
    @error($name)<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
