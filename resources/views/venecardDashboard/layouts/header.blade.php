<header id="header" class="header fixed-top d-flex align-items-center">

  <div class="d-flex align-items-center justify-content-between">
    <a href="#" class="logo d-flex align-items-center">
      <span class="d-none d-lg-block">eLive Card</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div><!-- End Logo -->

  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">
      <li class="nav-item dropdown pe-3">
        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
          <!-- <img src="{{ asset('assets/img/profile-img.jpg') }}" alt="Profile" class="rounded-circle"> -->
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0" />
            <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1" />
          </svg>
          <span class="d-none d-md-block dropdown-toggle ps-2">{{auth()->user()->name}}</span>
        </a><!-- End Profile Iamge Icon -->
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
          <li class="dropdown-header">
            <h6>{{auth()->user()->role}}</h6>
            <span>{{auth()->user()->email}}</span>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center" href="{{ route('profile.edit') }}">
              <i class="bi bi-person"></i>
              <span>My Profile</span>
            </a>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center" href="#" style="padding: 10px 20px;">
              <i class="bi bi-box-arrow-right text-danger"></i>
              <span class="ms-2">
                <form action="{{ route('logout') }}" method="POST" class="d-inline m-0">
                  @csrf
                  <button type="submit" class="btn btn-link text-danger p-0" style="text-decoration: none;">
                    Sign Out
                  </button>
                </form>
              </span>
            </a>
          </li>
        </ul><!-- End Profile Dropdown Items -->
      </li>
      <!-- End Profile Nav -->
    </ul>
  </nav><!-- End Icons Navigation -->
</header>