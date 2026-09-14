@props(['name', 'size' => 20])
<svg {{ $attributes->merge(['class' => 'icon']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('shield') <path d="M12 3 4 6v6c0 5 8 9 8 9s8-4 8-9V6l-8-3Z"/><path d="m8 12 3 3 5-6"/> @break
        @case('grid') <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/> @break
        @case('document') <path d="M14 3H5v18h14V8l-5-5Z"/><path d="M14 3v5h5M8 12h8M8 16h5"/> @break
        @case('review') <path d="M9 5H5v16h14V5h-4M9 3h6v4H9z"/><path d="m8 14 3 3 5-6"/> @break
        @case('users') <circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6M18 15a4 4 0 0 1 3 4v2"/> @break
        @case('arrow') <path d="M4 12h16m-6-6 6 6-6 6"/> @break
        @case('back') <path d="M20 12H4m6-6-6 6 6 6"/> @break
        @case('download') <path d="M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5"/> @break
        @case('plus') <path d="M12 5v14M5 12h14"/> @break
        @case('logout') <path d="M10 4H4v16h6M9 12h12m-4-4 4 4-4 4"/> @break
        @case('menu') <path d="M4 6h16M4 12h16M4 18h16"/> @break
        @case('check') <path d="m5 12 4 4L19 6"/> @break
        @case('info') <circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/> @break
        @case('lock') <rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/> @break
        @case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
        @case('search') <circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/> @break
    @endswitch
</svg>
