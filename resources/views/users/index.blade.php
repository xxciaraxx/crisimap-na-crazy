<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">User Management</h2>
            <a href="{{ route('users.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">Create User</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form class="mb-4 flex flex-wrap items-center gap-3" method="GET"><label for="status" class="text-sm font-medium text-gray-700">Status</label><select id="status" name="status" class="rounded border-gray-300 text-sm"><option value="">All accounts</option>@foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select><button class="rounded bg-blue-700 px-3 py-2 text-sm font-semibold text-white">Filter</button>@if(request()->filled('status'))<a href="{{ route('users.index') }}" class="text-sm text-blue-700">Clear</a>@endif</form>
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Email</th>
                            <th class="px-4 py-3 text-left">Role</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td class="px-4 py-3">{{ $user->name }}</td>
                                <td class="px-4 py-3">{{ $user->email }}</td>
                                <td class="px-4 py-3">{{ \App\Models\User::roleLabel($user->role) }}</td>
                                <td class="px-4 py-3">{{ $user->status }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('users.edit', $user) }}" class="text-blue-600">Edit</a>
                                    @if($user->status === 'pending')
                                        <form method="POST" action="{{ route('users.approve', $user) }}" class="inline ml-3">@csrf<button class="text-green-700">Approve</button></form>
                                        <form method="POST" action="{{ route('users.reject', $user) }}" class="inline ml-3" onsubmit="return confirm('Reject this account request?')">@csrf<input type="hidden" name="rejection_reason" value="Account request was not approved."><button class="text-red-700">Reject</button></form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
