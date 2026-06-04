@extends('auth.authLayouts.authMaster')

@section('auth-main-contents')
    <link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">

    <div class="login-page">
        <div class="login-card">

            <div class="text-center mb-4">
                <img src="{{ asset('assets/img/logo.png') }}"
                     alt="eLive Card Logo"
                     class="login-logo">
            </div>

            <form action="{{ route('login') }}" method="POST" id="loginForm" autocomplete="on">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>

                    <input type="email"
                           class="form-control @error('email') is-invalid @enderror"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           placeholder="Enter email address"
                           autocomplete="email"
                           required
                           autofocus>

                    @error('email')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>

                    <input type="password"
                           class="form-control @error('password') is-invalid @enderror"
                           id="password"
                           name="password"
                           placeholder="Enter password"
                           autocomplete="current-password"
                           required>

                    @error('password')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <button type="submit"
                        class="btn-login"
                        id="loginButton">
                    Sign In
                </button>

                <div class="forgot-password">
                    <a href="{{ route('password.request') }}">
                        Forgot Password?
                    </a>
                </div>
            </form>

        </div>
    </div>

    <script src="{{ asset('assets/js/login.js') }}"></script>
@endsection