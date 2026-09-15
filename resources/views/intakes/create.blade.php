<x-app-layout>
	<x-slot name="header"><div><h2 class="text-xl font-semibold text-slate-900">New General Intake Sheet</h2><p class="text-sm text-slate-500">Complete the official A4 intake form below.</p></div></x-slot>
	<div class="gis-page">
        <div class="gis-zoom-toolbar" data-gis-zoom-controls>
            <span class="gis-zoom-label"><span aria-hidden="true">◉</span> Form view</span>
            <button type="button" data-gis-zoom="out" aria-label="Zoom out" title="Zoom out">−</button>
            <output data-gis-zoom-value aria-live="polite">100%</output>
            <button type="button" data-gis-zoom="in" aria-label="Zoom in" title="Zoom in">+</button>
            <button type="button" data-gis-zoom="reset">Reset</button>
        </div>
        <form method="POST" action="{{ route('intakes.store') }}" enctype="multipart/form-data">@csrf @include('intakes._form')</form>
    </div>
</x-app-layout>
