<style>
    /* Button and basic modal styling */
    .modal-slide-in {
        position: fixed;
        top: 0;
        right: -30vw;
        /* Start off-screen */
        width: 30vw;
        height: 100vh;
        background-color: #f9f9f9;
        box-shadow: -3px 0 8px rgba(0, 0, 0, 0.2);
        padding: 20px;
        overflow-y: auto;
        transition: right 0.5s ease-in-out;
        z-index: 1000;
    }

    /* Add media query for small screens */
    @media screen and (max-width: 768px) {
        .modal-slide-in {
            width: 90vw;
            right: -90vw;
        }
    }

    .modal-slide-in.active {
        right: 0;
    }

    .close-btn {
        padding: 5px 10px;
        border: none;
        cursor: pointer;
    }

    .file-upload-container {
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #ddd;
    }

    .excel-sample {
        font-size: 0.7rem;
        color: #198754;
    }

    /* Dropdown menu styles */
    .dropdown-menu {
        position: absolute;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        padding: 8px 0;
        min-width: 150px;
        z-index: 1000;
        display: none;
    }

    .dropdown-menu.show {
        display: block;
    }

    .dropdown-menu a {
        display: block;
        padding: 8px 16px;
        color: #333;
        text-decoration: none;
        transition: background-color 0.2s;
    }

    .dropdown-menu a:hover {
        background-color: #f5f5f5;
    }

    .three-dots-container {
        position: relative;
        cursor: pointer;
    }

    /* Custom action button style for compact layout */
    .action-btn {
        padding: 2px 4px;
        margin: 0 1px;
        background: none;
        border: none;
        box-shadow: none;
        min-width: unset;
        min-height: unset;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .action-btn svg {
        width: 14px;
        height: 14px;
    }

    .action-btn:focus {
        outline: none;
        box-shadow: none;
    }

    .action-btn.disabled,
    .action-btn-disabled {
        opacity: 0.35;
        pointer-events: none;
        cursor: not-allowed;
    }

    .card-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.72rem;
        line-height: 1;
        padding: 4px 7px;
        border-radius: 999px;
        white-space: nowrap;
    }

    .card-status-ready {
        color: #047857;
        background: #d1fae5;
    }

    .card-status-missing {
        color: #92400e;
        background: #fef3c7;
    }
</style>

<!-- Add SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="row">
    <!-- modal to add guest  -->
    <div id="guestModal" class="modal-slide-in">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="mb-0"></h3>
            <button id="closeModal" class="btn btn-link p-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                    class="bi bi-x-square" viewBox="0 0 16 16">
                    <path
                        d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                    <path
                        d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
                </svg>
            </button>
        </div>

        <div class="row">
            <h5 class="card-title mb-0">Import Excel</h5>
            <form action="{{ route('guests.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="file-upload-container rounded bg-light mt-2">
                    <input type="text" value="{{ $event->id }}" name="event_id" hidden>
                    <input type="file" class="form-control" name="guestExcelFile" accept=".xlsx,.xls">
                </div>
                <button type="submit" class="btn btn-success mt-2">Upload Excel</button>
            </form>
            <p class="excel-sample">
                <a href="{{ route('downloadexcelsample') }}">Download Excel sample</a>
            </p>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Add New Guest</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('guests.addsingleguest', encrypt($event->id)) }}" class="mt-3" method="POST"
                    autocomplete="off">
                    @csrf
                    <div class="mb-3">
                        <input type="text" class="form-control" id="guestName" name="guestName"
                            placeholder="Guest name" required>
                    </div>

                    <div class="mb-3">
                        <input type="text" class="form-control" id="guest_phone" name="guestPhone"
                            placeholder="Phone number e.g. 07.. / 06.." maxlength="12" required>
                    </div>

                    <div class="mb-3">
                        <select class="form-select" id="cardType" name="cardType" required
                            onchange="toggleGroupSize()">
                            <option value="" disabled selected>Select card type</option>
                            <option value="SINGLE">Single</option>
                            <option value="DOUBLE">Double</option>
                            <option value="GROUP">Group</option>
                            <option value="MCHANGO">Mchango</option>
                        </select>
                    </div>

                    <div class="mb-3" id="groupSizeContainer" style="display: none;">
                        <input type="number" class="form-control" id="groupSize" name="groupSize"
                            placeholder="Enter number of people in group" min="1">
                    </div>
                    
                    <div class="mb-3">
                        <input type="text" class="form-control" id="note" name="note"
                            placeholder="Enter a note">
                    </div>

                    <div class="">
                        <button type="submit" class="btn btn-primary">Save Guest</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal to send SMS -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Send SMS Cards</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('sendcard.sendMessagecard', $event->id) }}" method="POST">
                        @csrf
                        <select class="form-select" name="invitees_to_send" required>
                            <option value="" selected disabled>choose invitees</option>
                            <option value="failed">failed whatsapp cards</option>
                            <option value="sent">sent whatsapp status</option>
                            <option value="all">send to ALL</option>
                        </select>

                </div>
                <div class="modal-footer mt-5">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Send SMS</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 d-flex justify-content-end flex-nowrap gap-2">
            <form action="{{ route('sendcard.sendthankyousms', $event->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="button" class="btn btn-success btn-sm d-flex align-items-center" onclick="confirmAction('thankyousms', this.form)"><i class="bi bi-envelope-heart me-1"></i>Thank You SMS</button>
            </form>
            <form action="{{ route('sendcard.sendremindersms', $event->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="button" class="btn btn-success btn-sm d-flex align-items-center" onclick="confirmAction('remindersms', this.form)"><i class="bi bi-bell me-1"></i>Reminder SMS</button>
            </form>
            <form action="" method="POST" class="d-inline">
                <button type="button" class="btn btn-success btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#exampleModal">
                    <i class="bi bi-chat-dots me-1"></i>SMS Cards
                </button>
            </form>
            <form action="{{ route('sendcard.sendwhatsappcard', $event->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="button" class="btn btn-success btn-sm d-flex align-items-center" onclick="confirmAction('whatsapp', this.form)"><i class="bi bi-whatsapp me-1"></i>WhatsApp</button>
            </form>
            <form class="d-inline">
                <button type="button" class="btn btn-primary btn-sm d-flex align-items-center" id="addGuestButton">
                    <i class="bi bi-person-plus-fill me-1"></i>Add Guest
                </button>
            </form>
        </div>
        <!-- add single event guest -->

    </div>

</div>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('events.show', encrypt($event->id)) }}"
                    class="row g-3 align-items-center mt-3">
                    <div class="row">
                        <div class="col-md-3">
                            <select class="form-select" name="sort_by">
                                <option value="">Sort Name By</option>
                                <option value="asc" {{ request('sort_by') == 'asc' ? 'selected' : '' }}>A-Z</option>
                                <option value="desc" {{ request('sort_by') == 'desc' ? 'selected' : '' }}>Z-A
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select class="form-select" name="card_type">
                                <option value="">Card Type</option>
                                <option value="single" {{ request('card_type') == 'single' ? 'selected' : '' }}>Single
                                </option>
                                <option value="double" {{ request('card_type') == 'double' ? 'selected' : '' }}>Double
                                </option>
                                <option value="group" {{ request('card_type') == 'group' ? 'selected' : '' }}>Group
                                </option>
                            </select>
                        </div>

                        {{-- <div class="col-md-3">
                            <select class="form-select" name="channel">
                                <option value="">Channel</option>
                                <option value="whatsapp" {{ request('channel') == 'whatsapp' ? 'selected' : '' }}>
                        WhatsApp Only</option>
                        <option value="sms" {{ request('channel') == 'sms' ? 'selected' : '' }}>SMS Only
                        </option>
                        <option value="both" {{ request('channel') == 'both' ? 'selected' : '' }}>Both
                        </option>
                        </select>
                    </div> --}}

                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">Message Status</option>
                            <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent
                            </option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed
                            </option>
                            <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>
                                Delivered</option>
                            <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Read
                            </option>
                            <option value="replied" {{ request('status') == 'replied' ? 'selected' : '' }}>Replied
                            </option>
                        </select>
                    </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search"
                            placeholder="Search name or phone" value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('events.show', encrypt($event->id)) }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                </div>
            </div>
            </form>
        </div>
    </div>
    <div class="d-flex justify-content-start">
        {{ $guests->links() }}
    </div>
    <div class="table-responsive">
        <table class="table table-striped mt-2">
            <thead>
                <tr>
                    <th scope="col">SN</th>
                    <th scope="col">Name</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Card Type</th>
                    <th scope="col">Note</th>
                    <th scope="col">Card Status</th>
                    <th scope="col">Channel</th>
                    <th scope="col">Sent date</th>
                    <th scope="col">Status</th>
                    <th scope="col">Reply</th>
                    <th scope="col">Scanned Time</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>


                @foreach ($guests as $guest)
                @php
                    $guestPdf = $guest->pdfcard ?? null;
                    $cardRelativePath = null;
                    $cardPublicPath = null;
                    $cardStoragePath = null;
                    $guestCardFileExists = false;

                    if ($guestPdf && ! empty($guestPdf->pdf_name) && ! empty($event->code)) {
                        $cardRelativePath = 'cards/PDFCards/' . $event->code . '/' . ltrim($guestPdf->pdf_name, '/');
                        $cardPublicPath = public_path('storage/' . $cardRelativePath);
                        $cardStoragePath = storage_path('app/public/' . $cardRelativePath);
                        $guestCardFileExists = file_exists($cardPublicPath) || file_exists($cardStoragePath);
                    }

                    $whatsappPhone = preg_replace('/\D/', '', (string) $guest->guest_phone);

                    if (str_starts_with($whatsappPhone, '0')) {
                        $whatsappPhone = '255' . substr($whatsappPhone, 1);
                    } elseif ($whatsappPhone !== '' && ! str_starts_with($whatsappPhone, '255')) {
                        $whatsappPhone = '255' . $whatsappPhone;
                    }
                @endphp
                <tr>
                    <th>{{ $loop->iteration + ($guests->currentPage() - 1) * $guests->perPage() }}</th>

                    <td>
                        @if ($guestCardFileExists)
                        <svg width="10" height="10" style="margin-right: 5px;">
                            <circle cx="5" cy="5" r="5" fill="green" />
                        </svg>
                        @else
                        <svg width="10" height="10" style="margin-right: 5px;">
                            <circle cx="5" cy="5" r="5" fill="#ffd700" />
                        </svg>
                        @endif
                        {{ $guest->guest_name }}
                    </td>
                    <td>
                        @if ($whatsappPhone)
                            <a href="https://wa.me/{{ $whatsappPhone }}" target="_blank">{{ $whatsappPhone }}</a>
                        @else
                            <span class="text-muted">No phone</span>
                        @endif
                    </td>
                    <td>{{ $guest->card_type }}</td>
                    <td>{{ $guest->note }}</td>
                    <td>
                        @if ($guestCardFileExists)
                            <span class="card-status-badge card-status-ready">Ready</span>
                        @else
                            <span class="card-status-badge card-status-missing" title="Generate or regenerate this guest card before sending SMS/WhatsApp.">Not generated</span>
                        @endif
                    </td>
                    <td>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                            fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
                            <path
                                d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232" />
                        </svg><br>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                            fill="currentColor" viewBox="0 0 16 16">
                            <path
                                d="M2 2a2 2 0 0 0-2 2v8.01A2 2 0 0 0 2 14h5.5a.5.5 0 0 0 0-1H2a1 1 0 0 1-.966-.741l5.64-3.471L8 9.583l7-4.2V8.5a.5.5 0 0 0 1 0V4a2 2 0 0 0-2-2zm3.708 6.208L1 11.105V5.383l4.708 2.825zM1 4.217V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v.217l-7 4.2z" />
                        </svg>
                    </td>
                   <td style="font-size: 0.8rem; line-height: 1.2rem; white-space: nowrap;">
                      <div>{{ $guest->sendwhatsappcard->updated_at ?? '' }}</div>
                      <div>{{ $guest->messagecard->created_at ?? '' }}</div>
                   </td>


                    <td style="font-size: 0.8rem;">
                        @if ($guest->sendwhatsappcard)
                        @if ($guest->sendwhatsappcard->delivery_status == 'failed')
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            fill="red" viewBox="0 0 16 16">
                            <path
                                d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z" />
                        </svg>
                        @elseif($guest->sendwhatsappcard->delivery_status == 'sent')
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            fill="currentColor" viewBox="0 0 16 16">
                            <path
                                d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
                        </svg>
                        @elseif($guest->sendwhatsappcard->delivery_status == 'delivered')
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            fill="currentColor" viewBox="0 0 16 16">
                            <path
                                d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0l7-7zm-4.208 7-.896-.897.707-.707.543.543 6.646-6.647a.5.5 0 0 1 .708.708l-7 7a.5.5 0 0 1-.708 0z" />
                        </svg>
                        @elseif($guest->sendwhatsappcard->delivery_status == 'read')
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            fill="#0d6efd" viewBox="0 0 16 16">
                            <path
                                d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0l7-7zm-4.208 7-.896-.897.707-.707.543.543 6.646-6.647a.5.5 0 0 1 .708.708l-7 7a.5.5 0 0 1-.708 0z" />
                        </svg>
                        @endif
                        {{ $guest->sendwhatsappcard->delivery_status }}
                        @endif
                        <br>
                        {{ $guest->messagecard->delivery_status ?? '' }}
                    </td>
                    <td>{{ $guest->sendwhatsappcard ? $guest->sendwhatsappcard->reply_message : '' }}</td>
                    <td>{{ $guest->scanned_time ? $guest->scanned_time : '' }}</td>

                    <td>
                        <div style="display: flex; flex-wrap: nowrap; gap: 0;">
                            <a href="{{ $guestCardFileExists ? route('resendcard.resendwhatsappcard', encrypt($guest->id)) : '#' }}"
                                class="action-btn {{ $guestCardFileExists ? '' : 'disabled action-btn-disabled' }}" title="{{ $guestCardFileExists ? 'Send WhatsApp' : 'Generate card first' }}"
                                onclick="@if($guestCardFileExists) confirmLinkAction(event, 'resend WhatsApp card') @else event.preventDefault(); Swal.fire('Card not generated', 'Generate this guest card first, then send WhatsApp.', 'warning'); @endif">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                    fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
                                    <path
                                        d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232" />
                                </svg>
                            </a>
                            <a href="{{ $guestCardFileExists ? route('resendcard.resendsmscard', encrypt($guest->id)) : '#' }}" class="action-btn {{ $guestCardFileExists ? '' : 'disabled action-btn-disabled' }}"
                                title="{{ $guestCardFileExists ? 'Send SMS' : 'Generate card first' }}" onclick="@if($guestCardFileExists) confirmLinkAction(event, 'resend SMS card') @else event.preventDefault(); Swal.fire('Card not generated', 'Generate this guest card first, then send SMS.', 'warning'); @endif">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                    fill="currentColor" viewBox="0 0 16 16">
                                    <path
                                        d="M2 2a2 2 0 0 0-2 2v8.01A2 2 0 0 0 2 14h5.5a.5.5 0 0 0 0-1H2a1 1 0 0 1-.966-.741l5.64-3.471L8 9.583l7-4.2V8.5a.5.5 0 0 0 1 0V4a2 2 0 0 0-2-2zm3.708 6.208L1 11.105V5.383l4.708 2.825zM1 4.217V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v.217l-7 4.2z" />
                                </svg>
                            </a>
                            <a href="{{ route('guests.edit', encrypt($guest->id)) }}" class="action-btn"
                                title="Edit Info" onclick="confirmLinkAction(event, 'edit information')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                    fill="currentColor" viewBox="0 0 16 16">
                                    <path
                                        d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z" />
                                </svg>
                            </a>
                            <a href="{{ $guestCardFileExists ? route('downloadinviteecard', strrev($guest->invitation_code)) : '#' }}" class="action-btn {{ $guestCardFileExists ? '' : 'disabled action-btn-disabled' }}"
                                title="{{ $guestCardFileExists ? 'Download card' : 'Generate card first' }}" onclick="@if($guestCardFileExists) confirmLinkAction(event, 'download card') @else event.preventDefault(); Swal.fire('Card not generated', 'Generate this guest card first before downloading.', 'warning'); @endif">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
                                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5" />
                                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z" />
                                </svg>
                            </a>
                            <form id="delete-form-{{ $guest->id }}" action="{{ route('guests.destroy', encrypt($guest->id)) }}" method="POST" style="display: none;">
                                @csrf
                                @method('DELETE')
                            </form>
                            <a href="#" class="action-btn" title="Delete"
                                onclick="event.preventDefault(); 
                                    Swal.fire({
                                        title: 'Are you sure?',
                                        text: 'Do you want to delete this invitee?',
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonColor: '#d33',
                                        cancelButtonColor: '#3085d6',
                                        confirmButtonText: 'Yes, delete it!'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            document.getElementById('delete-form-{{ $guest->id }}').submit();
                                        }
                                    });">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z" />
                                    <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z" />
                                </svg>
                            </a>
                        </div>
                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</div>

<!-- Pagination Links -->
<div class="d-flex justify-content-center">
    {{ $guests->links() }}
</div>





<script>
    const guestModal = document.getElementById('guestModal');
    const addGuestButton = document.getElementById('addGuestButton');
    const closeModalButton = document.getElementById('closeModal');

    // Function to open the modal and store state
    function openModal() {
        if (! guestModal) {
            return;
        }

        guestModal.classList.add('active');
        localStorage.setItem('modalState', 'open');
    }

    // Function to close the modal and update state
    function closeModal() {
        if (! guestModal) {
            return;
        }

        guestModal.classList.remove('active');
        localStorage.setItem('modalState', 'closed');
    }

    if (addGuestButton) {
        addGuestButton.addEventListener('click', openModal);
    }

    if (closeModalButton) {
        closeModalButton.addEventListener('click', closeModal);
    }

    // Check localStorage for modal state on page load
    document.addEventListener('DOMContentLoaded', function() {
        if (guestModal && localStorage.getItem('modalState') === 'open') {
            guestModal.classList.add('active');
        }

        // Add click event listeners for three dots menus
        document.querySelectorAll('.three-dots-container').forEach(container => {
            container.addEventListener('click', function(e) {
                e.stopPropagation();
                const dropdown = this.querySelector('.dropdown-menu');
                dropdown.classList.toggle('show');
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.three-dots-container')) {
                document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });
    });

    // Function to toggle group size input visibility
    function toggleGroupSize() {
        const cardType = document.getElementById('cardType').value;
        const groupSizeContainer = document.getElementById('groupSizeContainer');
        const groupSizeInput = document.getElementById('groupSize');

        if (cardType === 'GROUP') {
            groupSizeContainer.style.display = 'block';
            groupSizeInput.required = true;
        } else {
            groupSizeContainer.style.display = 'none';
            groupSizeInput.required = false;
        }
    }

    // Function to handle confirmation dialogs
    function confirmAction(actionType, form) {
        let title, text;

        switch (actionType) {
            case 'thankyousms':
                title = 'Send Thank You SMS';
                text = 'Are you sure you want to send thank you SMS to all guests?';
                break;
            case 'remindersms':
                title = 'Send Reminder SMS';
                text = 'Are you sure you want to send reminder SMS to all guests?';
                break;
            case 'messagecard':
                title = 'Send SMS Cards';
                text = 'Are you sure you want to send SMS cards to all guests?';
                break;
            case 'whatsapp':
                title = 'Send WhatsApp';
                text = 'Are you sure you want to send WhatsApp messages to all guests?';
                break;
        }

        Swal.fire({
            title: title,
            text: text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, send it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }

    // Function to handle confirmation for link actions
    function confirmLinkAction(event, actionType) {
        event.preventDefault();
        const link = event.currentTarget;

        Swal.fire({
            title: `Confirm ${actionType}`,
            text: `Are you sure you want to ${actionType}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, proceed!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = link.href;
            }
        });
    }
</script>