<div class="h-full flex flex-col bg-color-default ">
    <div class="grow-0 h-16">
        <div class="flex h-full items-center px-4">
            {{-- <img class="h-10 mr-3" src="{{ asset('imgs/isabela-state-university-logo.png') }}" alt="logo"> --}}
            <img class="h-[25px]" src="{{ asset('imgs/LightMaster.png') }}" alt="logo">
        </div>
    </div>
    <div class="flex-1 left-nav">
        <div class="uk-width-1-2@s uk-width-2-5@m" style="width: 100% !important">
            <ul class="uk-nav-default" uk-nav>
                <li id="dashboard-menu-item" style="display: block"
                    class="{{ Request::is('dashboard') ? 'active' : '' }}">
                    <a href="/dashboard">
                        <img src="{{ asset('imgs/dashboard.png') }}" alt="Dashboard">
                        Dashboard
                    </a>
                </li>
                <li id="vehicle-management-menu-item-parent" style="display: block"
                    class="{{ Request::is('device*') ? 'active' : '' }}">
                    <a href="/device">
                        <img src="{{ asset('imgs/microchip.png') }}" alt="Students">
                        Device Management
                    </a>
                    {{-- <ul class="uk-nav-sub">
                        <li id="vehicle-owners-menu-item" style="display: block" class="">
                            <a href="">Vehicle Owners</a>
                        </li>
                        <li id="registered-vehicle-menu-item" style="display: block" class="">
                            <a href="">Registered Vehicles</a>
                        </li>
                    </ul> --}}
                </li>
                <li id="reports-menu-item-parent" style="display: block"
                    class="{{ Request::is('reports') ? 'active' : '' }}">
                    <a href="/reports">
                        <img src="{{ asset('imgs/reports.png') }}" alt="Reports">
                        Reports
                    </a>
                    <ul class="uk-nav-sub">
                        <li class="">
                            <a href="">Activity Logs</a>
                        </li>
                        <li class="">
                            <a href="">Financial Reports</a>
                        </li>
                    </ul>
                </li>
                <li id="settings-menu-item-parent" style="display: block" class="">
                    <a href="/settings">
                        <img src="{{ asset('imgs/setting.png') }}" alt="Settings">
                        Settings
                    </a>
                    <ul class="uk-nav-sub">
                        <li class="">
                            <a href="">System Configuration</a>
                        </li>
                        <li class="{{ Request::is('*user*') ? 'active' : '' }}">
                            <a href="/manage-users">Manage Users</a>
                        </li>
                        <li class="{{ Request::is('*role*') ? 'active' : '' }}">
                            <a href="/manage-roles">Manage Roles</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>