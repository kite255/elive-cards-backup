@extends('auth.authLayouts.authMaster')

<style>
    .error-message {
        color: red;
        size: 8px;
        margin-inline: 3px;
    }
</style>


@section('auth-main-contents')
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card-disabled p-4" style="width: 22rem;">
            <div class="logo d-flex justify-content-center">
                <img src="{{ asset('assets/img/leoLogo.png') }}" alt="" style="width: 100px;">
            </div>
            <h3 class="row d-flex justify-content-center">register</h3>
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div id="error-message" class="alert alert-danger" style="display: none;"></div>
            <div id="success-message" class="alert alert-success" style="display: none;"></div>
            
            <form id="registerForm" action="{{ route('register') }}" method="POST" autocomplete="off">
                @csrf
                <div class="mb-3">
                    <input type="text" class="form-control" name="firstName" placeholder="first name">
                    {{-- @error('firstName')
                    <p class="error-message">{{ $message }}</p>
                @enderror --}}
                </div>
                <div class="mb-3">
                    <input type="text" class="form-control" name="lastName" placeholder="last name">
                    {{-- @error('lastName')
                    <p class="error-message">{{ $message }}</p>
                @enderror --}}
                </div>
                <div class="mb-3">
                    <input type="text" class="form-control" name="phoneNumber" placeholder="Enter your phone number">
                    {{-- @error('phoneNumber')
                    <p class="error-message">{{ $message }}</p>
                @enderror --}}
                </div>
                <div class="mb-3">
                    <input type="text" class="form-control" name="email" placeholder="Enter your email">
                    {{-- @error('email')
                    <p class="error-message">{{ $message }}</p>
                @enderror --}}
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Enter your password">
                    {{-- @error('password')
                    <p class="error-message">{{ $message }}</p>
                @enderror --}}
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="password_confirmation"
                        placeholder="confirm your password">
                </div>
                <button type="submit" class="btn btn-primary w-100">Register</button>
                <div class="d-flex justify-content-between mt-3">
                    <a href="{{ route('login') }}" class="text-decoration-none">already registered? Login</a>
                </div>
            </form>

            <!-- Include jQuery -->
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

            <script>
                $(document).ready(function() {
                            $('#registerForm').on('submit', function(e) {
                                    e.preventDefault(); // Prevent default form submission

                                    $.ajax({
                                            url: "{{ route('register') }}", // Endpoint URL
                                            type: "POST",
                                            data: $(this).serialize(), // Serialize form data
                                            success: function(response) {
                                                window.location.href = response.redirect;
                                            },


                                            // error: function(xhr) {
                                            //     // Handle errors (e.g., validation errors)
                                            //     let errors = xhr.responseJSON.errors;
                                            //     $('.error-message').remove(); // Clear previous error messages
                                            //     $.each(errors, function(key, value) {
                                            //         $('input[name="' + key + '"]').after(
                                            //             '<p class="error-message text-danger">' + value[0] +
                                            //             '</p>');
                                            //     });

                                                error: function(response) {
                                                    // Handle validation errors
                                                    if (response.status === 422) {
                                                        let errors = response.responseJSON.errors;
                                                        let errorList = '';
                                                        $.each(errors, function(key, value) {
                                                            errorList += '<li>' + value[0] + '</li>';
                                                        });
                                                        $('#error-message').html('<ul>' + errorList + '</ul>')
                                                        .show();
                                                    }

                                                }
                                            });
                                    });
                            });
            </script>

        </div>
    </div>
@endsection
