<x-app-layout>
	<x-slot name="header"><div><h2 class="text-xl font-semibold text-slate-900">Edit {{ $intake->intake_number }}</h2><p class="text-sm text-slate-500">Update the official A4 intake form.</p></div></x-slot>
	<div class="gis-page"><form method="POST" action="{{ route('intakes.update', $intake) }}">@csrf @method('PUT') @include('intakes._form')</form></div>
</x-app-layout>
