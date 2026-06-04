@extends('scan-card.layouts.master')

@section('content')
<style>
    .notification-container {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1000;
        width: 80vw;
        max-width: 500px;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease-in-out;
    }

    .notification-container.show {
        opacity: 1;
        visibility: visible;
    }

    .notification-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        transform: scale(0.9);
        transition: transform 0.3s ease-in-out;
        border: none;
    }

    .notification-container.show .notification-card {
        transform: scale(1);
    }

    .card-header {
        padding: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        background: transparent;
        border-bottom: none;
    }

    .success-icon,
    .error-icon {
        width: 48px;
        height: 48px;
        animation: iconPop 0.5s ease-out;
    }

    @keyframes iconPop {
        0% {
            transform: scale(0) rotate(-180deg);
        }

        50% {
            transform: scale(1.2) rotate(0deg);
        }

        100% {
            transform: scale(1) rotate(0deg);
        }
    }

    .success-icon path {
        fill: #198754;
    }

    .error-icon path {
        fill: #dc3545;
    }

    .card-body {
        padding: 2rem;
        text-align: center;
    }

    .card-text {
        font-size: 1.1rem;
        margin: 1rem 0;
        color: #333;
    }

    .btn-primary {
        transition: all 0.3s ease;
        padding: 0.8rem 2rem;
        border-radius: 50px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: linear-gradient(45deg, #198754, #20c997);
        border: none;
        box-shadow: 0 4px 15px rgba(25, 135, 84, 0.2);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(25, 135, 84, 0.3);
    }

    .error-btn {
        background: linear-gradient(45deg, #dc3545, #ff6b6b);
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
    }

    .error-btn:hover {
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
    }

    .guest-info {
        text-align: left;
        padding: 0 1rem;
    }

    .guest-name {
        color: #198754;
        font-weight: 600;
        font-size: 1.5rem;
    }

    .scanning-progress {
        display: flex;
        align-items: center;
    }

    .badge {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .guest-details {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 10px;
        margin-top: 1rem;
    }

    .guest-details p {
        font-size: 0.95rem;
    }

    .guest-details i {
        width: 20px;
        text-align: center;
    }
</style>


    <script src="{{asset('assets/js/script.js')}}"></script>

    <div class="notification-container">
        <div class="row justify-content-center">
            <div class="col-12">
                @if(session('success-message'))
                <div class="notification-card">
                    <div class="card-header">
                        <svg class="success-icon" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                        </svg>
                    </div>
                    <div class="card-body">
                        <div class="guest-info mb-4">
                            @php
                            $guest = session('guest');
                            @endphp
                            <h4 class="guest-name mb-2">{{ $guest->guest_name }}</h4>
                            <div class="scanning-progress mb-3">
                                <span class="badge bg-success">
                                    {{ session('scanning_progress')['current'] }} out of {{ session('scanning_progress')['total'] }}
                                </span>
                            </div>
                            <div class="guest-details">
                                <p class="text-muted mb-1">
                                    <i class="fas fa-phone me-2"></i>
                                    {{ $guest->guest_phone }}
                                </p>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-ticket-alt me-2"></i>
                                    {{ $guest->invitation_code }}
                                </p>
                            </div>
                        </div>
                       <p class="mt-2 mb-2">Scanned time: {{ \Carbon\Carbon::parse($guest->scanned_time)->format('d M Y, h:i A') }}</p>
                        <a href="{{ route('scan-cards.index', session('event')->code) }}" class="btn btn-primary btn-block w-100">Scan Another Card</a>
                    </div>
                </div>
                @endif

                @if(session('error-message'))
                <div class="notification-card">
                    <div class="card-header">
                        <svg class="error-icon" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                        </svg>
                    </div>
                    <div class="card-body">
                        <p class="card-text">{{ session('error-message') }}</p>
                        @if(session('guest'))
                        @php
                        $guest = session('guest');
                        @endphp
                        <div class="guest-details mb-3">
                            <p class="text-muted mb-1">
                                <i class="fas fa-user me-2"></i>
                                {{ $guest->guest_name }}
                            </p>
                            <p class="text-muted mb-1">
                                <i class="fas fa-phone me-2"></i>
                                {{ $guest->guest_phone }}
                            </p>
                            <p class="text-muted mb-0">
                                <i class="fas fa-ticket-alt me-2"></i>
                                {{ $guest->invitation_code }}
                            </p>
                        </div>
                        @endif
                         <p class="mt-2 mb-2">Scanned time: {{ \Carbon\Carbon::parse($guest->scanned_time)->format('d M Y, h:i A') }}</p>
                        <a href="{{ route('scan-cards.index', session('event')->code) }}" class="btn btn-primary error-btn btn-block w-100">Scan Another Card</a>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center align-items-center">
            <div id="reader" class="col-md-6"></div>
        </div>
        <div class="row justify-content-center mt-3">
            <div id="show" style="display: none;" class="text-center">
                <h4>Scanned Result</h4>
                <p style="color: blue;" id="result"></p>
                <p style="color: blue;" id="result1"></p>
            </div>
        </div>
    </div>

    <!-- scan card by invitation code -->
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form action="{{ route('verifycard', $event->id) }}" method="GET" class="d-flex">
                    <!--@csrf-->
                    <input type="number" name="scanned_value" class="form-control" placeholder="Enter invitation code" required>
                    <button type="submit" class="btn btn-success ms-2">Verify</button>
                </form>
            </div>
        </div>
    </div>



    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Show notification with animation
        document.addEventListener('DOMContentLoaded', function() {
            const notificationContainer = document.querySelector('.notification-container');
            if (notificationContainer) {
                setTimeout(() => {
                    notificationContainer.classList.add('show');
                }, 100);
            }
        });

        let html5Qrcode = null;
        let isScanning = false;

        function startScanner() {
            if (!html5Qrcode) {
                html5Qrcode = new Html5Qrcode('reader');
            }

            const qrCodeSuccessCallback = (decodedText, decodedResult) => {
                if (decodedText) {
                    // Redirect to the route with the scanned value
                    window.location.href = '{{ route("verifycard",$event->id) }}?scanned_value=' + decodedText;
                    html5Qrcode.stop();
                    isScanning = false;
                }
            }

            const config = {
                fps: 10,
                qrbox: {
                    width: 250,
                    height: 250
                }
            }

            html5Qrcode.start({
                facingMode: "environment"
            }, config, qrCodeSuccessCallback);
            isScanning = true;
        }

        // Only start scanning if there's no notification
        const notificationContainer = document.querySelector('.notification-container');
        if (!notificationContainer || !notificationContainer.querySelector('.notification-card')) {
            startScanner();
        }

        // Add click event listeners to "Scan Another Card" buttons
        document.querySelectorAll('.btn-primary').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                // Start scanner before redirecting
                startScanner();
                // Then redirect
                window.location.href = href;
            });
        });
    </script>

    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

@endsection