<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">System Settings</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">Barangays</h3>
                <form method="POST" action="{{ route('settings.barangays.store') }}" class="space-y-3">
                    @csrf
                    <input name="name" placeholder="Barangay name" class="w-full border rounded px-3 py-2" required>
                    <input name="code" placeholder="Code" class="w-full border rounded px-3 py-2">
                    <textarea name="notes" placeholder="Notes" class="w-full border rounded px-3 py-2"></textarea>
                    <button class="bg-green-600 text-white px-4 py-2 rounded">Add Barangay</button>
                </form>
                <ul class="mt-4 space-y-2">
                    @foreach($barangays as $barangay)
                        <li class="flex justify-between border rounded p-2"><span>{{ $barangay->name }}</span><form method="POST" action="{{ route('settings.barangays.destroy', $barangay) }}">@csrf @method('DELETE')<button class="text-red-600">Remove</button></form></li>
                    @endforeach
                </ul>
            </div>
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">Assistance Types</h3>
                <form method="POST" action="{{ route('settings.assistance-types.store') }}" class="space-y-3">
                    @csrf
                    <input name="name" placeholder="Assistance type" class="w-full border rounded px-3 py-2" required>
                    <input name="color" placeholder="Color" class="w-full border rounded px-3 py-2">
                    <textarea name="description" placeholder="Description" class="w-full border rounded px-3 py-2"></textarea>
                    <button class="bg-blue-600 text-white px-4 py-2 rounded">Add Type</button>
                </form>
                <ul class="mt-4 space-y-2">
                    @foreach($assistanceTypes as $type)
                        <li class="flex justify-between border rounded p-2"><span>{{ $type->name }}</span><form method="POST" action="{{ route('settings.assistance-types.destroy', $type) }}">@csrf @method('DELETE')<button class="text-red-600">Remove</button></form></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
