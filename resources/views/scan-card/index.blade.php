@extends('scan-card.layouts.master')

@section('content')
<style>
    body {
        background: #F8FAFC;
    }

    .scan-box {
        max-width: 520px;
        margin: 20px auto;
        padding: 16px;
    }

    .scan-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 8px 20px rgba(0,0,0,.06);
    }

    .title {
        font-size: 22px;
        font-weight: 700;
        color: #233F7E;
        margin-bottom: 4px;
        text-align: center;
    }

    .subtitle {
        color: #64748B;
        text-align: center;
        margin-bottom: 16px;
        font-size: 14px;
    }

    #reader {
        width: 100%;
        min-height: 300px;
        border-radius: 10px;
        overflow: hidden;
        background: #111827;
    }

    .status {
        margin-top: 12px;
        padding: 10px;
        border-radius: 8px;
        background: #EFF6FF;
        color: #1E3A8A;
        font-weight: 600;
        font-size: 14px;
        text-align: center;
    }

    .status.error {
        background: #FEE2E2;
        color: #991B1B;
    }

    .status.success {
        background: #DCFCE7;
        color: #166534;
    }

    .alert-box {
        padding: 12px;
        border-radius: 10px;
        margin-bottom: 14px;
        font-weight: 600;
    }

    .alert-success {
        background: #DCFCE7;
        color: #166534;
    }

    .alert-error {
        background: #FEE2E2;
        color: #991B1B;
    }

    .guest-info {
        margin-top: 10px;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.6;
    }

    .manual-form {
        margin-top: 16px;
    }

    .manual-form input {
        width: 100%;
        padding: 12px;
        border: 1px solid #CBD5E1;
        border-radius: 8px;
        font-size: 16px;
    }

    .btn-main {
        width: 100%;
        margin-top: 10px;
        padding: 12px;
        border: none;
        border-radius: 8px;
        background: #F99A12;
        color: #111827;
        font-weight: 700;
        cursor: pointer;
        text-align: center;
        text-decoration: none;
        display: block;
    }

    .btn-main:hover {
        background: #E48600;
        color: #111827;
        text-decoration: none;
    }
</style>

@php
    $sessionGuest = session('guest');
    $sessionEvent = session('event') ?? $event;
    $scanAgainUrl = route('scan-cards.index', $sessionEvent->code ?? $event->code ?? $event->id);
@endphp

<div class="scan-box">
    <div class="scan-card">
        <div class="title">Scan Card</div>
        <div class="subtitle">{{ $event->name ?? 'Event' }} • Use back camera</div>

        @if(session('success-message'))
            <div class="alert-box alert-success">
                ✅ {{ session('success-message') }}

                @if($sessionGuest)
                    <div class="guest-info">
                        <strong>Name:</strong> {{ $sessionGuest->guest_name ?? '-' }}<br>
                        <strong>Phone:</strong> {{ $sessionGuest->guest_phone ?? '-' }}<br>
                        <strong>Code:</strong> {{ $sessionGuest->invitation_code ?? '-' }}<br>
                        <strong>Card Type:</strong> {{ $sessionGuest->card_type ?? '-' }}

                        @if(!empty($sessionGuest->scanned_time))
                            <br><strong>Scanned:</strong> {{ \Carbon\Carbon::parse($sessionGuest->scanned_time)->format('d M Y, h:i A') }}
                        @endif

                        @if(session('scanning_progress'))
                            <br><strong>Progress:</strong>
                            {{ session('scanning_progress')['current'] ?? 0 }} / {{ session('scanning_progress')['total'] ?? 1 }}
                        @endif
                    </div>
                @endif

                <a href="{{ $scanAgainUrl }}" class="btn-main">Scan Another</a>
            </div>
        @endif

        @if(session('error-message'))
            <div class="alert-box alert-error">
                ❌ {{ session('error-message') }}

                @if($sessionGuest)
                    <div class="guest-info">
                        <strong>Name:</strong> {{ $sessionGuest->guest_name ?? '-' }}<br>
                        <strong>Phone:</strong> {{ $sessionGuest->guest_phone ?? '-' }}<br>
                        <strong>Code:</strong> {{ $sessionGuest->invitation_code ?? '-' }}<br>
                        <strong>Card Type:</strong> {{ $sessionGuest->card_type ?? '-' }}

                        @if(session('scanning_progress'))
                            <br><strong>Progress:</strong>
                            {{ session('scanning_progress')['current'] ?? 0 }} / {{ session('scanning_progress')['total'] ?? 1 }}
                        @endif
                    </div>
                @endif

                <a href="{{ $scanAgainUrl }}" class="btn-main">Try Again</a>
            </div>
        @endif

        <div id="reader"></div>
        <div id="cameraStatus" class="status">Opening back camera...</div>

        <form id="scanForm" method="GET" action="{{ route('verifycard', $event->id) }}">
            <input type="hidden" name="scanned_value" id="scanned_value">
        </form>

        <form class="manual-form" method="GET" action="{{ route('verifycard', $event->id) }}">
            <input type="text" name="scanned_value" placeholder="Enter invitation code manually" autocomplete="off">
            <button type="submit" class="btn-main">Verify Manually</button>
        </form>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>
    let scanner = null;
    let submitted = false;

    function setStatus(message, type = '') {
        const status = document.getElementById('cameraStatus');
        status.className = 'status ' + type;
        status.textContent = message;
    }

    function submitScan(value) {
        if (submitted) return;

        value = String(value || '').trim();
        if (!value) return;

        submitted = true;
        setStatus('QR detected. Verifying...', 'success');

        document.getElementById('scanned_value').value = value;

        if (scanner) {
            scanner.stop().finally(function () {
                document.getElementById('scanForm').submit();
            });
        } else {
            document.getElementById('scanForm').submit();
        }
    }

    function startScanner() {
        if (!window.isSecureContext) {
            setStatus('Camera requires HTTPS.', 'error');
            return;
        }

        scanner = new Html5Qrcode('reader');

        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            rememberLastUsedCamera: false
        };

        scanner.start(
            { facingMode: { exact: 'environment' } },
            config,
            submitScan,
            function () {}
        ).then(function () {
            setStatus('Back camera ready. Scan QR code.', 'success');
        }).catch(function () {
            scanner.start(
                { facingMode: 'environment' },
                config,
                submitScan,
                function () {}
            ).then(function () {
                setStatus('Back camera ready. Scan QR code.', 'success');
            }).catch(function () {
                setStatus('Camera failed. Allow camera permission and refresh.', 'error');
            });
        });
    }

    document.addEventListener('DOMContentLoaded', startScanner);
</script>
@endsection
