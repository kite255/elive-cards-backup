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
        0% { transform: scale(0) rotate(-180deg); }
        50% { transform: scale(1.2) rotate(0deg); }
        100% { transform: scale(1) rotate(0deg); }
    }

    .success-icon path { fill: #198754; }
    .error-icon path { fill: #dc3545; }

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
        justify-content: center;
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
        text-align: left;
    }

    .guest-details p {
        font-size: 0.95rem;
    }

    .guest-details i {
        width: 20px;
        text-align: center;
    }

    #reader {
        width: 100%;
        max-width: 520px;
        min-height: 320px;
        margin: 0 auto;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        background: #f8f9fa;
    }
</style>

@php
    $sessionGuest = session('guest');
    $sessionEvent = session('event') ?? $event;
    $scanAgainUrl = route('scan-cards.index', $sessionEvent->code ?? $event->code ?? $event->id);
@endphp

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
                        <p class="card-text text-success fw-bold">{{ session('success-message') }}</p>

                        @if($sessionGuest)
                            <div class="guest-info mb-4">
                                <h4 class="guest-name mb-2">{{ $sessionGuest->guest_name }}</h4>

                                @if(session('scanning_progress'))
                                    <div class="scanning-progress mb-3">
                                        <span class="badge bg-success">
                                            {{ session('scanning_progress')['current'] ?? 0 }}
                                            out of
                                            {{ session('scanning_progress')['total'] ?? 1 }}
                                        </span>
                                    </div>
                                @endif

                                <div class="guest-details">
                                    <p class="text-muted mb-1">
                                        <i class="fas fa-phone me-2"></i>
                                        {{ $sessionGuest->guest_phone ?? '-' }}
                                    </p>

                                    <p class="text-muted mb-1">
                                        <i class="fas fa-ticket-alt me-2"></i>
                                        {{ $sessionGuest->invitation_code ?? '-' }}
                                    </p>

                                    <p class="text-muted mb-0">
                                        <i class="fas fa-id-card me-2"></i>
                                        {{ $sessionGuest->card_type ?? '-' }}
                                    </p>
                                </div>
                            </div>

                            @if(!empty($sessionGuest->scanned_time))
                                <p class="mt-2 mb-2">
                                    Scanned time:
                                    {{ \Carbon\Carbon::parse($sessionGuest->scanned_time)->format('d M Y, h:i A') }}
                                </p>
                            @endif
                        @endif

                        <a href="{{ $scanAgainUrl }}" class="btn btn-primary btn-block w-100">
                            Scan Another Card
                        </a>
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
                        <p class="card-text text-danger fw-bold">{{ session('error-message') }}</p>

                        @if($sessionGuest)
                            <div class="guest-details mb-3">
                                <p class="text-muted mb-1">
                                    <i class="fas fa-user me-2"></i>
                                    {{ $sessionGuest->guest_name ?? '-' }}
                                </p>

                                <p class="text-muted mb-1">
                                    <i class="fas fa-phone me-2"></i>
                                    {{ $sessionGuest->guest_phone ?? '-' }}
                                </p>

                                <p class="text-muted mb-1">
                                    <i class="fas fa-ticket-alt me-2"></i>
                                    {{ $sessionGuest->invitation_code ?? '-' }}
                                </p>

                                <p class="text-muted mb-0">
                                    <i class="fas fa-id-card me-2"></i>
                                    {{ $sessionGuest->card_type ?? '-' }}
                                </p>
                            </div>

                            @if(!empty($sessionGuest->scanned_time))
                                <p class="mt-2 mb-2">
                                    Last scanned:
                                    {{ \Carbon\Carbon::parse($sessionGuest->scanned_time)->format('d M Y, h:i A') }}
                                </p>
                            @endif
                        @endif

                        <a href="{{ $scanAgainUrl }}" class="btn btn-primary error-btn btn-block w-100">
                            Scan Another Card
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row justify-content-center align-items-center">
        <div class="col-md-8 text-center">
            <h4 class="mb-3">Scan Invitation Card</h4>
            <div id="reader"></div>
            <p class="text-muted mt-2 mb-0">Allow camera permission and scan the invitee QR code.</p>
        </div>
    </div>
</div>

<div class="container mt-2 mb-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <form action="{{ route('verifycard', $event->id) }}" method="GET" class="d-flex">
                <input
                    type="text"
                    name="scanned_value"
                    class="form-control"
                    placeholder="Enter invitation code"
                    required
                    autocomplete="off"
                >
                <button type="submit" class="btn btn-success ms-2">
                    Verify
                </button>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('assets/js/script.js') }}"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const notificationContainer = document.querySelector('.notification-container');

        if (notificationContainer && notificationContainer.querySelector('.notification-card')) {
            setTimeout(() => {
                notificationContainer.classList.add('show');
            }, 100);
        }

        function extractCode(scannedText) {
            if (!scannedText) {
                return '';
            }

            scannedText = scannedText.trim();

            try {
                const url = new URL(scannedText);
                const parts = url.pathname.split('/').filter(Boolean);

                if (parts.length > 0) {
                    return parts[parts.length - 1];
                }
            } catch (e) {
                return scannedText;
            }

            return scannedText;
        }

        let html5Qrcode = null;
        let isScanning = false;

        function startScanner() {
            if (notificationContainer && notificationContainer.querySelector('.notification-card')) {
                return;
            }

            if (typeof Html5Qrcode === 'undefined') {
                console.error('Html5Qrcode is not loaded. Check assets/js/script.js');
                return;
            }

            if (!html5Qrcode) {
                html5Qrcode = new Html5Qrcode('reader');
            }

            if (isScanning) {
                return;
            }

            const qrCodeSuccessCallback = (decodedText) => {
                const code = extractCode(decodedText);

                if (!code) {
                    return;
                }

                isScanning = false;

                html5Qrcode.stop()
                    .catch(() => {})
                    .finally(() => {
                        window.location.href = '{{ route("verifycard", $event->id) }}?scanned_value=' + encodeURIComponent(code);
                    });
            };

            const config = {
                fps: 10,
                qrbox: {
                    width: 250,
                    height: 250
                }
            };

            html5Qrcode.start(
                { facingMode: "environment" },
                config,
                qrCodeSuccessCallback
            ).then(() => {
                isScanning = true;
            }).catch((error) => {
                console.error('Camera start failed:', error);
            });
        }

        startScanner();
    });
</script>
@endsection