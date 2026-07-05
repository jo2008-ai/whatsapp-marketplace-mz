<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /** @return \Illuminate\View\View */
    public function showLinkRequestForm()
    {
        return view('auth.esqueci-password');
    }

    /** @return \Illuminate\Http\RedirectResponse */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            try {
                Mail::to($user->email)->send(new PasswordResetMail($user, $token));
            } catch (\Exception $e) {
                // Don't reveal if email exists or not
            }
        }

        return back()->with('success', 'Se o email existir, receberás um link para repor a password.');
    }

    /** @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse */
    public function showResetForm(Request $request, string $token)
    {
        $email = $request->query('email');

        if (!$email) {
            return redirect('/esqueci-password')->with('error', 'Link inválido.');
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$record || !Hash::check($token, $record->token)) {
            return redirect('/esqueci-password')->with('error', 'Link inválido ou expirado.');
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return redirect('/esqueci-password')->with('error', 'Link expirado. Solicita um novo.');
        }

        return view('auth.repor-password', ['token' => $token, 'email' => $email]);
    }

    /** @return \Illuminate\Http\RedirectResponse */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'string', 'min:8', 'confirmed', new \App\Rules\StrongPassword],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'Link inválido ou expirado.']);
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->withErrors(['email' => 'Link expirado. Solicita um novo.']);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Utilizador não encontrado.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect('/login')->with('success', 'Password reposta com sucesso. Faz login.');
    }
}
