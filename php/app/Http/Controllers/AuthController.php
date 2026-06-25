<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login_code' => 'required|string|size:6',
        ]);

        $user = \App\Models\User::where('login_code', $credentials['login_code'])->first();

        if (!$user) {
            return back()->withErrors([
                'login_code' => 'Código inválido.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->isSuperAdmin()) {
            return redirect('/super');
        }

        return redirect('/painel');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
