<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Progress</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        :root {
            --elive-blue: #233F7E;
            --elive-blue-dark: #1D356A;
            --elive-orange: #F99A12;
            --success-color: #16A34A;
            --danger-color: #DC2626;
            --warning-color: #F59E0B;
            --muted-color: #6B7280;
            --soft-bg: #F8FAFC;
            --white: #FFFFFF;
            --border: #E5E7EB;
            --shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: var(--soft-bg);
            min-height: 100vh;
            font-family: Arial, sans-serif;
            color: #111827;
        }

        .main-container {
            background: var(--white);
            border-radius: 18px;
            box-shadow: var(--shadow);
            margin: 24px auto;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .header-section {
            background: linear-gradient(135deg, var(--elive-blue), var(--elive-blue-dark));
            color: white;
            padding: 28px;
        }

        .event-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .event-title span {
            color: var(--elive-orange);
        }

        .event-subtitle {
            font-size: 15px;
            opacity: 0.92;
            margin-bottom: 0;
        }

        .social-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.14);
            padding: 8px 14px;
            border-radius: 999px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .social-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.22);
        }

        .stats-container,
        .search-container,
        .table-container {
            background: white;
            padding: 20px;
            border-bottom: 1px solid var(--border);
        }

        .table-container {
            border-bottom: none;
        }

        .stat-item {
            background: #F9FAFB;
            padding: 16px;
            border-radius: 14px;
            text-align: center;
            border: 1px solid var(--border);
        }

        .stat-number {
            font-size: 24px;
            font-weight: 800;
            color: var(--elive-blue);
            display: block;
            line-height: 1;
        }

        .stat-label {
            margin-top: 8px;
            display: block;
            font-size: 13px;
            color: var(--muted-color);
            font-weight: 600;
        }

        .search-input {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 15px;
            background: #F9FAFB;
        }

        .search-input:focus {
            border-color: var(--elive-blue);
            box-shadow: 0 0 0 0.2rem rgba(35, 63, 126, 0.16);
            background: white;
        }

        .input-group-text {
            border: 1px solid var(--border);
            border-radius: 12px 0 0 12px;
            background: #F9FAFB;
        }

        .custom-table {
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid var(--border);
            margin-bottom: 0;
        }

        .custom-table thead th {
            background: var(--elive-blue);
            color: white;
            border: none;
            padding: 14px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }

        .custom-table tbody td {
            padding: 14px;
            vertical-align: middle;
            border-bottom: 1px solid #F1F5F9;
        }

        .custom-table tbody tr:last-child td {
            border-bottom: none;
        }

        .custom-table tbody tr:hover {
            background: #F8FAFC;
        }

        .invitee-name {
            font-weight: 700;
            color: #111827;
            font-size: 15px;
        }

        .card-type-text {
            font-size: 12px;
            color: var(--muted-color);
            margin-top: 3px;
        }

        .phone-badge {
            background: #EEF2FF;
            color: var(--elive-blue);
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            width: fit-content;
            margin-top: 7px;
        }

        .phone-badge a {
            color: var(--elive-blue);
            text-decoration: none;
        }

        .channel-status {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .status-item {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 700;
            width: fit-content;
            padding: 5px 10px;
            border-radius: 999px;
        }

        .status-success {
            color: #166534;
            background: #DCFCE7;
        }

        .status-failed {
            color: #991B1B;
            background: #FEE2E2;
        }

        .status-pending {
            color: #92400E;
            background: #FEF3C7;
        }

        .status-not-sent {
            color: #374151;
            background: #F3F4F6;
        }

        .reply-message {
            background: #F8FAFC;
            padding: 10px 12px;
            border-radius: 10px;
            border-left: 4px solid var(--elive-blue);
            font-size: 13px;
            color: #374151;
            max-width: 360px;
            word-wrap: break-word;
        }

        .empty-reply {
            color: var(--muted-color);
            font-style: italic;
            border-left-color: #D1D5DB;
        }

        .empty-state {
            display: none;
            text-align: center;
            padding: 24px;
            color: var(--muted-color);
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 12px;
                border-radius: 14px;
            }

            .header-section {
                padding: 20px;
            }

            .event-title {
                font-size: 21px;
            }

            .event-subtitle {
                font-size: 14px;
            }

            .social-link {
                margin-top: 14px;
            }

            .stats-container,
            .search-container,
            .table-container {
                padding: 14px;
            }

            .custom-table thead {
                display: none;
            }

            .custom-table,
            .custom-table tbody,
            .custom-table tr,
            .custom-table td {
                display: block;
                width: 100%;
            }

            .custom-table tr {
                border-bottom: 1px solid var(--border);
                padding: 12px;
            }

            .custom-table tbody td {
                border-bottom: none;
                padding: 7px 0;
            }

            .custom-table tbody td::before {
                content: attr(data-label);
                display: block;
                font-size: 11px;
                font-weight: 800;
                color: var(--muted-color);
                text-transform: uppercase;
                margin-bottom: 4px;
            }
        }
    </style>
</head>

<body>
@php
    $eventName = $event->name ?? 'Event';

    $doubleInvitees = $double_invitees ?? 0;
    $singleInvitees = $single_invitees ?? 0;
    $groupInvitees = $group_invitees ?? 0;
    $totalInvitees = $total_invitees ?? ($invitees->count() ?? 0);

    $formatPhone = function ($phone) {
        $phone = preg_replace('/\D+/', '', (string) $phone);

        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '255')) {
            return '+' . $phone;
        }

        if (str_starts_with($phone, '0')) {
            return $phone;
        }

        return '0' . $phone;
    };

    $statusClass = function ($status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'delivered', 'sent', 'success', 'successful', 'read' => 'status-success',
            'failed', 'error', 'undelivered', 'rejected' => 'status-failed',
            'pending', 'queued', 'processing', 'submitted' => 'status-pending',
            default => 'status-not-sent',
        };
    };

    $statusLabel = function ($status) {
        $status = trim((string) $status);

        return $status !== '' ? ucfirst($status) : 'Not sent';
    };
@endphp

<div class="container-fluid">
    <div class="main-container">

        <div class="header-section">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="event-title">
                        <i class="bi bi-calendar-event me-2"></i>
                        Event: <span>{{ $eventName }}</span>
                    </h1>
                    <p class="event-subtitle">
                        <i class="bi bi-people me-2"></i>
                        Track invitation progress, delivery status, and WhatsApp replies.
                    </p>
                </div>

                <div class="col-lg-4 text-lg-end">
                    <a href="https://instagram.com/e_live_card" target="_blank" rel="noopener" class="social-link">
                        <i class="bi bi-instagram"></i>
                        @e_live_card
                    </a>
                </div>
            </div>
        </div>

        <div class="stats-container">
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">{{ $doubleInvitees }}</span>
                        <span class="stat-label">Double</span>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">{{ $singleInvitees }}</span>
                        <span class="stat-label">Single</span>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">{{ $groupInvitees }}</span>
                        <span class="stat-label">Group Cards</span>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">{{ $totalInvitees }}</span>
                        <span class="stat-label">Total Invitees</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="search-container">
            <div class="input-group">
                <span class="input-group-text border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input
                    type="text"
                    class="form-control search-input border-start-0"
                    id="searchInput"
                    placeholder="Search by name, phone, card type, or status..."
                    autocomplete="off"
                >
            </div>
        </div>

        <div class="table-container">
            <div class="table-responsive">
                <table class="table custom-table" id="progressTable">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="30%">Invitee</th>
                            <th width="35%">Channel Status</th>
                            <th width="30%">WhatsApp Reply</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse ($invitees as $invitee)
                        @php
                            $guestName = $invitee->guest_name ?? $invitee->name ?? 'Unknown Invitee';
                            $cardType = $invitee->card_type ?? $invitee->cardtype ?? 'N/A';
                            $phoneDisplay = $formatPhone($invitee->guest_phone ?? $invitee->phone ?? '');

                            $whatsappStatus = $invitee->sendwhatsappcard->delivery_status ?? null;
                            $smsStatus = $invitee->messagecard->delivery_status ?? null;

                            $replyMessage = $invitee->sendwhatsappcard->reply_message ?? null;
                        @endphp

                        <tr>
                            <td data-label="#">
                                <span class="badge bg-primary rounded-pill">{{ $loop->iteration }}</span>
                            </td>

                            <td data-label="Invitee">
                                <div class="invitee-name">{{ $guestName }}</div>
                                <div class="card-type-text">{{ $cardType }}</div>

                                @if ($phoneDisplay)
                                    <div class="phone-badge">
                                        <i class="bi bi-telephone"></i>
                                        <a href="tel:{{ $phoneDisplay }}">{{ $phoneDisplay }}</a>
                                    </div>
                                @else
                                    <div class="phone-badge">
                                        <i class="bi bi-telephone"></i>
                                        No phone
                                    </div>
                                @endif
                            </td>

                            <td data-label="Channel Status">
                                <div class="channel-status">
                                    <div class="status-item {{ $statusClass($whatsappStatus) }}">
                                        <i class="bi bi-whatsapp"></i>
                                        <span>WhatsApp: {{ $statusLabel($whatsappStatus) }}</span>
                                    </div>

                                    <div class="status-item {{ $statusClass($smsStatus) }}">
                                        <i class="bi bi-chat-left-text"></i>
                                        <span>SMS: {{ $statusLabel($smsStatus) }}</span>
                                    </div>
                                </div>
                            </td>

                            <td data-label="WhatsApp Reply">
                                @if (!empty($replyMessage))
                                    <div class="reply-message">
                                        <i class="bi bi-chat-dots me-2"></i>
                                        {{ $replyMessage }}
                                    </div>
                                @else
                                    <div class="reply-message empty-reply">
                                        <i class="bi bi-dash-circle me-2"></i>
                                        No reply yet
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                No invitees found for this event.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

                <div class="empty-state" id="emptySearchState">
                    <i class="bi bi-search me-2"></i>
                    No matching invitees found.
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('progressTable');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const emptySearchState = document.getElementById('emptySearchState');

        searchInput.addEventListener('input', function () {
            const filter = searchInput.value.trim().toLowerCase();
            let visibleCount = 0;

            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                const shouldShow = filter === '' || text.includes(filter);

                row.style.display = shouldShow ? '' : 'none';

                if (shouldShow) {
                    visibleCount++;
                }
            });

            emptySearchState.style.display = visibleCount === 0 && rows.length > 0 ? 'block' : 'none';
        });
    });
</script>
</body>
</html>