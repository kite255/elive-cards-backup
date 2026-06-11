@extends('scan-card.layouts.master')

@section('content')
<style>
    body {
        background: #f8fafc;
    }

    .scan-wrapper {
        max-width: 760px;
        margin: 0 auto;
        padding: 24px 14px;
    }

    .scan-card {
        background: #ffffff;
        border-radius: 18px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.10);
        overflow: hidden;
        border: 1px solid #e5e7eb;
    }

    .scan-header {
        background: #233F7E;
        color: #ffffff;
        padding: 22px;
        text-align: center;
    }

    .scan-header h3 {
        margin: 0;
        font-weight: 700;
        font-size: 22px;
    }

    .scan-header p {
        margin: 8px 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .scan-body {
        padding: 20px;
    }

    #reader {
        width: 100%;
        max-width: 520px;
        min-height: 320px;
        margin: 0 auto;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        overflow: hidden;
        background: #f8f9fa;
    }

    .manual-box {
        margin-top: 22px;
        padding: 16px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
    }

    .manual-box label {
        font-weight: 700;
        color: #111827;
        margin-bottom: 8px;
        display: block;
    }

    .manual-box input {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        font-size: 16px;
    }

    .btn-scan {
        width: 100%;
        margin-top: 12px;
        border: none;
        border-radius: 10px;
        padding: 12px 14px;
        font-weight: 700;
        background: #F99A12;
        color: #111827;
        cursor: pointer;
    }

    .btn-scan:hover {
        background: #e48600;
    }

    .result-card {
        margin-bottom: 18px;
        border-radius: 16px;
        padding: 18px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    }

    .result-card.success {
        border-left: 6px solid #16a34a;
    }

    .result-card.error {
        border-left: 6px solid #dc2626;
    }

    .result-title {
        font-weight: 800;
        font-size: 20px;
        margin-bottom: 10px;
    }

    .success .result-title {
        color: #16a34a;
    }

    .error .result-title {
        color: #dc2626;
    }

    .guest-details {
        background: #f8fafc;
        padding: 14px;
        border-radius: 12px;
        margin-top: 12px;
    }

    .guest-details p {
        margin: 5px 0;
        color: #374151;
        font-size: 14px;
    }

    .progress-badge {
        display: inline-block;
        margin-top: 8px;
        padding: 7px 12px;
        border-radius: 999px;
        background: #dcfce7;
        color: #166534;
        font-weight: 700;
        font-size: 13px;
    }

    .error .progress-badge {
        background: #fee2e2;
        color: #991b1b;
    }

    .small-note {
        margin-top: 12px;
        color: #6b7280;
        font-size: 13px;
        text-align: center;
    }
</style>

@php
    $sessionGuest = session('guest');
    $sessionEvent = session('event') ?? $event;
    $scanAgainUrl = route('scan-cards.index', $sessionEvent->code ?? $event->code ?? $event->id);
@endphp

<div class="scan-wrapper">

    @if(session('success-message'))
        <div class="result-card success">
            <div class="result-title">{{ session('success-message') }}</div>

            @if($sessionGuest)
                <div class="guest-details">
                    <p><strong>Name:</strong> {{ $sessionGuest->guest_name ?? '-' }}</p>
                    <p><strong>Phone:</strong> {{ $sessionGuest->guest_phone ?? '-' }}</p>
                    <p><strong>Code:</strong> {{ $sessionGuest->invitation_code ?? '-' }}</p>
                    <p><strong>Card Type:</strong> {{ $sessionGuest->card_type ?? '-' }}</p>

                    @if(!empty($sessionGuest->scanned_time))
                        <p>
                            <strong>Scanned Time:</strong>
                            {{ \Carbon\Carbon::parse($sessionGuest->scanned_time)->format('d M Y, h:i A') }}
                        </p>
                    @endif

                    @if(session('scanning_progress'))
                        <span class="progress-badge">
                            {{ session('scanning_progress')['current'] ?? 0 }}
                            out of
                            {{ session('scanning_progress')['total'] ?? 1 }}
                        </span>
                    @endif
                </div>
            @endif

            <a href="{{ $scanAgainUrl }}" class="btn-scan" style="display:block;text-align:center;text-decoration:none;">
                Scan Another Card
            </a>
        </div>
    @endif

    @if(session('error-message'))
        <div class="result-card error">
            <div class="result-title">{{ session('error-message') }}</div>

            @if($sessionGuest)
                <div class="guest-details">
                    <p><strong>Name:</strong> {{ $sessionGuest->guest_name ?? '-' }}</p>
                    <p><strong>Phone:</strong> {{ $sessionGuest->guest_phone ?? '-' }}</p>
                    <p><strong>Code:</strong> {{ $sessionGuest->invitation_code ?? '-' }}</p>
                    <p><strong>Card Type:</strong> {{ $sessionGuest->card_type ?? '-' }}</p>

                    @if(!empty($sessionGuest->scanned_time))
                        <p>
                            <strong>Last Scanned:</strong>
                            {{ \Carbon\Carbon::parse($sessionGuest->scanned_time)->format('d M Y, h:i A') }}
                        </p>
                    @endif

                    @if(session('scanning_progress'))
                        <span class="progress-badge">
                            {{ session('scanning_progress')['current'] ?? 0 }}
                            out of
                            {{ session('scanning_progress')['total'] ?? 1 }}
                        </span>
                    @endif
                </div>
            @endif

            <a href="{{ $scanAgainUrl }}" class="btn-scan" style="display:block;text-align:center;text-decoration:none;">
                Try Again
            </a>
        </div>
    @endif

    <div class="scan-card">
        <div class="scan-header">
            <h3>Scan Invitation Card</h3>
            <p>{{ $event->name ?? 'Event' }} | {{ $event->code ?? $event->id }}</p>
        </div>

        <div class="scan-body">
            <div id="reader"></div>

            <form id="scanForm" method="GET" action="{{ route('verifycard', $event->id) }}">
                <input type="hidden" name="scanned_value" id="scanned_value">
            </form>

            <div class="manual-box">
                <form method="GET" action="{{ route('verifycard', $event->id) }}">
                    <label for="manual_code">Manual Scan / Enter Code</label>
                    <input
                        type="text"
                        name="scanned_value"
                        id="manual_code"
                        placeholder="Enter code or paste QR link, example: 200397"
                        autocomplete="off"
                    >
                    <button type="submit" class="btn-scan">
                        Verify Invitee
                    </button>
                </form>
            </div>

            <div class="small-note">
                Scanner accepts invitee code or full private link like /i/200397.
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>
    let alreadySubmitted = false;

    function submitScannedValue(value) {
        if (alreadySubmitted) {
            return;
        }

        value = String(value || '').trim();

        if (!value) {
            return;
        }

        alreadySubmitted = true;

        const input = document.getElementById('scanned_value');
        const form = document.getElementById('scanForm');

        input.value = value;
        form.submit();
    }

    function onScanSuccess(decodedText) {
        submitScannedValue(decodedText);
    }

    function onScanFailure(error) {
        // Keep silent. Scanner continuously retries.
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!window.Html5QrcodeScanner) {
            console.warn('QR scanner library not loaded.');
            return;
        }

        const scanner = new Html5QrcodeScanner(
            "reader",
            {
                fps: 10,
                qrbox: function(viewfinderWidth, viewfinderHeight) {
                    const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                    const qrboxSize = Math.floor(minEdge * 0.75);

                    return {
                        width: qrboxSize,
                        height: qrboxSize
                    };
                },
                rememberLastUsedCamera: true,
                supportedScanTypes: [
                    Html5QrcodeScanType.SCAN_TYPE_CAMERA
                ]
            },
            false
        );

        scanner.render(onScanSuccess, onScanFailure);
    });
</script>
@endsection