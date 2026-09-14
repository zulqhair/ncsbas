@props(['status' => null])
@php
    $tone = match ($status) {
        'completed', 'Advanced' => 'success',
        'open', 'pending', 'Basic' => 'warning',
        'accepted', 'in_review', 'Intermediate' => 'info',
        default => 'neutral',
    };
    $label = $status ? ucfirst(str_replace('_', ' ', $status)) : 'Not scored';
@endphp
<span {{ $attributes->merge(['class' => 'status-badge status-'.$tone]) }}><span class="status-dot" aria-hidden="true"></span>{{ $label }}</span>
