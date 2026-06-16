<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Prodi yang dilayani sistem ini (docs/spec.md bagian 1).
     */
    public const PRODI_OPTIONS = ['S1 Ilmu Aktuaria', 'S1 Matematika'];

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', ['prodiOptions' => self::PRODI_OPTIONS]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'no_induk' => ['required', 'string', 'max:50', 'regex:/^[0-9]+$/', 'unique:'.Student::class.',no_induk'],
            'prodi' => ['required', Rule::in(self::PRODI_OPTIONS)],
        ]);

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            Student::create([
                'user_id' => $user->id,
                'no_induk' => $request->no_induk,
                'nama' => $request->name,
                'prodi' => $request->prodi,
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
