<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request): View
    {
        abort_unless(session('password_reset.email') && session('password_reset.verified_at') && now()->diffInMinutes(session('password_reset.verified_at')) <= 15, 403);
        return view('auth.reset-password', ['request' => $request]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()]]);
        $email = session('password_reset.email');
        abort_unless($email && session('password_reset.verified_at') && now()->diffInMinutes(session('password_reset.verified_at')) <= 15, 403);
        $user = User::where('email', $email)->firstOrFail();
        $user->forceFill(['password' => Hash::make($data['password']), 'remember_token' => Str::random(60), 'credentials_updated_at' => now()])->save();
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        session()->forget('password_reset');
        event(new PasswordReset($user));
        return redirect()->route('login')->with('status', 'Password reset successfully.');
    }
}
