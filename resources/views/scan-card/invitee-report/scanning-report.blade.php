@extends('layouts.master')

@section('title', 'Scan Invitation Card')

@section('content')
@php
    $verifyUrlTemplate = route('verifycard', ['id' => '__CODE__']);
@endphp

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Scan Invitation Card</h5>
                </div>

                <div class="card-body">

                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="alert alert-warning">
                            {{ session('warning') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-bold">QR Scanner</label>

                        <div
                            id="reader"
                            style="width: 100%; min-height: 280px; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;"
                        ></div>

                        <div class="small text-muted mt-2">
                            Allow camera permission, then scan the invitee QR code.
                        </div>
                    </div>

                    <hr>

                    <form id="scanForm">
                        <div class="mb-3">
                            <label for="code" class="form-label fw-bold">
                                QR Code / Serial Number / Private Code
                            </label>

                            <input
                                type="text"
                                id="code"
                                class="form-control form-control-lg"
                                placeholder="Scan or type code here"
                                value="{{ old('code') }}"
                                autocomplete="off"
                                required
                                autofocus
                            >
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            Verify Invitation
                        </button>
                    </form>

                </div>
            </div>

            @isset($invitee)
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Invitee Details</h5>
                    </div>

                    <div class="card-body">
                        <table class="table table-bordered mb-0">
                            <tr>
                                <th style="width: 35%;">Name</th>
                                <td>{{ $invitee->name ?? '-' }}</td>
                            </tr>

                            <tr>
                                <th>Phone</th>
                                <td>{{ $invitee->phone ?? '-' }}</td>
                            </tr>

                            <tr>
                                <th>Card Type</th>
                                <td>{{ $invitee->card_type ?? $invitee->cardType->name ?? '-' }}</td>
                            </tr>

                            <tr>
                                <th>Allowed Guests</th>
                                <td>{{ $invitee->allowed_guests ?? $invitee->remaining_guests ?? '-' }}</td>
                            </tr>

                            <tr>
                                <th>Serial Number</th>
                                <td>{{ $invitee->serial_number ?? '-' }}</td>
                            </tr>

                            <tr>
                                <th>Status</th>
                                <td>
                                    @if (!empty($invitee->checked_in_at))
                                        <span class="badge bg-warning text-dark">Already Scanned</span>
                                    @else
                                        <span class="badge bg-success">Valid</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endisset

        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const codeInput = document.getElementById('code');
        const scanForm = document.getElementById('scanForm');
        const verifyUrlTemplate = @json($verifyUrlTemplate);

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

        function goToVerify(code) {
            code = extractCode(code);

            if (!code) {
                alert('Please scan or enter a valid code.');
                return;
            }

            const verifyUrl = verifyUrlTemplate.replace('__CODE__', encodeURIComponent(code));
            window.location.href = verifyUrl;
        }

        scanForm.addEventListener('submit', function (e) {
            e.preventDefault();
            goToVerify(codeInput.value);
        });

        function onScanSuccess(decodedText) {
            const code = extractCode(decodedText);

            if (!code) {
                return;
            }

            codeInput.value = code;

            if (window.html5QrCodeScanner) {
                window.html5QrCodeScanner.clear().catch(function () {});
            }

            goToVerify(code);
        }

        function onScanFailure(error) {
            // Ignore scanner noise.
        }

        if (document.getElementById('reader')) {
            window.html5QrCodeScanner = new Html5QrcodeScanner(
                "reader",
                {
                    fps: 10,
                    qrbox: {
                        width: 250,
                        height: 250
                    },
                    rememberLastUsedCamera: true
                },
                false
            );

            window.html5QrCodeScanner.render(onScanSuccess, onScanFailure);
        }
    });
</script>
@endsection