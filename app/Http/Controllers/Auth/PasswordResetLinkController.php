<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();
        if (! $user) return back()->with('status', 'If that email exists, a reset code has been sent.');
        $code = (string) random_int(100000, 999999);
        DB::table('password_reset_tokens')->updateOrInsert(['email' => $user->email], ['token' => Hash::make($code), 'created_at' => now()]);
        $user->notify(new PasswordResetCodeNotification($code));
        session(['password_reset.email' => $user->email]);
        return redirect()->route('password.code.create')->with('status', 'A reset code has been emailed to you.');
    }

    public function createCode(): View { return view('auth.verify-reset-code'); }
    public function verifyCode(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $email = session('password_reset.email');
        $record = $email ? DB::table('password_reset_tokens')->where('email', $email)->first() : null;
        if (! $record || now()->diffInMinutes($record->created_at) > 15 || ! Hash::check($data['code'], $record->token)) return back()->withErrors(['code' => 'This reset code is invalid or has expired.']);
        session(['password_reset.verified_at' => now()->timestamp]);
        return redirect()->route('password.reset');
    }
}
