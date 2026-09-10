@extends('layouts.app', ['title' => 'AGPIM • Edit Draft'])

@section('content')
<x-ui.panel>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <x-ui.section-heading eyebrow="Draft" :title="'Edit draft '.($type === 'berita' ? 'berita' : 'konten')" :subtitle="'Agenda: '.$agenda->title" />
        </div>
        <a href="{{ route('agendas.index') }}" class="btn-secondary">Kembali</a>
    </div>

    @if (session('status'))
        <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('agendas.draft.update', [$agenda, $type]) }}" class="mt-6 space-y-4">
        @csrf
        @method('PUT')
        <label class="form-field">
            <span>Isi draft {{ $type === 'berita' ? 'berita' : 'konten' }}</span>
            <textarea id="draftContent" name="content" rows="20" class="min-h-[560px] w-full" placeholder="Tulis draft di sini...">{{ old('content', $content) }}</textarea>
            <p class="mt-2 text-xs text-slate-500">Editor menggunakan TinyMCE untuk pengalaman menulis seperti Word.</p>
        </label>
        <div class="flex justify-end">
            <button type="submit" class="btn-primary">Simpan Draft</button>
        </div>
    </form>
</x-ui.panel>

<script src="https://cdn.tiny.cloud/1/6h1pwrr2zi4ne4tchyvkcm0104lb59echyx2238r8slilvh5/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    (function () {
        const textarea = document.getElementById('draftContent');
        if (!textarea || typeof tinymce === 'undefined') {
            return;
        }

        tinymce.init({
            selector: '#draftContent',
            height: 620,
            menubar: 'file edit view insert format tools table help',
            branding: false,
            promotion: false,
            plugins: [
                'advlist autolink lists link image charmap preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table help wordcount',
                'emoticons codesample quickbars'
            ],
            toolbar: [
                'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor',
                'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | codesample blockquote',
                'removeformat | fullscreen preview code | help'
            ],
            quickbars_selection_toolbar: 'bold italic underline | blocks | forecolor backcolor | quicklink blockquote',
            quickbars_insert_toolbar: 'quickimage quicktable',
            content_style: 'body { font-family: Calibri, Arial, sans-serif; font-size: 15px; line-height: 1.6; margin: 1rem; }',
            setup: function (editor) {
                const form = textarea.closest('form');
                if (!form) {
                    return;
                }

                form.addEventListener('submit', function () {
                    editor.save();
                });
            }
        });
    })();
</script>
@endsection
