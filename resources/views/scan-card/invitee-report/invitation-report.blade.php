<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Invitation Report</title>
    <!-- Bootstrap CSS CDN (use your preferred version) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 20mm 15mm 20mm 15mm;
        }

        html,
        body {
            width: 210mm;
            min-height: 297mm;
            background: #f3fbfe;
            font-size: 12pt;
        }

        body {
            margin: 0;
            padding: 0;
        }

        .a4-container {
            width: 180mm;
            margin: 0 auto;
            background: #f3fbfe;
            padding: 0;
        }

        .brand-title {
            color: #2dc6f6;
            font-weight: bold;
            font-size: 2rem;
            letter-spacing: 2px;
        }

        /* .summary-box {
            background: #f3fbfe;
            border-radius: 8px;
            padding: 1.5rem 0.5rem;
            text-align: center;
            margin-bottom: 1rem;
        } */
        .summary-box .summary-value {
            color: #2dc6f6;
            font-size: 2.5rem;
            font-weight: bold;
        }

        .table-container {
            display: block;
            width: 100%;
            margin: 0;
        }

        .custom-table {
            margin: 0;
            border-collapse: collapse;
            min-width: 95%;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        }

        .custom-table th,
        .custom-table td {
            border: 1px solid #6bbbc7;
            text-align: center;
            vertical-align: middle;
            padding: 8px 4px;
        }

        .custom-table thead th {
            background: #6bbbc7;
            color: #fff;
            font-weight: bold;
            font-size: 1.05em;
        }

        .custom-table tbody tr {
            background-color: #f9fdfc;
        }

        /* Remove border-spacing and margin between rows */
    </style>
</head>

<body>
    <div class="a4-container p-4">
        <div class="row mb-2">
            <div class="col-md-6">
                <div class="brand-title mb-0">VENECARD TECHNOLOGIES</div>
                <div class="mt-2">
                    Tabata Kinyerezi, Dar es salaam<br>
                    Near Mahakama ya wilaya ya Ilala<br>
                    +255 767 71 80 26
                </div>
            </div>
            <div class="col-md-6 text-end mb-2">
                <h2 class="fw-bold mt-3">Event Number: <span style="color: #2dc6f6">{{$event->code}}</span></h2>
                <div class="fs-5">Date Issues: {{$event->created_at->format('Y-m-d')}}</div>
                <div class="fs-5">Event Date: {{date('Y-m-d', strtotime($event->date))}}</div>
            </div>
        </div>

        <div class="d-flex flex-nowrap overflow-auto mt-4 gap-2">
            <div class="summary-box p-3 text-center">
                <div>Total Sent cards : <span style="color: #2dc6f6">{{$statistics['totalInvitees']}}</span> | Total Invited Guests : <span style="color: #2dc6f6">{{$statistics['totalInvitees']}}</span> | Single Cards : <span style="color: #2dc6f6">{{$statistics['totalSingleInvitees']}}</span> Double Cards : <span style="color: #2dc6f6">{{$statistics['totalDoubleInvitees']}}</span></div>
            </div>
            <div class="summary-box p-3 text-center">
                <div>Will Attend Replies : <span style="color: #2dc6f6">{{$statistics['willAttend']}}</span> | Will not Attend Replies : <span style="color: #2dc6f6">{{$statistics['willNotAttend']}}</span> | Other Replies : <span style="color: #2dc6f6">{{$statistics['otherReplies']}}</span></div>
            </div>
        </div>

        <hr>
        <div class="text-center fw-bold fs-4 mb-3">SEND CARD REPORT</div>
        <div class="table-container">
            <table class="table custom-table table-bordered table-striped align-left" style="margin:0;">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Type</th>
                        <th>Channel</th>
                        <th>Status</th>
                        <th>Replies</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invitees as $invitee)
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <td>{{$invitee->guest_name}}</td>
                        <td>{{$invitee->guest_phone}}</td>
                        <td>{{$invitee->card_type}}</td>
                        @if($invitee->sendwhatsappcard->delivery_status == 'failed' || $invitee->sendwhatsappcard->delivery_status == 'sent')
                        <td>SMS</td>
                        <td>{{strtolower($invitee->sendmessagecard->delivery_status ?? '')}}</td>
                        <td>{{''}}</td>
                        @else
                        <td>WHATSAPP</td>
                        <td>{{$invitee->sendwhatsappcard->delivery_status ?? ''}}</td>
                        <td>{{$invitee->sendwhatsappcard->reply_message ?? ''}}</td>
                        @endif
                    </tr>
                    @endforeach
                    <!-- Add more rows as needed -->
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>