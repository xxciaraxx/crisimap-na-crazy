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
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);
        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            // The email is the permanent sign-in identifier after this setup step.
            'username' => $data['email'],
            'password' => $data['password'],
            'must_complete_setup' => false,
            'credentials_updated_at' => now(),
        ])->save();
        ActivityLog::create(['user_id' => $user->id, 'action' => 'account_setup_completed', 'description' => 'Completed required account setup.', 'subject_type' => get_class($user), 'subject_id' => $user->id]);
        return redirect()->route('dashboard')->with('success', 'Account setup complete.');
    }
}
