<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $remember = (bool) ($credentials['remember'] ?? false);
        unset($credentials['remember']);

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();
        if (! $user) {
            return to_route('home');
        }

        $role = $user->role instanceof UserRole ? $user->role : UserRole::from($user->role);

        return match ($role) {
            UserRole::Bupati => to_route('dashboard', ['leader' => 'bupati']),
            UserRole::WakilBupati => to_route('dashboard', ['leader' => 'wakil_bupati']),
            UserRole::Sekda => to_route('dashboard', ['leader' => 'sekda']),
            UserRole::SuperAdmin => to_route('users.index'),
            default => to_route('agendas.index'),
        };
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }
}
