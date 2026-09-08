<x-app-layout>
	<x-slot name="header"><div><h2 class="text-xl font-semibold text-slate-900">New General Intake Sheet</h2><p class="text-sm text-slate-500">Complete the official A4 intake form below.</p></div></x-slot>
	<div class="gis-page"><form method="POST" action="{{ route('intakes.store') }}">@csrf @include('intakes._form')</form></div>
</x-app-layout>
