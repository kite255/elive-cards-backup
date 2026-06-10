<div class="row">
    <div class="col-lg-7 col-md-12">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $designWidth = 420;
            $designHeight = 620;

            $eventCard = optional($event->eventcard);
            $hasCard = ! empty($eventCard->card_name);

            $formAction = $hasCard
                ? route('card.update', encrypt($event->eventcard->id))
                : route('card.store');

            /*
             * X/Y positions are now PIXELS on the 420x620 designer canvas.
             * Preview and download use the same canvas, so positions match exactly.
             */
            $guestX = old('guestNameX', $eventCard->guestPositionX ?? 210);
            $guestY = old('guestNameY', $eventCard->guestPositionY ?? 115);

            $cardTypeX = old('cardTypeX', $eventCard->cardTypePositionX ?? 210);
            $cardTypeY = old('cardTypeY', $eventCard->cardTypePositionY ?? 500);

            $qrX = old('qrCodeX', $eventCard->qrCodePositionX ?? 315);
            $qrY = old('qrCodeY', $eventCard->qrCodePositionY ?? 500);

            $qrSize = old('qrCodeSize', $eventCard->qrCodeSize ?? 72);

            $guestNameFontSize = old('guestNameFontSize', $eventCard->guest_name_font_size ?? 12);
            $cardTypeFontSize = old('guestCardtypeFontSize', $eventCard->guest_cardtype_font_size ?? 8);

            $guestColor = old('guestNameColor', $eventCard->guest_name_color ?? '#000000');
            $cardTypeColor = old('guestCardtypeColor', $eventCard->guest_cardtype_color ?? '#000000');

            $qrForegroundColor = old('qrCodeForegroundColor', $eventCard->qrCodeForegroundColor ?? '#000000');
            $qrBackgroundColor = old('qrCodeBackgroundColor', $eventCard->qrCodeBackgroundColor ?? '#ffffff');
            $qrEyeColor = old('qrCodeEyeColor', $eventCard->qrCodeEyeColor ?? $qrForegroundColor);

            $qrPosition = old('qrcodePosition', $eventCard->qrcode_cardtype_position ?? 'custom');

            $smsCard = $event->smsCard ?? $event->eventSMScard ?? null;
            $smsReminder = $event->smsReminder ?? $event->remindersms ?? null;
            $smsWelcoming = $event->smsWelcoming ?? $event->welcomingsms ?? null;
            $smsThankyou = $event->smsThankyou ?? $event->thankyousms ?? null;

            $defaultCardMessage = "Habari #NAME#,\n\nUnakaribishwa kwenye #EVENT#\nCARD: #CARD#\nCODE : #CODE#\nTAREHE : #TAREHE#\nUKUMBI: #VENUE#\nLINK: #CARDLINK#";

            $defaultReminderMessage = "Habari #NAME#,\n\nUnakumbushwa kuhudhuria #EVENT#, kesho tarehe #TAREHE# kuanzia saa 12:30 jioni kama ilivyoelezwa kwenye kadi yako.";

            $defaultWelcomeMessage = "Karibu sana #NAME#. Tunakukaribisha kwa heshima na furaha tele kushiriki katika sherehe hii.\nTunakutakia wakati mzuri uliojaa furaha, upendo na kumbukumbu nzuri. Asante kwa uwepo wako.";

            $defaultThankYouMessage = "Habari #NAME#, Asante kwa kuungana nasi. Mungu akubariki sana na ajaze maradufu ulipopunguka. Amina.";

            $sampleQrText = 'ELIVECARD-SAMPLE-' . ($event->code ?? $event->id ?? 'CARD');

            try {
                $sampleQrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(300)
                    ->margin(2)
                    ->generate($sampleQrText);
            } catch (\Throwable $e) {
                $sampleQrSvg = null;
            }
        @endphp

        <style>
            .card-designer-viewport {
                width: 100%;
                max-width: 100%;
                overflow: auto;
                padding: 12px;
                background: #f8fafc;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                text-align: center;
            }

            .card-designer-stage {
                width: {{ $designWidth }}px;
                height: {{ $designHeight }}px;
                margin: 0 auto;
                transform-origin: top center;
            }

            .upload-container.card-designer {
                position: relative;
                width: {{ $designWidth }}px;
                height: {{ $designHeight }}px;
                overflow: hidden;
                background: #ffffff;
                border: 1px dashed #cbd5e1;
                border-radius: 8px;
                line-height: normal;
            }

            .card-preview-image {
                position: absolute;
                inset: 0;
                width: {{ $designWidth }}px;
                height: {{ $designHeight }}px;
                object-fit: fill;
                z-index: 1;
                display: {{ $hasCard ? 'block' : 'none' }};
            }

            .designer-placeholder {
                position: absolute;
                z-index: 5;
                cursor: move;
                user-select: none;
                touch-action: none;
                transform: translate(-50%, -50%);
                border: 1px dashed rgba(37, 99, 235, 0.55);
                background: transparent !important;
                padding: 0;
                line-height: 1.2;
                white-space: nowrap;
                text-align: center;
            }

            .guest-name-placeholder {
                font-weight: 700;
            }

            .card-type-placeholder {
                font-weight: 700;
                border-color: rgba(22, 163, 74, 0.65);
            }

            .qr-placeholder {
                width: {{ $qrSize }}px;
                height: {{ $qrSize }}px;
                border-color: #111827;
                background: #ffffff !important;
                overflow: visible;
                --qr-main-color: {{ $qrForegroundColor }};
                --qr-bg-color: {{ $qrBackgroundColor }};
            }

            .qr-svg-box,
            .qr-svg-box svg {
                width: 100%;
                height: 100%;
                display: block;
            }

            .qr-svg-box svg {
                background: var(--qr-bg-color) !important;
            }

            .qr-svg-box svg path {
                fill: var(--qr-main-color) !important;
            }

            .qr-svg-box svg rect {
                fill: var(--qr-bg-color) !important;
            }

            .qr-placeholder-fallback {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--qr-bg-color);
                color: var(--qr-main-color);
                font-size: 12px;
                font-weight: 700;
                border: 1px solid var(--qr-main-color);
            }

            .sms-side-card .card-header {
                font-weight: 600;
                background: #ffffff;
            }
        </style>

        <form id="cardSettingsForm" action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
            @csrf

            @if ($hasCard)
                @method('PUT')
            @endif

            <input type="hidden" name="event_id" value="{{ $event->id }}">

            <div class="mb-4">
                <h6 class="fw-bold mb-3">Card Image</h6>

                <div class="card-designer-viewport" id="cardDesignerViewport">
                    <div class="card-designer-stage" id="cardDesignerStage">
                        <div class="upload-container card-designer" id="upload-container">
                            <input type="file" id="fileInput" name="image" accept="image/*" hidden>

                            <img
                                id="previewImage"
                                class="img-fluid card-preview-image"
                                alt="Card Preview"
                                @if ($hasCard)
                                    src="{{ asset('storage/' . $eventCard->card_name) }}"
                                @endif
                            >

                            <div
                                id="guestNamePlaceholder"
                                class="designer-placeholder guest-name-placeholder"
                                data-x-input="guestNameX"
                                data-y-input="guestNameY"
                                data-x-visible="guestNameXVisible"
                                data-y-visible="guestNameYVisible"
                                style="left: {{ $guestX }}px; top: {{ $guestY }}px; font-size: {{ $guestNameFontSize }}px; color: {{ $guestColor }};"
                            >
                                Mr &amp; Mrs John Doe
                            </div>

                            <div
                                id="cardTypePlaceholder"
                                class="designer-placeholder card-type-placeholder"
                                data-x-input="cardTypeX"
                                data-y-input="cardTypeY"
                                data-x-visible="cardTypeXVisible"
                                data-y-visible="cardTypeYVisible"
                                style="left: {{ $cardTypeX }}px; top: {{ $cardTypeY }}px; font-size: {{ $cardTypeFontSize }}px; color: {{ $cardTypeColor }};"
                            >
                                DOUBLE
                            </div>

                            <div
                                id="qrPlaceholder"
                                class="designer-placeholder qr-placeholder"
                                data-x-input="qrCodeX"
                                data-y-input="qrCodeY"
                                data-x-visible="qrCodeXVisible"
                                data-y-visible="qrCodeYVisible"
                                style="left: {{ $qrX }}px; top: {{ $qrY }}px; width: {{ $qrSize }}px; height: {{ $qrSize }}px;"
                            >
                                <div class="qr-svg-box">
                                    @if ($sampleQrSvg)
                                        {!! $sampleQrSvg !!}
                                    @else
                                        <div class="qr-placeholder-fallback">QR</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="changeBtn">
                        {{ $hasCard ? 'Change Card Image' : 'Choose Card Image' }}
                    </button>

                    @if ($hasCard)
                        <a
                            class="btn btn-sm btn-outline-secondary"
                            href="{{ route('samplecard.download', encrypt($event->id)) }}"
                            id="downloadSampleCardBtn"
                            data-download-url="{{ route('samplecard.download', encrypt($event->id)) }}"
                        >
                            Save & Download Sample Guest Card
                        </a>
                    @endif
                </div>

                <small class="text-muted d-block mt-2">
                    Drag Guest Name, Card Type, and QR code directly on the card. Positions are saved as exact pixels.
                </small>
            </div>

            <input type="hidden" id="guestNameX" name="guestNameX" value="{{ $guestX }}">
            <input type="hidden" id="guestNameY" name="guestNameY" value="{{ $guestY }}">
            <input type="hidden" id="cardTypeX" name="cardTypeX" value="{{ $cardTypeX }}">
            <input type="hidden" id="cardTypeY" name="cardTypeY" value="{{ $cardTypeY }}">
            <input type="hidden" id="qrCodeX" name="qrCodeX" value="{{ $qrX }}">
            <input type="hidden" id="qrCodeY" name="qrCodeY" value="{{ $qrY }}">
            <input type="hidden" id="qrCodeSize" name="qrCodeSize" value="{{ $qrSize }}">
            <input type="hidden" id="qrcodePosition" name="qrcodePosition" value="{{ $qrPosition }}">
            <input type="hidden" id="guestCardtypeBackgroundColor" name="guestCardtypeBackgroundColor" value="transparent">

            <div class="mb-4">
                <h6 class="fw-bold mb-3">Guest Name Settings</h6>

                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" id="toggleGuestName" checked>
                    <label class="form-check-label" for="toggleGuestName">Show Guest Name</label>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Position X</label>
                        <input type="text" class="form-control" id="guestNameXVisible" value="{{ $guestX }}" readonly>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Position Y</label>
                        <input type="text" class="form-control" id="guestNameYVisible" value="{{ $guestY }}" readonly>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h6 class="fw-bold mb-3">Card Type Settings</h6>

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Position X</label>
                        <input type="text" class="form-control" id="cardTypeXVisible" value="{{ $cardTypeX }}" readonly>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Position Y</label>
                        <input type="text" class="form-control" id="cardTypeYVisible" value="{{ $cardTypeY }}" readonly>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h6 class="fw-bold mb-3">QR Code Settings</h6>

                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label">QR Position</label>
                        <select class="form-control" id="qrcodePositionSelect">
                            <option value="custom" {{ $qrPosition === 'custom' ? 'selected' : '' }}>Custom / Drag</option>
                            <option value="left" {{ $qrPosition === 'left' ? 'selected' : '' }}>Left</option>
                            <option value="center" {{ $qrPosition === 'center' ? 'selected' : '' }}>Center</option>
                            <option value="right" {{ $qrPosition === 'right' ? 'selected' : '' }}>Right</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="form-label">QR Size</label>
                        <input type="number" step="1" min="30" max="250" class="form-control" id="qrCodeSizeInput" value="{{ $qrSize }}">
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="form-label">QR Center X</label>
                        <input type="text" class="form-control" id="qrCodeXVisible" value="{{ $qrX }}" readonly>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="form-label">QR Center Y</label>
                        <input type="text" class="form-control" id="qrCodeYVisible" value="{{ $qrY }}" readonly>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h6 class="fw-bold mb-1">Font Settings</h6>

                <div class="row">
                    <div class="col-md-4 mb-4">
                        <label class="form-label">Guest Name Font Size</label>
                        <input type="number" step="1" min="6" max="120" class="form-control" id="guestNameFontSize" name="guestNameFontSize" value="{{ $guestNameFontSize }}">
                    </div>

                    <div class="col-md-4 mb-4">
                        <label class="form-label">Card Type Font Size</label>
                        <input type="number" step="1" min="6" max="120" class="form-control" id="guestCardtypeFontSize" name="guestCardtypeFontSize" value="{{ $cardTypeFontSize }}">
                    </div>
                </div>
            </div>

            <div class="mb-2">
                <h6 class="fw-bold mb-3">Color Settings</h6>

                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Guest Name</label>
                        <input type="color" class="form-control form-control-color w-100" id="guestNameColor" name="guestNameColor" value="{{ $guestColor }}">
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="form-label">Card Type Text</label>
                        <input type="color" class="form-control form-control-color w-100" id="guestCardtypeColor" name="guestCardtypeColor" value="{{ $cardTypeColor }}">
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="form-label">QR Main Color</label>
                        <input type="color" class="form-control form-control-color w-100" id="qrCodeForegroundColor" name="qrCodeForegroundColor" value="{{ $qrForegroundColor }}">
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="form-label">QR Background</label>
                        <input type="color" class="form-control form-control-color w-100" id="qrCodeBackgroundColor" name="qrCodeBackgroundColor" value="{{ $qrBackgroundColor }}">
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="form-label">QR Eye Color</label>
                        <input type="color" class="form-control form-control-color w-100" id="qrCodeEyeColor" name="qrCodeEyeColor" value="{{ $qrEyeColor }}">
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4 mb-4">
                <button type="submit" class="btn btn-primary">
                    {{ $hasCard ? 'Update Card Settings' : 'Save Card Settings' }}
                </button>

                <button type="button" class="btn btn-outline-secondary" id="resetPlaceholdersBtn">
                    Reset Positions
                </button>
            </div>
        </form>
    </div>

    <div class="col-lg-5 col-md-12">
        <div class="sms-sections mt-4">
            <div class="card mb-4 shadow-sm sms-side-card">
                <div class="card-header">
                    Card Message
                </div>
                <div class="card-body">
                    <form action="{{ $smsCard ? route('smscard.update', encrypt($event->id)) : route('smscard.store') }}" method="POST">
                        @csrf
                        @if ($smsCard)
                            @method('PUT')
                        @endif

                        <input type="hidden" value="{{ encrypt($event->id) }}" name="event_id" readonly>

                        <textarea name="SMS_card" class="form-control" rows="6" placeholder="{{ $defaultCardMessage }}">{{ old('SMS_card', $smsCard->SMS_card ?? '') }}</textarea>
                        <small class="text-muted d-block mt-2">
                            The text above is only a sample placeholder. Type and save your real card message here.
                        </small>

                        <div class="mt-3">
                            <button class="btn {{ $smsCard ? 'btn-info' : 'btn-success' }} w-100">
                                {{ $smsCard ? 'Update Card Message' : 'Save Card Message' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4 shadow-sm sms-side-card">
                <div class="card-header">
                    Reminder SMS
                </div>
                <div class="card-body">
                    <form action="{{ $smsReminder ? route('smsreminder.update', encrypt($event->id)) : route('smsreminder.store') }}" method="POST">
                        @csrf
                        @if ($smsReminder)
                            @method('PUT')
                        @endif

                        <input type="hidden" value="{{ encrypt($event->id) }}" name="event_id" readonly>

                        <textarea name="SMS_reminder" class="form-control" rows="4" placeholder="{{ $defaultReminderMessage }}">{{ old('SMS_reminder', $smsReminder->SMS_reminder ?? '') }}</textarea>
                        <small class="text-muted d-block mt-2">
                            The text above is only a sample placeholder. Type and save your real reminder message here.
                        </small>

                        <div class="mt-3">
                            <button class="btn {{ $smsReminder ? 'btn-info' : 'btn-success' }} w-100">
                                {{ $smsReminder ? 'Update Reminder SMS' : 'Save Reminder SMS' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4 shadow-sm sms-side-card">
                <div class="card-header">
                    Welcome SMS
                </div>
                <div class="card-body">
                    <form action="{{ $smsWelcoming ? route('smswelcoming.update', encrypt($event->id)) : route('smswelcoming.store') }}" method="POST">
                        @csrf
                        @if ($smsWelcoming)
                            @method('PUT')
                        @endif

                        <input type="hidden" value="{{ encrypt($event->id) }}" name="event_id" readonly>

                        <textarea name="SMS_welcoming" class="form-control" rows="4" placeholder="{{ $defaultWelcomeMessage }}">{{ old('SMS_welcoming', $smsWelcoming->SMS_welcoming ?? '') }}</textarea>
                        <small class="text-muted d-block mt-2">
                            The text above is only a sample placeholder. Type and save your real welcome message here.
                        </small>

                        <div class="mt-3">
                            <button class="btn {{ $smsWelcoming ? 'btn-info' : 'btn-success' }} w-100">
                                {{ $smsWelcoming ? 'Update Welcome SMS' : 'Save Welcome SMS' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4 shadow-sm sms-side-card">
                <div class="card-header">
                    Thank You SMS
                </div>
                <div class="card-body">
                    <form action="{{ $smsThankyou ? route('smsthankyou.update', encrypt($event->id)) : route('smsthankyou.store') }}" method="POST">
                        @csrf
                        @if ($smsThankyou)
                            @method('PUT')
                        @endif

                        <input type="hidden" value="{{ encrypt($event->id) }}" name="event_id" readonly>

                        <textarea name="SMS_thankyou" class="form-control" rows="4" placeholder="{{ $defaultThankYouMessage }}">{{ old('SMS_thankyou', $smsThankyou->SMS_thankyou ?? '') }}</textarea>
                        <small class="text-muted d-block mt-2">
                            The text above is only a sample placeholder. Type and save your real thank-you message here.
                        </small>

                        <div class="mt-3">
                            <button class="btn {{ $smsThankyou ? 'btn-info' : 'btn-success' }} w-100">
                                {{ $smsThankyou ? 'Update Thank You SMS' : 'Save Thank You SMS' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const DESIGN_WIDTH = 420;
    const DESIGN_HEIGHT = 620;

    const container = document.getElementById('upload-container');
    const stage = document.getElementById('cardDesignerStage');
    const viewport = document.getElementById('cardDesignerViewport');
    const fileInput = document.getElementById('fileInput');
    const previewImage = document.getElementById('previewImage');
    const changeBtn = document.getElementById('changeBtn');
    const resetBtn = document.getElementById('resetPlaceholdersBtn');
    const cardSettingsForm = document.getElementById('cardSettingsForm');
    const downloadBtn = document.getElementById('downloadSampleCardBtn');

    const guestNamePlaceholder = document.getElementById('guestNamePlaceholder');
    const cardTypePlaceholder = document.getElementById('cardTypePlaceholder');
    const qrPlaceholder = document.getElementById('qrPlaceholder');

    const guestNameFontSize = document.getElementById('guestNameFontSize');
    const cardTypeFontSize = document.getElementById('guestCardtypeFontSize');
    const guestNameColor = document.getElementById('guestNameColor');
    const cardTypeColor = document.getElementById('guestCardtypeColor');

    const qrCodeForegroundColor = document.getElementById('qrCodeForegroundColor');
    const qrCodeBackgroundColor = document.getElementById('qrCodeBackgroundColor');
    const qrCodeSizeInput = document.getElementById('qrCodeSizeInput');
    const qrCodeSizeHidden = document.getElementById('qrCodeSize');

    const toggleGuestName = document.getElementById('toggleGuestName');
    const qrcodePositionSelect = document.getElementById('qrcodePositionSelect');
    const qrcodePosition = document.getElementById('qrcodePosition');

    if (!container) return;

    fitWholeCardInView();
    window.addEventListener('resize', fitWholeCardInView);

    if (changeBtn && fileInput) {
        changeBtn.addEventListener('click', function () {
            fileInput.click();
        });
    }

    container.addEventListener('click', function (event) {
        if (event.target.closest('.designer-placeholder')) {
            return;
        }

        if (fileInput) {
            fileInput.click();
        }
    });

    if (fileInput && previewImage) {
        fileInput.addEventListener('change', function () {
            const file = this.files[0];

            if (!file) return;

            const reader = new FileReader();

            reader.onload = function (event) {
                previewImage.src = event.target.result;
                previewImage.style.display = 'block';
                previewImage.onload = fitWholeCardInView;
            };

            reader.readAsDataURL(file);
        });
    }

    makeDraggable(guestNamePlaceholder);
    makeDraggable(cardTypePlaceholder);
    makeDraggable(qrPlaceholder, true);

    bindFontSizeInput(guestNameFontSize, guestNamePlaceholder);
    bindFontSizeInput(cardTypeFontSize, cardTypePlaceholder);
    bindStyleInput(guestNameColor, guestNamePlaceholder, 'color');
    bindStyleInput(cardTypeColor, cardTypePlaceholder, 'color');

    if (qrCodeForegroundColor && qrPlaceholder) {
        qrCodeForegroundColor.addEventListener('input', function () {
            qrPlaceholder.style.setProperty('--qr-main-color', this.value);
        });
    }

    if (qrCodeBackgroundColor && qrPlaceholder) {
        qrCodeBackgroundColor.addEventListener('input', function () {
            qrPlaceholder.style.setProperty('--qr-bg-color', this.value);
        });
    }

    if (qrCodeSizeInput && qrCodeSizeHidden && qrPlaceholder) {
        qrCodeSizeInput.addEventListener('input', function () {
            const size = Math.max(30, Math.min(250, parseInt(this.value || 72)));
            qrCodeSizeHidden.value = size;
            qrPlaceholder.style.width = size + 'px';
            qrPlaceholder.style.height = size + 'px';
            fitWholeCardInView();
        });
    }

    if (toggleGuestName && guestNamePlaceholder) {
        toggleGuestName.addEventListener('change', function () {
            guestNamePlaceholder.style.display = this.checked ? 'block' : 'none';
        });
    }

    if (qrcodePositionSelect && qrcodePosition && qrPlaceholder) {
        qrcodePositionSelect.addEventListener('change', function () {
            const value = this.value;
            qrcodePosition.value = value;

            if (value === 'left') {
                setPlaceholderPosition(qrPlaceholder, 105, 500);
            }

            if (value === 'center') {
                setPlaceholderPosition(qrPlaceholder, 210, 500);
            }

            if (value === 'right') {
                setPlaceholderPosition(qrPlaceholder, 315, 500);
            }
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            setPlaceholderPosition(guestNamePlaceholder, 210, 115);
            setPlaceholderPosition(cardTypePlaceholder, 210, 500);
            setPlaceholderPosition(qrPlaceholder, 315, 500);

            if (qrcodePosition) qrcodePosition.value = 'right';
            if (qrcodePositionSelect) qrcodePositionSelect.value = 'right';
        });
    }

    if (downloadBtn && cardSettingsForm) {
        downloadBtn.addEventListener('click', async function (event) {
            event.preventDefault();

            const downloadUrl = this.dataset.downloadUrl || this.href;
            const originalText = this.textContent;

            this.classList.add('disabled');
            this.textContent = 'Saving...';

            try {
                const formData = new FormData(cardSettingsForm);

                const response = await fetch(cardSettingsForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json,text/html,*/*',
                    },
                    credentials: 'same-origin',
                    redirect: 'follow',
                });

                if (!response.ok) {
                    throw new Error('Save failed');
                }

                window.location.href = downloadUrl;
            } catch (error) {
                alert('Please click Update Card Settings first, then download again.');
                this.classList.remove('disabled');
                this.textContent = originalText;
            }
        });
    }

    function fitWholeCardInView() {
        if (!stage || !viewport) return;

        stage.style.transform = 'scale(1)';
        stage.style.height = DESIGN_HEIGHT + 'px';

        const availableWidth = Math.max(260, viewport.clientWidth - 24);
        const availableHeight = Math.max(360, window.innerHeight - 180);

        const scaleByWidth = availableWidth / DESIGN_WIDTH;
        const scaleByHeight = availableHeight / DESIGN_HEIGHT;
        const scale = Math.min(1, scaleByWidth, scaleByHeight);

        stage.style.transform = `scale(${scale})`;
        stage.style.height = `${DESIGN_HEIGHT * scale}px`;
    }

    function bindFontSizeInput(input, element) {
        if (!input || !element) return;

        function applyFontSize() {
            element.style.fontSize = input.value + 'px';
        }

        input.addEventListener('input', applyFontSize);
        applyFontSize();
    }

    function bindStyleInput(input, element, property, suffix = '') {
        if (!input || !element) return;

        input.addEventListener('input', function () {
            element.style[property] = this.value + suffix;
        });
    }

    function makeDraggable(element, isQr = false) {
        if (!element) return;

        element.addEventListener('mousedown', startDrag);
        element.addEventListener('touchstart', startDrag, { passive: false });

        function startDrag(event) {
            event.preventDefault();
            event.stopPropagation();

            document.addEventListener('mousemove', drag);
            document.addEventListener('mouseup', stopDrag);
            document.addEventListener('touchmove', drag, { passive: false });
            document.addEventListener('touchend', stopDrag);
        }

        function drag(event) {
            event.preventDefault();

            const point = event.touches ? event.touches[0] : event;
            const rect = container.getBoundingClientRect();

            let x = ((point.clientX - rect.left) / rect.width) * DESIGN_WIDTH;
            let y = ((point.clientY - rect.top) / rect.height) * DESIGN_HEIGHT;

            x = Math.max(0, Math.min(DESIGN_WIDTH, x));
            y = Math.max(0, Math.min(DESIGN_HEIGHT, y));

            setPlaceholderPosition(element, x, y);

            if (isQr) {
                if (qrcodePosition) qrcodePosition.value = 'custom';
                if (qrcodePositionSelect) qrcodePositionSelect.value = 'custom';
            }
        }

        function stopDrag() {
            document.removeEventListener('mousemove', drag);
            document.removeEventListener('mouseup', stopDrag);
            document.removeEventListener('touchmove', drag);
            document.removeEventListener('touchend', stopDrag);
        }
    }

    function setPlaceholderPosition(element, x, y) {
        if (!element) return;

        const formattedX = Number(x).toFixed(2);
        const formattedY = Number(y).toFixed(2);

        element.style.left = formattedX + 'px';
        element.style.top = formattedY + 'px';

        const xInputId = element.dataset.xInput;
        const yInputId = element.dataset.yInput;
        const xVisibleId = element.dataset.xVisible;
        const yVisibleId = element.dataset.yVisible;

        if (xInputId && document.getElementById(xInputId)) {
            document.getElementById(xInputId).value = formattedX;
        }

        if (yInputId && document.getElementById(yInputId)) {
            document.getElementById(yInputId).value = formattedY;
        }

        if (xVisibleId && document.getElementById(xVisibleId)) {
            document.getElementById(xVisibleId).value = formattedX;
        }

        if (yVisibleId && document.getElementById(yVisibleId)) {
            document.getElementById(yVisibleId).value = formattedY;
        }
    }
});
</script>
