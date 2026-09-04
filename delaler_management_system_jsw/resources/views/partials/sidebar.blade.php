@php
    $menu = \App\Helpers\SidebarHelper::getMenu();
@endphp

<div class="bg-white border-end shadow-sm h-100" id="sidebar-wrapper">
    <div class="list-group list-group-flush border-0 mt-3">
        @foreach ($menu as $index => $item)
            @if (isset($item['submodules']) && count($item['submodules']) > 0)
                @php
                    $isSubmenuActive = false;
                    foreach ($item['submodules'] as $sub) {
                        if (\App\Helpers\SidebarHelper::isActiveRoute($sub)) {
                            $isSubmenuActive = true;
                            break;
                        }
                    }
                @endphp

                <a href="#submenu-{{ $index }}" data-bs-toggle="collapse"
                    aria-expanded="{{ $isSubmenuActive ? 'true' : 'false' }}"
                    class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center justify-content-between {{ $isSubmenuActive ? 'bg-light text-primary fw-bold' : 'text-dark' }}">
                    <div>
                        <i class="{{ $item['icon'] ?? 'fas fa-circle' }} fa-fw me-2"></i> {{ $item['title'] }}
                    </div>
                    <i class="fas fa-chevron-down fa-xs text-muted collapse-icon"></i>
                </a>

                <div class="collapse {{ $isSubmenuActive ? 'show' : '' }}" id="submenu-{{ $index }}">
                    <div class="submenu-tree bg-transparent">
                        @foreach ($item['submodules'] as $sub)
                            @php
                                $isActive = \App\Helpers\SidebarHelper::isActiveRoute($sub);
                            @endphp
                            <a href="{{ isset($sub['route']) && Route::has($sub['route']) ? route($sub['route']) : '#' }}"
                                class="list-group-item list-group-item-action submenu-item {{ $isActive ? 'active-submenu' : 'text-muted' }}">
                                <i class="fas fa-circle fa-2xs me-2 opacity-50"></i> {{ $sub['title'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                @php
                    $isActive = \App\Helpers\SidebarHelper::isActiveRoute($item);
                @endphp
                <a href="{{ isset($item['route']) && Route::has($item['route']) ? route($item['route']) : '#' }}"
                    class="list-group-item list-group-item-action border-0 py-3 {{ $isActive ? 'bg-light text-primary fw-bold border-start border-primary border-3' : 'text-dark' }}">
                    <i class="{{ $item['icon'] ?? 'fas fa-circle' }} fa-fw me-2"></i> {{ $item['title'] }}
                </a>
            @endif
        @endforeach
    </div>
</div>
