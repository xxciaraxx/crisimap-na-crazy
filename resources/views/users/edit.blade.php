<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit User</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm">Name</label><input name="name" value="{{ old('name', $user->name) }}" class="w-full border rounded px-3 py-2" required></div>
                        <div><label class="block text-sm">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full border rounded px-3 py-2" required></div>
                        <div><label class="block text-sm">Role</label><select name="role" class="w-full border rounded px-3 py-2"><option value="social_officer" @selected($user->role === 'social_officer')>Social Officer</option><option value="focal_person" @selected($user->role === 'focal_person')>Focal Person</option><option value="admin" @selected($user->role === 'admin')>Admin (MSWDO Head)</option><option value="super_admin" @selected($user->role === 'super_admin')>Super Administrator (IT)</option></select></div>
                        <div><label class="block text-sm">Status</label><select name="status" class="w-full border rounded px-3 py-2"><option value="pending" @selected($user->status === 'pending')>Pending</option><option value="approved" @selected($user->status === 'approved')>Approved</option><option value="rejected" @selected($user->status === 'rejected')>Rejected</option></select></div>
                        <div><label class="block text-sm">Password</label><input type="password" name="password" class="w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-sm">Confirm Password</label><input type="password" name="password_confirmation" class="w-full border rounded px-3 py-2"></div>
                    </div>
                    <button class="bg-green-600 text-white px-4 py-2 rounded">Update</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
