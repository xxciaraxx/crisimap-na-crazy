<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-semibold text-slate-900">Review {{ $intake->intake_number }}</h2><p class="text-sm text-slate-500">Review the complete General Intake Sheet before making a decision.</p></div></x-slot>
    <div class="gis-page intake-review-page" x-data="{ approveOpen: false, rejectOpen: false, rejectAction: '', rejectRole: '' }">
        <fieldset disabled>
            @include('intakes._form')
        </fieldset>
        <div class="intake-decision-panel">
            <div>
                <p class="intake-decision-eyebrow">Decision required</p>
                <h3>Confirm the application decision</h3>
                <p>Review the information above before approving or rejecting this General Intake Sheet.</p>
            </div>
            <div class="intake-decision-actions">
                @can('decide', $intake)
                    <form id="approve-intake-form" method="POST" action="{{ route('intakes.decide', $intake) }}">@csrf<input type="hidden" name="decision" value="approved"></form>
                    <button type="button" class="intake-button intake-button-approve" @click="approveOpen = true">Approve Intake</button>
                    <button type="button" class="intake-button intake-button-reject" @click="rejectAction = '{{ route('intakes.decide', $intake) }}'; rejectRole = 'Focal Person'; rejectOpen = true">Reject Intake</button>
                @endcan
                @can('headDecide', $intake)
                    <form id="approve-intake-form" method="POST" action="{{ route('intakes.head-decide', $intake) }}">@csrf<input type="hidden" name="decision" value="approved"></form>
                    <button type="button" class="intake-button intake-button-approve" @click="approveOpen = true">Approve Intake</button>
                    <button type="button" class="intake-button intake-button-reject" @click="rejectAction = '{{ route('intakes.head-decide', $intake) }}'; rejectRole = 'MSWDO Head'; rejectOpen = true">Reject Intake</button>
                @endcan
            </div>
        </div>
        <div x-cloak x-show="approveOpen" x-transition.opacity class="intake-confirm-backdrop" role="dialog" aria-modal="true" aria-labelledby="approve-intake-title" @keydown.escape.window="approveOpen = false">
            <div class="intake-confirm-card" @click.outside="approveOpen = false">
                <div class="intake-confirm-icon" aria-hidden="true">✓</div>
                <p class="intake-decision-eyebrow">Approval confirmation</p>
                <h3 id="approve-intake-title">Approve this intake sheet?</h3>
                <p>This will mark <strong>{{ $intake->intake_number }}</strong> as eligible and move it to the next approval stage.</p>
                <div class="intake-confirm-actions"><button type="button" class="intake-button intake-button-cancel" @click="approveOpen = false">Cancel</button><button type="submit" form="approve-intake-form" class="intake-button intake-button-approve">Yes, approve intake</button></div>
            </div>
        </div>
        <div x-cloak x-show="rejectOpen" x-transition.opacity class="intake-confirm-backdrop" role="dialog" aria-modal="true" aria-labelledby="reject-intake-title" @keydown.escape.window="rejectOpen = false">
            <div class="intake-confirm-card intake-reject-card" @click.outside="rejectOpen = false">
                <div class="intake-confirm-icon intake-confirm-icon-reject" aria-hidden="true">!</div>
                <p class="intake-decision-eyebrow intake-reject-eyebrow">Rejection confirmation</p>
                <h3 id="reject-intake-title">Reject this application?</h3>
                <p>You are rejecting <strong>{{ $intake->intake_number }}</strong> as <strong x-text="rejectRole"></strong>. This action will move the application to the rejected stage.</p>
                <form method="POST" :action="rejectAction">
                    @csrf
                    <input type="hidden" name="decision" value="rejected">
                    <label class="intake-reason-label" for="modal-rejection-reason">Reason for rejection <span>Required</span></label>
                    <textarea id="modal-rejection-reason" name="rejection_reason" required maxlength="2000" placeholder="Explain why this application is being rejected"></textarea>
                    <div class="intake-confirm-actions"><button type="button" class="intake-button intake-button-cancel" @click="rejectOpen = false">Cancel</button><button type="submit" class="intake-button intake-button-reject">Confirm rejection</button></div>
                </form>
            </div>
        </div>
    </div>
    <style>
        .intake-review-page .gis-actions{display:none}.intake-decision-panel{display:grid;grid-template-columns:minmax(0,1fr) minmax(280px,420px);gap:2rem;margin-top:1.5rem;border:1px solid #dbe6f1;border-radius:16px;padding:1.5rem;background:#f8fbff}.intake-decision-eyebrow{margin:0;color:#1768aa;font-size:.7rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.intake-decision-panel h3,.intake-confirm-card h3{margin:.35rem 0;color:#153b60;font-size:1.1rem;font-weight:800}.intake-decision-panel p:not(.intake-decision-eyebrow),.intake-confirm-card>p:not(.intake-decision-eyebrow){margin:.45rem 0;color:#61758b;font-size:.84rem;line-height:1.5}.intake-decision-actions{display:grid;gap:.75rem}.intake-button{display:inline-flex;align-items:center;justify-content:center;min-height:42px;border:0;border-radius:9px;padding:.7rem 1rem;font-size:.82rem;font-weight:800;cursor:pointer;transition:background .18s ease,transform .18s ease,box-shadow .18s ease}.intake-button:hover{transform:translateY(-1px);box-shadow:0 6px 14px rgba(16,59,96,.14)}.intake-button-approve{color:#fff;background:#18794e}.intake-button-approve:hover{background:#12633f}.intake-button-reject{color:#fff;background:#d9363e}.intake-button-reject:hover{background:#b92830}.intake-confirm-backdrop{position:fixed;z-index:100;inset:0;display:grid;place-items:center;padding:1rem;background:rgba(15,38,65,.64)}.intake-confirm-card{width:min(100%,430px);border:1px solid #dbe6f1;border-radius:16px;padding:1.7rem;background:#fff;box-shadow:0 24px 60px rgba(8,35,65,.28)}.intake-confirm-icon{display:grid;place-items:center;width:42px;height:42px;margin-bottom:.85rem;border-radius:50%;color:#fff;background:#18794e;font-weight:900}.intake-confirm-icon-reject{background:#d9363e}.intake-reject-eyebrow{color:#c22f38}.intake-reason-label{display:block;margin-top:1rem;color:#536b83;font-size:.75rem;font-weight:800}.intake-reason-label span{color:#c22f38;font-weight:600}.intake-confirm-card textarea{width:100%;min-height:95px;margin-top:.45rem;resize:vertical;border:1px solid #cbd9e6;border-radius:8px;padding:.65rem;color:#24445f;background:#fff;font-size:.82rem;outline:0}.intake-confirm-card textarea:focus{border-color:#d9363e;box-shadow:0 0 0 3px rgba(217,54,62,.12)}.intake-confirm-actions{display:flex;justify-content:flex-end;gap:.65rem;margin-top:1.3rem}.intake-button-cancel{border:1px solid #cbd9e6;color:#405c75;background:#fff}.intake-button-cancel:hover{background:#f3f7fb}@media(max-width:760px){.intake-decision-panel{grid-template-columns:1fr;gap:1rem}.intake-confirm-actions{flex-direction:column-reverse}.intake-confirm-actions .intake-button{width:100%}}
    </style>
</x-app-layout>
