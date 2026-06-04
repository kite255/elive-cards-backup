@extends('venecardDashboard.layouts.master')


<!-- Add jQuery UI CSS -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<!-- Bootstrap Datepicker CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" rel="stylesheet">



<!-- Add Clipboard.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.11/clipboard.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- jQuery UI -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<!-- Bootstrap Datepicker JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

<script src="{{ asset('assets/js/event.js') }}"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const eventTypeSelect = document.getElementById('event_type');
        const eventPackageDiv = document.getElementById('event_package_div');

        // Hide event package by default
        eventPackageDiv.style.display = 'none';

        // Add change event listener to event type select
        eventTypeSelect.addEventListener('change', function() {
            if (this.value === 'invitation') {
                eventPackageDiv.style.display = 'block';
            } else {
                eventPackageDiv.style.display = 'none';
                // Reset event package selection
                document.getElementById('event_package').value = '';
            }
        });
    });
</script>

@section('main-contents')
<div class="pagetitle">
    <h1>Event</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">event</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<!-- Modal for adding event -->
<div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false"
    tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h2 class="modal-title fs-5 fw-bold" id="staticBackdropLabel">Add Event</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="container-fluid px-0">
                    <form action="{{ route('events.store') }}" method="POST" autocomplete="off" class="event-registration-form">

                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3 text-primary">Event Details</h6>
                                        <div class="mb-3">
                                            <label for="event_name" class="form-label">Event Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="event_name" name="name"
                                                placeholder="Harusi ya John & Anna" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="event_category" class="form-label">Event Category <span class="text-danger">*</span></label>
                                            <select name="category" class="form-select" id="event_category" required>
                                                <option value="" selected disabled>-- Select Category --</option>
                                                @foreach($eventCategories as $category)
                                                <option value="{{ $category->id }}" {{ old('event_category_id') == $category->id ? 'selected' : '' }}>
                                                    {{ $category->title}}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="event_place" class="form-label">Event Venue <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="event_place" name="place"
                                                placeholder="ABCD Hotel" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="event_date1" class="form-label">Event Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control datepicker1" id="event_date1" name="date"
                                                placeholder="Select event date" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="venue_map" class="form-label">Venue Map Location Link <span class="text-muted">(optional)</span></label>
                                            <input type="text" class="form-control" id="venue_map" name="venue_map_location_link"
                                                placeholder="https://maps.app.goo.gl/1234567890">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3 text-primary">Event Type</h6>
                                        <div class="mb-3">
                                            {{-- <label for="contact_name" class="form-label">Event Type <span class="text-muted"></span></label> --}}
                                           <select name="event_type" class="form-select" id="event_type" required>
                                            <option value="" selected disabled>-- Select Event Type --</option>
                                            <option value="invitation">invitation event</option>
                                            <option value="contribution">contribution event</option>
                                           </select>
                                        </div>
                                        {{-- <div class="mb-3" id="event_package_div">
                                        <label for="contact_phone" class="form-label">Event Package</label>
                                            <select name="event_package" class="form-select" id="event_package">
                                                <option value="" selected disabled>-- Select Event Package --</option>
                                                <option value="bronze">Bronze (700 Tshs)</option>
                                                <option value="silver">Silver (900 Tshs)</option>
                                                <option value="gold" disabled>Gold (XXX Tshs)</option>
                                            </select>
                                        </div> --}}
                                        <h6 class="card-title mb-3 text-primary">Contact Details <span class="text-muted">(optional)</span></h6>
                                        <div class="mb-3">
                                            <label for="contact_name" class="form-label">Contact Name <span class="text-muted"></label>
                                            <input type="text" class="form-control" id="contact_name" name="contactName"
                                                placeholder="Contact name">
                                        </div>
                                        <div class="mb-3">
                                            <label for="contact_phone" class="form-label">Contact Phone <span class="text-muted"></label>
                                            <input type="tel" class="form-control" id="contact_phone" name="contactPhone"
                                                placeholder="Contact phone">
                                        </div>
                                        <div class="mb-3">
                                            <label for="contact_email" class="form-label">Email Address <span class="text-muted"></label>
                                            <input type="email" class="form-control" id="contact_email" name="email"
                                                placeholder="Email address">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0 px-0 mt-4">
                            <button type="button" class="btn btn-light btn-lg" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save me-2"></i>Save Event
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- filter event form -->
<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Filter Events</h5>
        </div>
        <div class="card-body mt-2">
            <form action="{{ route('events.index') }}" method="GET" class="filter-form">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="category" class="form-label">Event Category</label>
                            <select name="category" class="form-control" id="category">
                                <option value="">All Categories</option>
                                @foreach($eventCategories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->title }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="text" class="form-control datepicker" id="start_date" name="start_date" value="{{ request('start_date') }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="text" class="form-control datepicker" id="end_date" name="end_date" value="{{ request('end_date') }}" readonly>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('events.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i> reset
                    </a>
                </div>
            </form>
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
                    <i class="bi bi-calendar-plus-fill"></i> new event
                </button>


            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Card</th>
                            <th scope="col">Name</th>
                            <th scope="col">Contact</th>
                            <th scope="col">Code</th>
                            <th scope="col">Approver</th>
                            <th scope="col">Qty</th>
                            <th scope="col">Event date</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $index => $event)
                        <tr>
                            @php
                            $index =
                            $events->currentPage() * $events->perPage() -
                            $events->perPage() +
                            $index +
                            1;
                            @endphp
                            <td>{{ $index }}</td>
                            <td>
                                @if ($event->card_name == 'sample card')
                                <img src="{{ asset('storage/eventSampleCards/sample.jpg') }}" alt=""
                                    style="width: 60px;">
                                @else
                                <img @if(optional($event->eventcard)->card_name) src="{{ asset('storage/'.$event->eventcard->card_name) }}" @else src="{{ asset('storage/eventsCardSamples/sample card.jpg') }}" @endif alt=""
                                style="width: 60px;">
                                @endif
                            </td>
                            <td>
                                <p>{{ $event->name }}</p>
                                <p><span class="badge text-bg-secondary">{{ optional($event->eventCategory)->title }}</span></p>
                            </td>
                            <td>
                                <p class="text-muted">{{ $event->contact_name }}</p>
                                <p><span class="badge text-bg-secondary">{{ $event->contact_phone }}</span></p>
                            </td>

                            <td>
                                {{ $event->code }} <br>
                                <div class="d-flex gap-2">
                                    @if($event->venue_map_location_link)
                                    <a href="{{ $event->venue_map_location_link }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="{{ $event->place }} map">
                                        <i class="bi bi-map"></i>
                                    </a>
                                    @endif
                                    <button class="btn btn-sm btn-outline-secondary copy-btn" data-clipboard-text="https://staff.elivecard.site/scan-cards/{{ $event->code }}" title="Copy link to scan {{ $event->name }}">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary copy-btn" data-clipboard-text="https://staff.elivecard.site/invitation-updates/{{ $event->code }}" title="Trace Invitation updates to {{ $event->name }}">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </td>
                            <td>
                                @php
                                $nameParts = explode(' ', $event->user->name);
                                @endphp
                                {{ $nameParts[0] }}<br>
                                @if(isset($nameParts[1]))
                                {{ $nameParts[1] }}<br>
                                @endif
                                @if(isset($nameParts[2]))
                                {{ $nameParts[2] }}
                                @endif
                            </td>
                            <td><span class="badge text-bg-primary">{{ $event->eventGuests->count() }}</span></td>
                            <td>{{ date('d-m-Y', strtotime($event->date)) }}</td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('events.show', encrypt($event->id)) }}">Show</a></li>
                                        <li><a class="dropdown-item" href="{{ route('events.edit', encrypt($event->id)) }}">Edit Info</a></li>
                                        <li>
                                            <form action="{{ route('events.destroy', encrypt($event->id)) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this event?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-1"></i>Delete</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $events->links() }}
        </div>
    </div>
</div>
@endsection