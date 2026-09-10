<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\AgendaDocument;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgendaMediaController extends Controller
{
    use AuthorizesRequests;

    private function documentsQuery(Agenda $agenda, ?string $type = null)
    {
        $query = $agenda->documents()->orderByDesc('created_at');

        if ($type !== null) {
            $query->where('category', $type);
        }

        return $query;
    }

    public function index(Request $request, Agenda $agenda): View
    {
        $this->authorize('view', $agenda);

        $type = $request->query('type');
        $allowedTypes = ['photo', 'video', 'berita', 'konten'];
        $activeType = in_array($type, $allowedTypes, true) ? $type : 'all';

        $documentsQuery = $activeType === 'all'
            ? $this->documentsQuery($agenda)
            : $this->documentsQuery($agenda, $activeType);

        return view('agendas.media', [
            'agenda' => $agenda,
            'documents' => $documentsQuery->get(),
            'activeType' => $activeType,
        ]);
    }

    public function store(Request $request, Agenda $agenda): RedirectResponse
    {
        $this->authorize('view', $agenda);

        $category = $request->input('category');
        $fileRules = ['file', 'max:20480'];
        if ($category === 'photo') {
            $fileRules[] = 'mimes:jpg,jpeg,png,webp,gif,bmp';
        } elseif ($category === 'video') {
            $fileRules[] = 'mimes:mp4,mov,avi,mkv,webm';
        }

        $validated = $request->validate([
            'category' => ['required', Rule::in(['photo', 'video', 'berita', 'konten'])],
            'source_from' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => $fileRules,
        ], [
            'files.required' => 'Pilih minimal satu file untuk diupload.',
            'files.*.mimes' => 'Jenis file tidak sesuai dengan kategori yang dipilih.',
        ]);

        foreach ($request->file('files', []) as $file) {
            $path = $file->store('agendas/media/'.$agenda->id, 'public');

            $agenda->documents()->create([
                'uploaded_by' => $request->user()?->id,
                'category' => $validated['category'],
                'title' => $file->getClientOriginalName(),
                'source_from' => $validated['source_from'] ?? null,
                'description' => $validated['description'],
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'is_public_internal' => false,
            ]);
        }

        return back()->with('status', 'File berhasil diupload.');
    }

    public function update(Request $request, Agenda $agenda, AgendaDocument $document): RedirectResponse
    {
        $this->authorize('view', $agenda);

        if ($document->agenda_id !== $agenda->id) {
            abort(404);
        }

        $validated = $request->validate([
            'source_from' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $document->update([
            'source_from' => $validated['source_from'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('status', 'Keterangan file berhasil diperbarui.');
    }

    public function bulkUpdate(Request $request, Agenda $agenda): RedirectResponse
    {
        $this->authorize('view', $agenda);

        $validated = $request->validate([
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer'],
            'source_from' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $selectedIds = array_values(array_unique($validated['document_ids']));
        $documents = AgendaDocument::query()
            ->where('agenda_id', $agenda->id)
            ->whereIn('id', $selectedIds)
            ->get();

        if ($documents->count() !== count($selectedIds)) {
            return back()->withErrors([
                'document_ids' => 'Sebagian file terpilih tidak valid atau tidak ditemukan.',
            ])->withInput();
        }

        AgendaDocument::query()
            ->where('agenda_id', $agenda->id)
            ->whereIn('id', $selectedIds)
            ->update([
                'source_from' => $validated['source_from'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);

        return back()->with('status', count($selectedIds).' file berhasil diperbarui sekaligus.');
    }

    public function bulkDestroy(Request $request, Agenda $agenda): RedirectResponse
    {
        $this->authorize('view', $agenda);

        $validated = $request->validate([
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer'],
        ]);

        $selectedIds = array_values(array_unique($validated['document_ids']));
        $documents = AgendaDocument::query()
            ->where('agenda_id', $agenda->id)
            ->whereIn('id', $selectedIds)
            ->get();

        if ($documents->count() !== count($selectedIds)) {
            return back()->withErrors([
                'document_ids' => 'Sebagian file terpilih tidak valid atau tidak ditemukan.',
            ])->withInput();
        }

        foreach ($documents as $document) {
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
        }

        AgendaDocument::query()
            ->where('agenda_id', $agenda->id)
            ->whereIn('id', $selectedIds)
            ->delete();

        return back()->with('status', count($selectedIds).' file berhasil dihapus sekaligus.');
    }

    public function destroy(Agenda $agenda, AgendaDocument $document): RedirectResponse
    {
        $this->authorize('view', $agenda);

        if ($document->agenda_id !== $agenda->id) {
            abort(404);
        }

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('status', 'File berhasil dihapus.');
    }
}
