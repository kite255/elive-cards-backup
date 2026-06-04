@extends('scan-card.layouts.master')

@section('content')

    <style>
        .notification-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }

        .notification-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            margin-bottom: 20px;
            animation: slideIn 0.3s ease-out;
        }

        .notification-card .card-header {
            padding: 20px;
            text-align: center;
            background: transparent;
            border-bottom: none;
        }

        .notification-card .card-body {
            padding: 20px;
        }

        .success-icon {
            width: 60px;
            height: 60px;
            fill: #28a745;
        }

        .error-icon {
            width: 60px;
            height: 60px;
            fill: #dc3545;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-100px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>

    <div class="container">
        <div class="notification-container">
            <div class="row justify-content-center">
                <div class="col-12">
                    @if (session('success-message'))
                        <div class="notification-card">
                            <div class="card-header">
                                <svg class="success-icon" viewBox="0 0 24 24">
                                    <path
                                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
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
                                            {{ session('scanning_progress')['current'] }} out of
                                            {{ session('scanning_progress')['total'] }}
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
                                <button onclick="closeNotification()" class="btn btn-primary btn-block w-100">Scan Another
                                    Card</button>
                            </div>
                        </div>
                    @endif

                    @if (session('error-message'))
                        <div class="notification-card">
                            <div class="card-header">
                                <svg class="error-icon" viewBox="0 0 24 24">
                                    <path
                                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                                </svg>
                            </div>
                            <div class="card-body">
                                <p class="card-text">{{ session('error-message') }}</p>
                                @if (session('guest'))
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
                                <button onclick="closeNotification()" class="btn btn-primary error-btn btn-block w-100">Scan
                                    Another Card</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Main Content Section -->
        <div class="row mt-3 mb-5">
            <div class="col-md-12">
                <div class="card">
                    <!-- Card Header -->
                    <div class="card-header">
                        <h4>Invitees List</h4>
                        <!-- Logo Section -->
                        <div class="row mb-4">
                            <div class="col-12 text-center">
                                <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" class="img-fluid" style="max-width: 120px;">
                            </div>
                        </div>
                        <div class="mt-3">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search by name...">
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>SN</th>
                                        <th>Name</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="inviteesTableBody">
                                    @forelse($invitees as $key => $invitee)
                                        <tr class="invitee-row">
                                            <td>{{ $key + 1 }}</td>
                                            <td>
                                                <span class="guest-name">{{ $invitee->guest_name }}</span>
                                                <br>
                                                <span class="badge bg-secondary">{{ $invitee->card_type }}</span>
                                                @php
                                                    if (strtoupper($invitee->card_type) == 'SINGLE') {
                                                        $card_type = 1;
                                                    } elseif (strtoupper($invitee->card_type) == 'DOUBLE') {
                                                        $card_type = 2;
                                                    } else {
                                                        if (strtoupper(substr($invitee->card_type, 0, 4)) == 'WATU') {
                                                            // Remove first 5 digits and take the rest
                                                            $card_type = substr($invitee->card_type, 5);
                                                        }else{
                                                            $card_type = 'pending';
                                                        }
                                                    }
                                                @endphp
                                                <span class="badge bg-secondary">{{ $invitee->scanning_times }} /
                                                    {{ $card_type }}</span>
                                            </td>
                                            <td>
                                                <a href="{{ route('verifyinvitee', $invitee->id) }}"
                                                    class="btn btn-primary btn-sm"
                                                    onclick="return confirm('Are you sure you want to scan this invitee?')">
                                                    scan
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center">No invitees found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        $(document).ready(function() {
            // Initialize search functionality
            $("#searchInput").on("keyup", function() {
                const searchValue = $(this).val().toLowerCase();

                $("#inviteesTableBody tr.invitee-row").each(function() {
                    const guestName = $(this).find(".guest-name").text().toLowerCase();
                    $(this).toggle(guestName.includes(searchValue));
                });
            });
        });

        function closeNotification() {
            $(".notification-container").hide();
        }
    </script>


@endsection
