<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', [
            'availableLeadershipRoles' => User::availableLeadershipRoles(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $availableLeadershipRoles = User::availableLeadershipRoles();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
            'role' => $availableLeadershipRoles
                ? ['required', 'in:'.implode(',', [...$availableLeadershipRoles, 'social_officer'])]
                : ['nullable'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            // Retained only for compatibility with existing accounts; sign-in uses email exclusively.
            'username' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $availableLeadershipRoles ? $request->role : 'social_officer',
            'status' => 'pending',
            'must_complete_setup' => false,
        ]);

        event(new Registered($user));
        User::where('role', 'super_admin')->where('status', 'approved')->get()
            ->each(fn (User $administrator) => $administrator->notify(new WorkflowNotification(
                'Account approval required',
                "{$user->name} has requested a ".User::roleLabel($user->role).' account.',
                route('users.edit', $user),
            )));

        return redirect()->route('login')->with('status', 'Your account request has been submitted for system administrator approval.');
    }
}
