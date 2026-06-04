@extends('venecardDashboard.layouts.master')

@section('main-contents')
    <!-- Modal -->
    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">Send SMS Message</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <!-- Form Selection Radio Buttons -->
                    <div class="form-selection mb-4">
                        <label class="form-label fw-bold">Select Message Type:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="messageType" id="singleType" value="single"
                                checked>
                            <label class="form-check-label" for="singleType">
                                Single Message
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="messageType" id="batchType" value="batch">
                            <label class="form-check-label" for="batchType">
                                Batch Message (Excel Upload)
                            </label>
                        </div>
                    </div>

                    <!-- Single Phone Number Form -->
                    <form action="{{ route('sendmessage.sendsinglemessage') }}" method="post" enctype="multipart/form-data"
                        id="singlePhoneForm">
                        @csrf
                        <input type="hidden" name="form_type" value="single">
                        <div class="form-group mb-3">
                            <label for="modal_phone" class="form-label">Phone Number</label>
                            <input type="text" name="phone" id="modal_phone" class="form-control"
                                placeholder="Enter phone number" maxlength="10" minlength="10" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="modal_sender_id" class="form-label">Sender ID</label>
                           <select id="sender_id" name="sender_id"  class="form-control">
                                 <option selected value="elive card" selected>elive card</option>
                          </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="modal_message" class="form-label">Message</label>
                            <textarea name="message" id="modal_message" class="form-control" rows="4" placeholder="Type your message here"
                                required></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary px-4 w-100">Send Message</button>
                        </div>
                    </form>

                    <!-- Excel Upload Form -->
                    <form action="{{ route('sendmessage.sendbatchmessage') }}" method="post" enctype="multipart/form-data"
                        id="excelUploadForm" style="display: none;">
                        @csrf
                        <input type="hidden" name="form_type" value="batch">
                        <div class="form-group mb-3">
                            <label for="excel_file" class="form-label">Upload Excel File</label>
                            <input type="file" name="excel_file" id="excel_file" class="form-control"
                                accept=".xlsx, .xls" required>
                            <p><a href="{{ route('bulksms.downloadexcelsample') }}" style="font-size: 0.8rem;">Download Sample
                                    Excel File</a></p>
                        </div>
                        <div class="form-group mb-3">
                            <label for="modal_sender_id" class="form-label">Sender ID</label>
                             <select id="sender_id" name="sender_id"  class="form-control">
                                 <option selected value="elive card" selected>elive card</option>
                          </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="batch_message" class="form-label">Message</label>
                            <textarea name="message" id="batch_message" class="form-control" rows="4"
                                placeholder="Habari #B#, unakumbushwa ahadi yako ni #C#, umetoa #D# bado unadaiwa #E#" required></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-success px-4 w-100">Send Batch Messages</button>
                        </div>
                    </form>

                    <!-- JavaScript for form switching -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const singleType = document.getElementById('singleType');
                            const batchType = document.getElementById('batchType');
                            const singleForm = document.getElementById('singlePhoneForm');
                            const batchForm = document.getElementById('excelUploadForm');

                            function toggleForms() {
                                if (singleType.checked) {
                                    singleForm.style.display = 'block';
                                    batchForm.style.display = 'none';
                                } else {
                                    singleForm.style.display = 'none';
                                    batchForm.style.display = 'block';
                                }
                            }

                            singleType.addEventListener('change', toggleForms);
                            batchType.addEventListener('change', toggleForms);
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>


    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <div class="" style="float: right;">
                    <!-- Button trigger modal -->
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal"
                        data-bs-target="#staticBackdrop">
                        <i class="bi bi-person-plus-fill"></i> Send Message
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">SN</th>
                                <th scope="col">Status</th>
                                <th scope="col">Phone Number</th>
                                <th scope="col">Message</th>
                                <th scope="col">Date</th>
                                <th scope="col">Batch ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($messages->isEmpty())
                                <tr>
                                    <td colspan="6" class="text-center">No messages found</td>
                                </tr>
                            @else
                                @foreach ($messages as $message)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            @if(strtolower($message->delivery_status) == 'wait')
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                    <circle cx="12" cy="12" r="10" fill="#3498db"/>
                                                </svg>
                                            @elseif(strtolower($message->delivery_status) == 'delivered')
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                    <circle cx="12" cy="12" r="10" fill="#2ecc71"/>
                                                </svg>
                                            @elseif(strtolower($message->delivery_status) == 'pending')
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                    <circle cx="12" cy="12" r="10" fill="#f1c40f"/>
                                                </svg>
                                            @elseif(strtolower($message->delivery_status) == 'failed')
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                    <circle cx="12" cy="12" r="10" fill="#e74c3c"/>
                                                </svg>
                                            @else
                                                @if(strtolower($message->delivery_status) == 'unknown')
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                        <circle cx="12" cy="12" r="10" fill="#9b59b6"/>
                                                    </svg>
                                                @else
                                                    <span class="text-muted">{{ $message->delivery_status }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>{{ '+255' . $message->phone }}</td>
                                        <td title="{{ $message->message }}">{{ substr($message->message, 0, 10) }}...</td>
                                        <td>{{ $message->created_at->format('d-m-Y') }}</td>
                                        <td>{{ $message->batch_id }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

