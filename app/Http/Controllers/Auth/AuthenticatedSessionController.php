<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Sengaja TIDAK pakai redirect()->intended(): area admin & student
        // saling eksklusif (role:admin vs role:student), jadi "intended URL"
        // dari sebelum login (mis. /dashboard yang ke-bookmark) bisa salah
        // ngarahin admin ke halaman yang role-nya tidak boleh akses -> 403.
        // Selalu pulang ke home sesuai role aktual.
        $request->session()->forget('url.intended');

        $home = $request->user()->isAdmin()
            ? route('admin.students.index', absolute: false)
            : route('dashboard', absolute: false);

        return redirect($home);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
