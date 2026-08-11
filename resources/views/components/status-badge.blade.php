@props(['status'])
@php
    $classes = match ($status) {
        'Pending' => 'bg-amber-100 text-amber-800',
        'Approved', 'Released', 'Completed' => 'bg-emerald-100 text-emerald-800',
        'Rejected' => 'bg-rose-100 text-rose-800',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-2 py-1 text-xs font-medium {$classes}"]) }}>{{ $status }}</span>
