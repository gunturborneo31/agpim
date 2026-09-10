<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AgendaDraftController extends Controller
{
    use AuthorizesRequests;
    public function edit(Agenda $agenda, string $type): View
    {
        $this->authorize('view', $agenda);

        $allowedTypes = ['berita', 'konten'];
        abort_unless(in_array($type, $allowedTypes, true), 404);

        $field = $type === 'berita' ? 'draft_berita' : 'draft_konten';
        $content = $agenda->{$field} ?? '';

        return view('agendas.draft-editor', [
            'agenda' => $agenda,
            'type' => $type,
            'field' => $field,
            'content' => $content,
        ]);
    }

    public function update(Request $request, Agenda $agenda, string $type): RedirectResponse
    {
        $this->authorize('view', $agenda);

        $allowedTypes = ['berita', 'konten'];
        abort_unless(in_array($type, $allowedTypes, true), 404);

        $field = $type === 'berita' ? 'draft_berita' : 'draft_konten';

        $request->validate([
            'content' => ['nullable', 'string'],
        ]);

        $agenda->forceFill([
            $field => $request->input('content', ''),
        ])->save();

        return redirect()->route('agendas.draft.edit', [$agenda, $type])
            ->with('status', 'Draft '.($type === 'berita' ? 'berita' : 'konten').' berhasil disimpan.');
    }
}
