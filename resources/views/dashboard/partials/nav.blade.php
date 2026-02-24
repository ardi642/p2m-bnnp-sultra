@php
    $role = auth()->user()->role;
    $isAll = in_array($role, ['admin', 'admin_satker', 'operator_satker']);
    $isP2m = $isAll || in_array($role, ['admin_p2m', 'operator_p2m']);
    $isBerantas = $isAll || in_array($role, ['admin_berantas', 'operator_berantas']);
    $isRehab = $isAll || in_array($role, ['admin_rehab', 'operator_rehab']);
    $currentRoute = Route::currentRouteName();
@endphp

<div class="btn-group shadow-sm">
    @if($isP2m)
        <a href="{{ route('dashboard.p2m.index') }}" class="btn {{ str_contains($currentRoute, 'p2m') ? 'btn-primary border-primary' : 'btn-light text-secondary border' }} fw-bold px-4">
            <i class="bi bi-megaphone-fill me-1"></i> P2M
        </a>
    @endif
    
    @if($isBerantas)
        <a href="{{ route('dashboard.berantas.index') }}" class="btn {{ str_contains($currentRoute, 'berantas') ? 'btn-primary border-primary' : 'btn-light text-secondary border' }} fw-bold px-4">
            <i class="bi bi-shield-fill-check me-1"></i> Berantas
        </a>
    @endif
    
    @if($isRehab)
        <a href="{{ route('dashboard.rehab.index') }}" class="btn {{ str_contains($currentRoute, 'rehab') ? 'btn-primary border-primary' : 'btn-light text-secondary border' }} fw-bold px-4">
            <i class="bi bi-heart-pulse-fill me-1"></i> Rehab
        </a>
    @endif
</div>