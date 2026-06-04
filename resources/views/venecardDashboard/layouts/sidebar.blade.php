<aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

        <li class="nav-item">
            <a class="nav-link " href="{{ route('dashboard') }}">
                <i class="bi bi-grid"></i>
                <span>Dashboard</span>
            </a>
        </li><!-- End Dashboard Nav -->

        {{-- event type section --}}
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#event-categories-section" data-bs-toggle="collapse"
                href="#">
                <i class="bi bi-tags"></i><span>Event categories</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="event-categories-section" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                <li>
                    <a href="{{ route('eventcategories.index') }}">
                        <i class="bi bi-circle"></i><span>Registed event categories</span>
                    </a>
                </li>
            </ul>
        </li>
        {{-- End event type section --}}

        {{-- manage event section --}}
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#manage-events" data-bs-toggle="collapse" href="#">
                <i class="bi bi-calendar-event"></i><span>Manage Events</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="manage-events" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                <li>
                    <a href="{{ route('events.index') }}">
                        <i class="bi bi-circle"></i><span>Events</span>
                    </a>
                </li>
                {{-- <li>
                    <a href="#">
                        <i class="bi bi-circle"></i><span>Scanner</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="bi bi-circle"></i><span>Events reports</span>
                    </a>
                </li> --}}
            </ul>
        </li>
        {{-- End manage event section --}}

        {{-- admin management section --}}
        <!--<li class="nav-item">-->
        <!--    <a class="nav-link collapsed" data-bs-target="#manage-admin" data-bs-toggle="collapse" href="">-->
        <!--        <i class="bi bi-people"></i><span>Admin management</span><i-->
        <!--            class="bi bi-chevron-down ms-auto"></i>-->
        <!--    </a>-->
        <!--    <ul id="manage-admin" class="nav-content collapse " data-bs-parent="#sidebar-nav">-->
        <!--        <li>-->
        <!--            <a href="#">-->
        <!--                <i class="bi bi-circle"></i><span>Kiwonyi Staff panel</span>-->
        <!--            </a>-->
        <!--        </li>-->
        <!--    </ul>-->
        <!--</li>-->
        {{-- End admin management section --}}

          {{-- send message section --}}
          <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#messaging-section" data-bs-toggle="collapse" href="#">
                <i class="bi bi-chat-dots"></i><span>Send message</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="messaging-section" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                <li>
                    <a href="{{ route('sendmessage.index') }}">
                        <i class="bi bi-circle"></i><span>Bulk message</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="bi bi-circle"></i><span>Whatsapp SMS</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="bi bi-circle"></i><span>Send Email</span>
                    </a>
                </li>
            </ul>
        </li>
        {{-- End send message section --}}

        {{-- thirdParties site section --}}
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#thirdPartiesSite-section" data-bs-toggle="collapse"
                href="#">
                <i class="bi bi-link-45deg"></i><span>Third parties sites</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="thirdPartiesSite-section" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                <li>
                    <a href=""
                        target="_blank">
                        <i class="bi bi-circle"></i><span>eLive SMS</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="bi bi-circle"></i><span>Bulk Whatsapp</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="bi bi-circle"></i><span>Payment API providers</span>
                    </a>
                </li>
            </ul>
        </li>
        {{-- End thirdParties site section --}}



        {{-- payment section --}}
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#payment-section" data-bs-toggle="collapse" href="#">
                <i class="bi bi-credit-card"></i><span>Payments</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="payment-section" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                <li>
                    <a href="#">
                        <i class="bi bi-circle"></i><span>Send payment USSD</span>
                    </a>
                </li>
            </ul>
        </li>
        {{-- End payment section --}}


    </ul>

</aside>
