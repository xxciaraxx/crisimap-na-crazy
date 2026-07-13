<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Beneficiary</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <form method="POST" action="{{ route('beneficiaries.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm">Full Name</label><input name="name" value="{{ old('name') }}" class="w-full border rounded px-3 py-2" required></div>
                        <div><label class="block text-sm">Age</label><input type="number" name="age" value="{{ old('age') }}" class="w-full border rounded px-3 py-2" required></div>
                        <div><label class="block text-sm">Sex</label><select name="sex" class="w-full border rounded px-3 py-2"><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option></select></div>
                        <div><label class="block text-sm">Contact</label><input name="contact" value="{{ old('contact') }}" class="w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-sm">Barangay</label><select name="barangay_id" class="w-full border rounded px-3 py-2"><option value="">Select</option>@foreach($barangays as $barangay)<option value="{{ $barangay->id }}">{{ $barangay->name }}</option>@endforeach</select></div>
                        <div><label class="block text-sm">Assistance Type</label><select name="assistance_type_id" class="w-full border rounded px-3 py-2"><option value="">Select</option>@foreach($assistanceTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select></div>
                        <div><label class="block text-sm">Address</label><input name="address" value="{{ old('address') }}" class="w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-sm">Date of Assistance</label><input type="date" name="date_of_assistance" value="{{ old('date_of_assistance') }}" class="w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-sm">Latitude</label><input name="latitude" value="{{ old('latitude') }}" class="w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-sm">Longitude</label><input name="longitude" value="{{ old('longitude') }}" class="w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-sm">Status</label><select name="status" class="w-full border rounded px-3 py-2"><option value="Pending">Pending</option><option value="Approved">Approved</option><option value="Released">Released</option><option value="Completed">Completed</option></select></div>
                        <div><label class="block text-sm">Attachment</label><input type="file" name="attachment" class="w-full"></div>
                    </div>
                    <div><label class="block text-sm">Case Information</label><textarea name="case_info" rows="4" class="w-full border rounded px-3 py-2">{{ old('case_info') }}</textarea></div>
                    <div class="flex gap-3">
                        <button class="bg-green-600 text-white px-4 py-2 rounded">Save</button>
                        <a href="{{ route('beneficiaries.index') }}" class="bg-gray-200 px-4 py-2 rounded">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
