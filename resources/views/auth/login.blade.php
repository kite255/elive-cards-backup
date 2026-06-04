@extends('auth.authLayouts.authMaster')

@section('auth-main-contents')
<!-- login css -->
<link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm fade-in-up">
                <div class="card-body p-4">
                    <!-- Logo Section -->
                    <div class="text-center mb-4">
                        <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" class="img-fluid" style="max-width: 200px;">
                       
                    </div>
                    <!-- Login Form -->
                    <form action="{{ route('login') }}" method="POST" id="loginForm">
                        @csrf
                        <div class="mb-3">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com">
                                <label for="email">Email address</label>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password">
                                <label for="password">Password</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3" id="loginButton">Sign In</button>
                        <!-- Links -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="#" class="text-decoration-none text-muted small">Forgot Password?</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('assets/js/login.js') }}"></script>
@endsection