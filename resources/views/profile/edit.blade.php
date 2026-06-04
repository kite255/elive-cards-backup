<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Profile') }}</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <main class="py-4 w-100">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">

                    <div class="card mb-4">
                        <div class="card-body">
                            {{-- <div class="max-w-xl">
                                @include('profile.partials.update-profile-information-form')
                            </div> --}}

                            {{-- <div class="max-w-xl">
                                @include('profile.partials.update-password-form')
                            </div> --}}
                            @if (session('status') === 'password-updated')
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ __('Password updated successfully!') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <form method="post" action="{{ route('password.update') }}">
                                @csrf
                                @method('put')

                                <div class="mb-3">
                                    <label for="update_password_current_password" class="form-label">{{ __('Current Password') }}</label>
                                    <input id="update_password_current_password" name="current_password" type="password" class="form-control" autocomplete="current-password" />
                                    @if ($errors->updatePassword->has('current_password'))
                                        <div class="text-danger small">{{ $errors->updatePassword->first('current_password') }}</div>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <label for="update_password_password" class="form-label">{{ __('New Password') }}</label>
                                    <input id="update_password_password" name="password" type="password" class="form-control" autocomplete="new-password" />
                                    @if ($errors->updatePassword->has('password'))
                                        <div class="text-danger small">{{ $errors->updatePassword->first('password') }}</div>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <label for="update_password_password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
                                    <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" />
                                    @if ($errors->updatePassword->has('password_confirmation'))
                                        <div class="text-danger small">{{ $errors->updatePassword->first('password_confirmation') }}</div>
                                    @endif
                                </div>

                                <div class="d-flex align-items-center gap-3">
                                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <div class="max-w-xl">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div> --}}
                </div>
            </div>
        </div>
    </main>
    <!-- Bootstrap JS (optional, for interactive components) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
