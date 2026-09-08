<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-semibold text-slate-900">Review {{ $intake->intake_number }}</h2><p class="text-sm text-slate-500">Review the complete General Intake Sheet before making a decision.</p></div></x-slot>
    <div class="gis-page">
        <fieldset disabled>
            @include('intakes._form')
        </fieldset>
        <div class="mt-5 flex flex-wrap gap-3 border-t border-slate-200 pt-5">
            <a class="gis-action gis-action-print" href="{{ route('intakes.show', $intake) }}">Back to application</a>
            @can('decide', $intake)
                <form method="POST" action="{{ route('intakes.decide', $intake) }}">@csrf<input type="hidden" name="decision" value="approved"><button class="gis-action gis-action-submit" type="submit">Approve Intake</button></form>
                <form method="POST" action="{{ route('intakes.decide', $intake) }}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="decision" value="rejected"><input name="rejection_reason" required placeholder="Reason for rejection" class="border border-slate-300 px-3 py-2"><button class="gis-action" style="background:#dc2626;color:#fff" type="submit">Reject Intake</button></form>
            @endcan
            @can('headDecide', $intake)
                <form method="POST" action="{{ route('intakes.head-decide', $intake) }}">@csrf<input type="hidden" name="decision" value="approved"><button class="gis-action gis-action-submit" type="submit">Approve Intake</button></form>
                <form method="POST" action="{{ route('intakes.head-decide', $intake) }}">@csrf<input type="hidden" name="decision" value="rejected"><input name="rejection_reason" required placeholder="Reason for rejection" class="border border-slate-300 px-3 py-2"><button class="gis-action" style="background:#dc2626;color:#fff" type="submit">Reject Intake</button></form>
            @endcan
        </div>
    </div>
</x-app-layout>
