<style>
    .guest-slide-modal {
        position: fixed;
        top: 0;
        right: -34vw;
        width: 34vw;
        max-width: 560px;
        height: 100vh;
        background-color: #f9f9f9;
        box-shadow: -3px 0 12px rgba(0, 0, 0, 0.18);
        padding: 20px;
        overflow-y: auto;
        transition: right 0.35s ease-in-out;
        z-index: 1060;
    }

    .guest-slide-modal.active {
        right: 0;
    }

    .guest-slide-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        z-index: 1055;
        display: none;
    }

    .guest-slide-backdrop.active {
        display: block;
    }

    @media screen and (max-width: 992px) {
        .guest-slide-modal {
            width: 92vw;
            right: -92vw;
        }
    }

    .file-upload-container {
        min-height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #ddd;
        padding: 8px;
    }

    .excel-sample {
        font-size: 0.75rem;
        color: #198754;
    }

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
        color: #212529;
        text-decoration: none;
    }

    .action-btn svg {
        width: 15px;
        height: 15px;
    }

    .action-btn:focus {
        outline: none;
        box-shadow: none;
    }

    .guest-status-dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        display: inline-block;
        margin-right: 5px;
    }

    .guest-status-dot.generated {
        background: #198754;
    }

    .guest-status-dot.pending {
        background: #ffd700;
    }

    .table td,
    .table th {
        vertical-align: middle;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="row">
    <div id="guestSlideBackdrop" class="guest-slide-backdrop"></div>

    <div id="guestModal" class="guest-slide-modal" aria-hidden="true">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Invitees</h5>
            <button id="closeModal" type="button" class="btn btn-link p-0 text-dark" aria-label="Close add guest panel">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-x-square" viewBox="0 0 16 16">
                    <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
                </svg>
            </button>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Import Excel</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('guests.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" value="{{ $event->id }}" name="event_id">

                    <div class="file-upload-container rounded bg-light mt-2">
                        <input type="file" class="form-control" name="guestExcelFile" accept=".xlsx,.xls" required>
                    </div>

                    <button type="submit" class="btn btn-success mt-2">Upload Excel</button>
                </form>

                <p class="excel-sample mt-2 mb-0">
                    <a href="{{ route('downloadexcelsample') }}">Download Excel sample</a>
                </p>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Add New Guest</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('guests.addsingleguest', encrypt($event->id)) }}" method="POST" autocomplete="off">
                    @csrf

                    <div class="mb-3">
                        <input type="text" class="form-control" id="guestName" name="guestName" placeholder="Guest name" required>
                    </div>

                    <div class="mb-3">
                        <input type="text" class="form-control" id="guest_phone" name="guestPhone" placeholder="Phone number eg. 07.. / 06.." maxlength="10" required>
                    </div>

                    <div class="mb-3">
                        <select class="form-select" id="cardType" name="cardType" required>
                            <option value="" disabled selected>Select card type</option>
                            <option value="SINGLE">Single</option>
                            <option value="DOUBLE">Double</option>
                            <option value="GROUP">Group</option>
                            <option value="MCHANGO">Mchango</option>
                        </select>
                    </div>

                    <div class="mb-3" id="groupSizeContainer" style="display: none;">
                        <input type="number" class="form-control" id="groupSize" name="groupSize" placeholder="Enter number of people in group" min="1">
                    </div>

                    <div class="mb-3">
                        <input type="text" class="form-control" id="note" name="note" placeholder="Enter a note">
                    </div>

                    <button type="submit" class="btn btn-primary">Save Guest</button>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="smsCardsModal" tabindex="-1" aria-labelledby="smsCardsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('sendcard.sendMessagecard', $event->id) }}" method="POST" id="smsCardsForm">
                    @csrf
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="smsCardsModalLabel">Send SMS Cards</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <label class="form-label">Choose invitees</label>
                        <select class="form-select" name="invitees_to_send" required>
                            <option value="" selected disabled>Choose invitees</option>
                            <option value="failed">Failed WhatsApp cards</option>
                            <option value="sent">Sent WhatsApp status</option>
                            <option value="all">Send to all</option>
                        </select>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success" onclick="confirmAction('messagecard', document.getElementById('smsCardsForm'))">Send SMS</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 d-flex justify-content-end flex-wrap gap-2">
            <form action="{{ route('sendcard.sendthankyousms', $event->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="button" class="btn btn-success btn-sm d-flex align-items-center" onclick="confirmAction('thankyousms', this.form)">
                    <i class="bi bi-envelope-heart me-1"></i>Thank You SMS
                </button>
            </form>

            <form action="{{ route('sendcard.sendremindersms', $event->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="button" class="btn btn-success btn-sm d-flex align-items-center" onclick="confirmAction('remindersms', this.form)">
                    <i class="bi bi-bell me-1"></i>Reminder SMS
                </button>
            </form>

            <button type="button" class="btn btn-success btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#smsCardsModal">
                <i class="bi bi-chat-dots me-1"></i>SMS Cards
            </button>

            <form action="{{ route('sendcard.sendwhatsappcard', $event->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="button" class="btn btn-success btn-sm d-flex align-items-center" onclick="confirmAction('whatsapp', this.form)">
                    <i class="bi bi-whatsapp me-1"></i>WhatsApp
                </button>
            </form>

            <button type="button" class="btn btn-primary btn-sm d-flex align-items-center" id="addGuestButton">
                <i class="bi bi-person-plus-fill me-1"></i>Add Guest
            </button>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('events.show', encrypt($event->id)) }}" class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <select class="form-select" name="sort_by">
                            <option value="">Sort Name By</option>
                            <option value="asc" {{ request('sort_by') == 'asc' ? 'selected' : '' }}>A-Z</option>
                            <option value="desc" {{ request('sort_by') == 'desc' ? 'selected' : '' }}>Z-A</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <select class="form-select" name="card_type">
                            <option value="">Card Type</option>
                            <option value="single" {{ request('card_type') == 'single' ? 'selected' : '' }}>Single</option>
                            <option value="double" {{ request('card_type') == 'double' ? 'selected' : '' }}>Double</option>
                            <option value="group" {{ request('card_type') == 'group' ? 'selected' : '' }}>Group</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">Message Status</option>
                            <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Read</option>
                            <option value="replied" {{ request('status') == 'replied' ? 'selected' : '' }}>Replied</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <input type="text" class="form-control" name="search" placeholder="Search name or phone" value="{{ request('search') }}">
                    </div>

                    <div class="col-md-2">
                        <a href="{{ route('events.show', encrypt($event->id)) }}" class="btn btn-secondary w-100">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-start mt-2">
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
                    <th scope="col">Channel</th>
                    <th scope="col">Sent date</th>
                    <th scope="col">Updated At</th>
                    <th scope="col">Status</th>
                    <th scope="col">Reply</th>
                    <th scope="col">Scanned Time</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($guests as $guest)
                    @php
                        $rawPhone = preg_replace('/\D+/', '', $guest->guest_phone ?? '');

                        if (str_starts_with($rawPhone, '255')) {
                            $waPhone = $rawPhone;
                        } elseif (str_starts_with($rawPhone, '0')) {
                            $waPhone = '255' . substr($rawPhone, 1);
                        } else {
                            $waPhone = '255' . $rawPhone;
                        }

                        $whatsappStatus = $guest->sendwhatsappcard->delivery_status ?? null;
                        $smsStatus = $guest->messagecard->delivery_status ?? null;
                    @endphp

                    <tr>
                        <th>{{ $loop->iteration + ($guests->currentPage() - 1) * $guests->perPage() }}</th>

                        <td>
                            <span class="guest-status-dot {{ $guest->pdfcard ? 'generated' : 'pending' }}"></span>
                            {{ $guest->guest_name }}
                        </td>

                        <td>
                            <a href="https://wa.me/{{ $waPhone }}" target="_blank">{{ $waPhone }}</a>
                        </td>

                        <td>{{ $guest->card_type }}</td>
                        <td>{{ $guest->note }}</td>

                        <td>
                            <i class="bi bi-whatsapp"></i><br>
                            <i class="bi bi-envelope"></i>
                        </td>

                        <td style="font-size: 0.8rem; line-height: 1.2rem; white-space: nowrap;">
                            <div>{{ optional($guest->sendwhatsappcard)->updated_at }}</div>
                            <div>{{ optional($guest->messagecard)->created_at }}</div>
                        </td>

                        <td style="font-size: 0.8rem; white-space: nowrap;">
                            {{ $guest->updated_at ? $guest->updated_at->format('Y-m-d H:i:s') : '-' }}
                        </td>

                        <td style="font-size: 0.8rem;">
                            @if ($whatsappStatus === 'failed')
                                <i class="bi bi-x-lg text-danger"></i>
                            @elseif ($whatsappStatus === 'sent')
                                <i class="bi bi-check-lg"></i>
                            @elseif ($whatsappStatus === 'delivered')
                                <i class="bi bi-check2-all"></i>
                            @elseif ($whatsappStatus === 'read')
                                <i class="bi bi-check2-all text-primary"></i>
                            @endif

                            {{ $whatsappStatus }}<br>
                            {{ $smsStatus }}
                        </td>

                        <td>{{ optional($guest->sendwhatsappcard)->reply_message }}</td>
                        <td>{{ $guest->scanned_time ?: '' }}</td>

                        <td>
                            <div style="display: flex; flex-wrap: nowrap; gap: 0;">
                                <a href="{{ route('resendcard.resendwhatsappcard', encrypt($guest->id)) }}" class="action-btn" title="Send WhatsApp" onclick="confirmLinkAction(event, 'resend WhatsApp card')">
                                    <i class="bi bi-whatsapp"></i>
                                </a>

                                <a href="{{ route('resendcard.resendsmscard', encrypt($guest->id)) }}" class="action-btn" title="Send SMS" onclick="confirmLinkAction(event, 'resend SMS card')">
                                    <i class="bi bi-envelope"></i>
                                </a>

                                <a href="{{ route('guests.edit', encrypt($guest->id)) }}" class="action-btn" title="Edit Info" onclick="confirmLinkAction(event, 'edit information')">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <a href="{{ route('downloadinviteecard', strrev($guest->invitation_code)) }}" class="action-btn" title="Download card" onclick="confirmLinkAction(event, 'download card')">
                                    <i class="bi bi-download"></i>
                                </a>

                                <form id="delete-form-{{ $guest->id }}" action="{{ route('guests.destroy', encrypt($guest->id)) }}" method="POST" style="display: none;">
                                    @csrf
                                    @method('DELETE')
                                </form>

                                <a href="#" class="action-btn text-danger" title="Delete" onclick="confirmDeleteInvitee(event, '{{ $guest->id }}')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">No invitees found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-center">
    {{ $guests->links() }}
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const guestModal = document.getElementById('guestModal');
        const guestBackdrop = document.getElementById('guestSlideBackdrop');
        const addGuestButton = document.getElementById('addGuestButton');
        const closeModalButton = document.getElementById('closeModal');
        const cardType = document.getElementById('cardType');

        function openGuestPanel() {
            guestModal.classList.add('active');
            guestBackdrop.classList.add('active');
            guestModal.setAttribute('aria-hidden', 'false');
            localStorage.setItem('guestModalState', 'open');
        }

        function closeGuestPanel() {
            guestModal.classList.remove('active');
            guestBackdrop.classList.remove('active');
            guestModal.setAttribute('aria-hidden', 'true');
            localStorage.setItem('guestModalState', 'closed');
        }

        function toggleGroupSize() {
            const groupSizeContainer = document.getElementById('groupSizeContainer');
            const groupSizeInput = document.getElementById('groupSize');

            if (cardType.value === 'GROUP') {
                groupSizeContainer.style.display = 'block';
                groupSizeInput.required = true;
            } else {
                groupSizeContainer.style.display = 'none';
                groupSizeInput.required = false;
                groupSizeInput.value = '';
            }
        }

        addGuestButton?.addEventListener('click', openGuestPanel);
        closeModalButton?.addEventListener('click', closeGuestPanel);
        guestBackdrop?.addEventListener('click', closeGuestPanel);
        cardType?.addEventListener('change', toggleGroupSize);

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeGuestPanel();
            }
        });

        if (localStorage.getItem('guestModalState') === 'open') {
            openGuestPanel();
        }

        toggleGroupSize();
    });

    function confirmAction(actionType, form) {
        let title = 'Confirm Action';
        let text = 'Are you sure you want to continue?';

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
                text = 'Are you sure you want to send SMS cards to the selected invitees?';
                break;
            case 'whatsapp':
                title = 'Send WhatsApp';
                text = 'Are you sure you want to send WhatsApp cards to all guests?';
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

    function confirmDeleteInvitee(event, guestId) {
        event.preventDefault();

        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to delete this invitee?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(`delete-form-${guestId}`).submit();
            }
        });
    }
</script>
