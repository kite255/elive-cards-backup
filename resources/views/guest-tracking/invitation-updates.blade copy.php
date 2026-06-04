<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Invitation Progress</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .icon {
            vertical-align: middle;
            margin-right: 4px;
        }

        .status-success {
            color: #27ae60;
            font-weight: bold;
        }

        .status-failed {
            color: #e74c3c;
            font-weight: bold;
        }

        .ig-link {
            text-decoration: none;
            color: #E1306C;
            font-weight: 500;
        }

        .ig-link:hover {
            text-decoration: underline;
            color: #ad1457;
        }

        @media (max-width: 576px) {
            .container {
                padding: 0.5rem !important;
            }

            .card-title {
                font-size: 1.1rem;
                margin-left: 0.5rem !important;
            }

            .card {
                margin-bottom: 1rem !important;
            }

            .table th,
            .table td {
                font-size: 0.85rem;
                padding: 0.3rem;
            }

            .text-muted span {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-5">




        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="row">
                    <div class="row">
                        <div class="col-lg-12 d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">
                                Event: <span class="text-primary">Harusi ya Joseph</span>
                            </h4>
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#E1306C"
                                    viewBox="0 0 16 16">
                                    <path
                                        d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.917 3.917 0 0 0-1.417.923A3.927 3.927 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.916 3.916 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.926 3.926 0 0 0-.923-1.417A3.911 3.911 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0h.003zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599.28.28.453.546.598.92.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.47 2.47 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.478 2.478 0 0 1-.92-.598 2.48 2.48 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233 0-2.136.008-2.388.046-3.231.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92.28-.28.546-.453.92-.598.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045v.002zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92zm-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217zm0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334" />
                                </svg>
                                <a href="https://instagram.com/venecard_technologies" class="ig-link">@venecard_technologies</a>
                            </div>
                        </div>
                    </div>

                {{-- filter displayed invitees by name or phone number --}}
                <input type="text" class="form-control mt-2 mb-2" id="searchInput"
                    placeholder="Search by name or phone number...">
                {{-- filter displayed invitees by name or phone number --}}

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <div class="text-muted">
                                <span class="me-3">Double: <b>5</b></span>
                                <span class="me-3">Single: <b>12</b></span>
                                <span class="me-3">Group: <b>2</b></span>
                                <span>Total Invitees: <b>34</b></span>
                            </div>
                        </div>
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>S/N</th>
                                    <th>Invitee</th>
                                    <th>Channel</th>
                                    <th>whatsapp reply</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invitees as $invitee)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $invitee->guest_name }} <br>
                                            <span
                                                class="bg-info-subtle rounded px-2 py-1">{{ '0' . $invitee->guest_phone }}</span>
                                        </td>

                                        <td>
                                            <span>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="green" class="bi bi-whatsapp" viewBox="0 0 16 16">
                                                    <path
                                                        d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232" />
                                                </svg>
                                                {{ $invitee->sendwhatsappcard ? $invitee->sendwhatsappcard->delivery_status : '' }}
                                            </span><br>
                                            <span>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="green" viewBox="0 0 16 16">
                                                    <path
                                                        d="M2 2a2 2 0 0 0-2 2v8.01A2 2 0 0 0 2 14h5.5a.5.5 0 0 0 0-1H2a1 1 0 0 1-.966-.741l5.64-3.471L8 9.583l7-4.2V8.5a.5.5 0 0 0 1 0V4a2 2 0 0 0-2-2zm3.708 6.208L1 11.105V5.383l4.708 2.825zM1 4.217V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v.217l-7 4.2z" />
                                                </svg>
                                                {{ $invitee->messagecard->delivery_status ?? '' }}
                                        <td>{{ $invitee->sendwhatsappcard ? $invitee->sendwhatsappcard->reply_message : '' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS (optional, for interactivity) -->
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
                    // Get the text content of the invitee name and phone number (second td)
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
        });
    </script>
</body>

</html>
