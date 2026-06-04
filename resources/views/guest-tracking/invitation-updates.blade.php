<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Invitation Progress</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
            --card-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --card-shadow-hover: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        body {
            background: #fafafa;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
            overflow: hidden;
        }

        .header-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .event-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .event-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .social-link {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            text-decoration: none;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .social-link:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .search-container {
            background: white;
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        .search-input {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: white;
        }

        .stats-container {
            background: white;
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        .stat-item {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 1rem;
            border-radius: 15px;
            text-align: center;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-shadow-hover);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }

        .table-container {
            background: white;
            padding: 1.5rem;
        }

        .custom-table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            border: none;
        }

        .custom-table thead th {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .custom-table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f1f3f4;
        }

        .custom-table tbody tr:hover {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .custom-table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border: none;
        }

        .invitee-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .invitee-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1rem;
        }

        .phone-badge {
            background: linear-gradient(135deg, var(--info-color), #5bc0de);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
            width: fit-content;
        }

        .phone-badge a {
            color: white;
            text-decoration: none;
        }

        .phone-badge a:hover {
            color: white;
            text-decoration: none;
        }

        .channel-status {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0;
            border-radius: 0;
            background: transparent;
            font-size: 0.9rem;
            font-weight: 500;
            border: none;
        }

        .status-success {
            color: #155724;
            background: transparent;
            border: none;
        }

        .status-failed {
            color: #721c24;
            background: transparent;
            border: none;
        }

        .status-pending {
            color: #856404;
            background: transparent;
            border: none;
        }

        .status-not-sent {
            color: #6c757d;
            background: transparent;
            border: none;
        }

        .reply-message {
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
            font-size: 0.9rem;
            color: #495057;
            max-width: 300px;
            word-wrap: break-word;
        }

        .empty-reply {
            color: #6c757d;
            font-style: italic;
        }

        .icon {
            width: 18px;
            height: 18px;
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 1rem;
                border-radius: 15px;
            }

            .header-section {
                padding: 1.5rem;
            }

            .event-title {
                font-size: 1.5rem;
            }

            .search-container,
            .stats-container,
            .table-container {
                padding: 1rem;
            }

            .custom-table thead th,
            .custom-table tbody td {
                padding: 0.75rem 0.5rem;
                font-size: 0.85rem;
            }

            .stat-item {
                margin-bottom: 1rem;
            }

            .reply-message {
                max-width: 200px;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 576px) {
            .custom-table {
                font-size: 0.8rem;
            }

            .custom-table thead th,
            .custom-table tbody td {
                padding: 0.5rem 0.25rem;
            }

            .invitee-name {
                font-size: 0.9rem;
            }

            .phone-badge {
                font-size: 0.8rem;
                padding: 0.2rem 0.5rem;
            }

            .status-item {
                font-size: 0.8rem;
                padding: 0.4rem;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="main-container">
            <!-- Header Section -->
            <div class="header-section">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1 class="event-title">
                            <i class="bi bi-calendar-event me-2"></i>
                            Event: <span class="text-warning">{{ $event->name }}</span>
                        </h1>
                        <p class="event-subtitle mb-0">
                            <i class="bi bi-people me-2"></i>
                            Track invitation progress and responses
                        </p>
                    </div>
                    <div class="col-lg-4 text-end">
                        <a href="https://instagram.com/e_live_card" class="social-link">
                            <i class="bi bi-instagram me-2"></i>
                            @e_live_card
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="stats-container">
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <span class="stat-number">{{ $double_invitees }}</span>
                            <span class="stat-label">Double</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <span class="stat-number">{{ $single_invitees }}</span>
                            <span class="stat-label">Single</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <span class="stat-number">{{ $group_invitees}}</span>
                            <span class="stat-label">Group Cards</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <span class="stat-number">{{ $total_invitees }}</span>
                            <span class="stat-label">Total Invitees</span>
                        </div>
                    </div>
                </div>
            </div>
            
              <!-- Search Section -->
            <div class="search-container">
                <div class="row">
                    <div class="col-12">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control search-input border-start-0" id="searchInput"
                                placeholder="Search by name or phone number...">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table custom-table mb-0">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="30%">Invitee</th>
                                <th width="35%">Channel Status</th>
                                <th width="30%">WhatsApp Reply</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invitees as $invitee)
                                <tr>
                                    <td>
                                        <span class="badge bg-primary rounded-pill">{{ $loop->iteration }}</span>
                                    </td>
                                    <td>
                                        <div class="invitee-info">
                                            <div class="invitee-name">{{ $invitee->guest_name }}</div>
                                            <div class="text-muted" style="font-size: 0.8rem; color: #6c757d;"> {{$invitee->card_type}}</div>
                                            <div class="phone-badge">
                                                <i class="bi bi-telephone me-1"></i>
                                                <a href="tel:0{{ $invitee->guest_phone }}">{{ '0' . $invitee->guest_phone }}</a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="channel-status">
                                            <div class="status-item {{ $invitee->sendwhatsappcard && $invitee->sendwhatsappcard->delivery_status === 'delivered' ? 'status-success' : ($invitee->sendwhatsappcard && $invitee->sendwhatsappcard->delivery_status === 'failed' ? 'status-failed' : ($invitee->sendwhatsappcard && $invitee->sendwhatsappcard->delivery_status ? 'status-pending' : 'status-not-sent')) }}">
                                                <i class="bi bi-whatsapp icon"></i>
                                                <span>{{ $invitee->sendwhatsappcard ? $invitee->sendwhatsappcard->delivery_status : '' }}</span>
                                            </div>
                                            <div class="status-item {{ $invitee->messagecard && $invitee->messagecard->delivery_status === 'delivered' ? 'status-success' : ($invitee->messagecard && $invitee->messagecard->delivery_status === 'failed' ? 'status-failed' : ($invitee->messagecard && $invitee->messagecard->delivery_status ? 'status-pending' : 'status-not-sent')) }}">
                                                <i class="bi bi-envelope icon"></i>
                                                <span>{{ $invitee->messagecard ? $invitee->messagecard->delivery_status : '' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($invitee->sendwhatsappcard && $invitee->sendwhatsappcard->reply_message)
                                            <div class="reply-message">
                                                <i class="bi bi-chat-dots me-2"></i>
                                                {{ $invitee->sendwhatsappcard->reply_message }}
                                            </div>
                                        @else
                                        <!--reply-->
                                            <div class="reply-message empty-reply">
                                                <i class="bi bi-dash-circle me-2"></i>
                                               <!--no reply yet-->
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const table = document.querySelector('table');
            const tbody = table.querySelector('tbody');
            const rows = tbody.querySelectorAll('tr');

            searchInput.addEventListener('input', function() {
                const filter = searchInput.value.trim().toLowerCase();
                rows.forEach(row => {
                    const tds = row.querySelectorAll('td');
                    if (!tds || tds.length < 2) return;
                    const namePhoneText = tds[1].textContent.toLowerCase();
                    if (filter === '' || namePhoneText.includes(filter)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });

            // Add smooth scrolling and enhanced interactions
            const statItems = document.querySelectorAll('.stat-item');
            statItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.02)';
                });

                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });
    </script>
</body>

</html>
