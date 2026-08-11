@props(['items' => []])
<nav {{ $attributes->merge(['class' => 'mb-4 flex flex-wrap items-center gap-2 text-sm text-slate-500']) }} aria-label="Breadcrumb">
    @foreach($items as $item)
        @if(! $loop->first)<span aria-hidden="true">/</span>@endif
        @if(!empty($item['url']))<a class="hover:text-blue-700" href="{{ $item['url'] }}">{{ $item['label'] }}</a>@else<span class="font-medium text-slate-700">{{ $item['label'] }}</span>@endif
    @endforeach
</nav>
