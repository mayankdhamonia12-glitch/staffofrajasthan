@props(['padding' => 'p-5', 'interactive' => false])
<article {{ $attributes->class(['rounded-2xl border border-slate-200 bg-white shadow-sm', $padding, $interactive ? 'transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-lg' : '']) }}>{{ $slot }}</article>
