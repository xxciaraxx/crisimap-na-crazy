<x-app-layout>
    <x-slot name="header">
        <div class="submit-review-header">
            <a href="{{ route('intakes.edit', $intake) }}" class="submit-review-back">← Back to editing</a>
            <p class="submit-review-eyebrow">Final check</p>
            <h2>Review General Intake Sheet</h2>
            <p>Review the details below before sending <strong>{{ $intake->intake_number }}</strong> for processing.</p>
        </div>
    </x-slot>

    <div class="submit-review-page">
        <div class="submit-review-notice">
            <span class="submit-review-notice-icon" aria-hidden="true">!</span>
            <div><strong>Submitting will lock this sheet</strong><p>After submission, Social Worker Officers can view this record but cannot edit it. Make sure all information is complete and accurate.</p></div>
        </div>
        <div class="gis-zoom-toolbar submit-review-zoom-toolbar" data-gis-zoom-controls>
            <span class="gis-zoom-label"><span aria-hidden="true">◉</span> Form view</span>
            <button type="button" data-gis-zoom="out" aria-label="Zoom out" title="Zoom out">−</button>
            <output data-gis-zoom-value aria-live="polite">100%</output>
            <button type="button" data-gis-zoom="in" aria-label="Zoom in" title="Zoom in">+</button>
            <button type="button" data-gis-zoom="reset">Reset</button>
        </div>

        <fieldset disabled class="submit-review-form">
            @include('intakes._form')
        </fieldset>

        <div class="submit-review-actions">
            <a href="{{ route('intakes.edit', $intake) }}" class="submit-review-secondary">← Continue editing</a>
            <button type="button" class="submit-review-primary" onclick="document.getElementById('submit-confirmation').hidden = false">Submit General Intake Sheet <span aria-hidden="true">→</span></button>
        </div>
    </div>

    <div id="submit-confirmation" class="submit-confirmation" hidden role="dialog" aria-modal="true" aria-labelledby="submit-confirmation-title">
        <div class="submit-confirmation-card">
            <button type="button" class="submit-confirmation-close" aria-label="Close" onclick="document.getElementById('submit-confirmation').hidden = true">×</button>
            <span class="submit-confirmation-check" aria-hidden="true">✓</span>
            <p class="submit-review-eyebrow">Ready to submit?</p>
            <h3 id="submit-confirmation-title">Send this intake sheet for review?</h3>
            <p>Once submitted, this General Intake Sheet will move to the Submitted tab and editing will be disabled for your account.</p>
            <div class="submit-confirmation-summary"><strong>{{ $intake->intake_number }}</strong><span>{{ $intake->client_name }}</span></div>
            <div class="submit-confirmation-actions">
                <button type="button" class="submit-review-secondary" onclick="document.getElementById('submit-confirmation').hidden = true">Go back</button>
                <form method="POST" action="{{ route('intakes.submit', $intake) }}">@csrf<button type="submit" class="submit-review-primary">Yes, submit sheet <span aria-hidden="true">→</span></button></form>
            </div>
        </div>
    </div>

    <style>
        .submit-review-header{position:relative}.submit-review-back{display:inline-block;margin-bottom:.8rem;color:#246da8;font-size:.75rem;font-weight:700;text-decoration:none}.submit-review-eyebrow{margin:0;color:#2170b8;font-size:.67rem;font-weight:800;letter-spacing:.13em;text-transform:uppercase}.submit-review-header h2{margin:.25rem 0;color:#12345a;font-size:1.55rem;letter-spacing:-.035em}.submit-review-header p:not(.submit-review-eyebrow){margin:.35rem 0 0;color:#718198;font-size:.84rem}.submit-review-page{min-height:calc(100vh - 190px);padding:1.7rem clamp(1rem,4vw,3.5rem);background:#f5f8fc}.submit-review-notice{display:flex;gap:.8rem;max-width:960px;margin:0 auto 1.2rem;border:1px solid #f0d49b;border-radius:12px;padding:1rem 1.1rem;color:#77520b;background:#fff9e9}.submit-review-notice-icon{display:grid;flex:0 0 25px;place-items:center;width:25px;height:25px;border-radius:50%;color:#fff;background:#d39212;font-weight:800}.submit-review-notice strong{font-size:.8rem}.submit-review-notice p{margin:.2rem 0 0;color:#8c713a;font-size:.73rem;line-height:1.45}.submit-review-form{max-width:960px;margin:0 auto;border:0;padding:0}.submit-review-form .gis-actions{display:none}.submit-review-actions{display:flex;align-items:center;justify-content:space-between;gap:1rem;max-width:960px;margin:1.2rem auto 0;padding:1.1rem 0;border-top:1px solid #dce6f0}.submit-review-primary,.submit-review-secondary{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;border:0;border-radius:8px;padding:.72rem 1rem;font-size:.77rem;font-weight:750;text-decoration:none;cursor:pointer}.submit-review-primary{color:#fff;background:#0e5cad;box-shadow:0 6px 14px #0e5cad2b}.submit-review-secondary{border:1px solid #cfdae6;color:#536a83;background:#fff}.submit-confirmation{position:fixed;z-index:100;inset:0;display:grid;place-items:center;padding:1rem;background:#102d5078}.submit-confirmation[hidden]{display:none}.submit-confirmation-card{position:relative;width:min(100%,430px);border-radius:16px;padding:2rem;background:#fff;box-shadow:0 22px 55px #102d5040;text-align:center}.submit-confirmation-close{position:absolute;top:.75rem;right:.9rem;border:0;color:#8c9aac;background:none;font-size:1.5rem;cursor:pointer}.submit-confirmation-check{display:grid;place-items:center;width:44px;height:44px;margin:0 auto 1rem;border-radius:50%;color:#fff;background:#23865a;font-size:1.2rem;font-weight:800}.submit-confirmation-card h3{margin:.3rem 0;color:#183a5d;font-size:1.15rem}.submit-confirmation-card>p:not(.submit-review-eyebrow){margin:.55rem auto 1rem;color:#718198;font-size:.78rem;line-height:1.5}.submit-confirmation-summary{display:grid;gap:.2rem;margin:1rem 0;padding:.75rem;border-radius:9px;background:#f4f8fc}.submit-confirmation-summary strong{color:#234664;font-size:.78rem}.submit-confirmation-summary span{color:#7c8c9e;font-size:.72rem}.submit-confirmation-actions{display:flex;justify-content:center;gap:.6rem}.submit-confirmation-actions form{margin:0}@media(max-width:640px){.submit-review-actions{align-items:stretch;flex-direction:column-reverse}.submit-review-primary,.submit-review-secondary{width:100%}}
    </style>
</x-app-layout>
