<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AccountSetupController extends Controller
{
    public function create(Request $request)
    {
        abort_unless($request->user()->must_complete_setup, 404);
        return view('auth.account-setup');
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->must_complete_setup, 404);
        $data = $request->validate([
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:50', 'unique:users,username,'.$user->id],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);
        $user->forceFill(['username' => $data['username'], 'email' => $data['email'], 'password' => $data['password'], 'must_complete_setup' => false, 'credentials_updated_at' => now(), 'email_verified_at' => null])->save();
        ActivityLog::create(['user_id' => $user->id, 'action' => 'account_setup_completed', 'description' => 'Completed required account setup.', 'subject_type' => get_class($user), 'subject_id' => $user->id]);
        $user->sendEmailVerificationNotification();
        return redirect()->route('verification.notice')->with('status', 'Account setup complete. Verify your email to continue.');
    }
}
