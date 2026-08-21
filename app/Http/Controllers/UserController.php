<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Rules\SingleAccountRole;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);
        $users = User::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('create', User::class);
        return view('users.create');
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status,
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'created',
            'description' => 'Created user account '.$user->name,
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'username' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username,'.$user->id],
            'role' => ['required', 'in:social_officer,focal_person,admin,super_admin', new SingleAccountRole($user->id)],
            'status' => ['required', 'in:pending,approved,rejected'],
            'password' => ['nullable', 'min:8', 'confirmed'],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function approve(User $user)
    {
        $this->authorize('update', $user);

        if ($user->status !== 'pending') {
            return back()->with('error', 'Only pending account requests can be approved.');
        }

        if (! User::roleSlotAvailable($user->role, $user->id)) {
            return back()->with('error', 'Someone else was already approved as '.User::roleLabel($user->role).'. Reject or reassign this application instead.');
        }

        $user->update(['status' => 'approved', 'rejection_reason' => null]);
        ActivityLog::create(['user_id' => auth()->id(), 'action' => 'approved', 'description' => 'Approved user account '.$user->name, 'subject_type' => User::class, 'subject_id' => $user->id]);

        return back()->with('success', 'Account approved.');
    }

    public function reject(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);

        if ($user->status !== 'pending') {
            return back()->with('error', 'Only pending account requests can be rejected.');
        }

        $user->update(['status' => 'rejected', 'rejection_reason' => $data['rejection_reason']]);
        ActivityLog::create(['user_id' => auth()->id(), 'action' => 'rejected', 'description' => 'Rejected user account '.$user->name, 'subject_type' => User::class, 'subject_id' => $user->id]);

        return back()->with('success', 'Account request rejected.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        if ($user->role === 'super_admin' && User::where('role', 'super_admin')->count() === 1) {
            return back()->with('error', 'The last System Administrator account cannot be deleted.');
        }
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted.');
    }
}
