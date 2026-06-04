@extends('venecardDashboard.layouts.master')

@section('main-contents')
<!-- Add CSS for Bootstrap Datepicker -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

<!-- Ensure jQuery UI CSS is loaded -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

<div class="container">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Edit Event Details</h5>

            <form id="editEventForm" action="{{ route('events.update', encrypt($event->id)) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Event Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ $event->name }}" required>
                        <div class="invalid-feedback">
                            Please enter event name
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="category" class="form-label">Event Category</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="">Select category</option>
                            @foreach(\App\Models\EventCategory::all() as $category)
                                <option value="{{ $category->id }}" {{ $event->event_categories_id == $category->id ? 'selected' : '' }}>
                                    {{ $category->title }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">
                            Please select a category
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="place" class="form-label">Event Place</label>
                        <input type="text" class="form-control" id="place" name="place" value="{{ $event->place }}" required>
                        <div class="invalid-feedback">
                            Please enter event place
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="date" class="form-label">Event Date</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="date" name="date" value="{{ date('Y-m-d', strtotime($event->date)) }}" required readonly>
                            <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                        </div>
                        <div class="invalid-feedback">
                            Please select event date
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="contactName" class="form-label">Contact Name</label>
                        <input type="text" class="form-control" id="contactName" name="contactName" value="{{ $event->contact_name }}">
                    </div>

                    <div class="col-md-6">
                        <label for="contactPhone" class="form-label">Contact Phone</label>
                        <input type="text" class="form-control" id="contactPhone" name="contactPhone" value="{{ $event->contact_phone }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ $event->email }}">
                        <div class="invalid-feedback">
                            Please enter a valid email address
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="venue_map_location_link" class="form-label">Venue Map Location Link</label>
                        <input type="url" class="form-control" id="venue_map_location_link" name="venue_map_location_link" value="{{ $event->venue_map_location_link }}">
                        <div class="invalid-feedback">
                            Please enter a valid URL
                        </div>
                    </div>
                </div>


                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Update Event</button>
                        <a href="{{ route('events.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script>
    // Wait for both jQuery and jQuery UI to be loaded
    window.addEventListener('load', function() {
        console.log('Window loaded');
        if (typeof jQuery != 'undefined') {
            console.log('jQuery is loaded');
            if (typeof jQuery.ui != 'undefined') {
                console.log('jQuery UI is loaded');
                try {
                    // Initialize jQuery UI datepicker
                    $("#date").datepicker({
                        dateFormat: 'yy-mm-dd',
                        changeMonth: true,
                        changeYear: true,
                        minDate: 0,
                        showAnim: 'fadeIn'
                    });
                    console.log('Datepicker initialized');

                    // Add click handler for calendar icon
                    $('.input-group-text').on('click', function() {
                        $("#date").datepicker('show');
                    });
                } catch(e) {
                    console.error('Error initializing datepicker:', e);
                }
            } else {
                console.error('jQuery UI is not loaded');
            }
        } else {
            console.error('jQuery is not loaded');
        }
    });

    // Form validation
    (function () {
        'use strict'

        // Fetch all forms we want to apply custom validation styles to
        var forms = document.querySelectorAll('.needs-validation')

        // Loop over them and prevent submission
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }

                    form.classList.add('was-validated')
                }, false)
            })
    })()

    // Phone number validation
    document.getElementById('contactPhone').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if(value.length > 12) {
            value = value.slice(0, 12);
        }
        e.target.value = value;
    });

    // Email validation
    document.getElementById('email').addEventListener('input', function(e) {
        const email = e.target.value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if(email && !emailRegex.test(email)) {
            e.target.setCustomValidity('Please enter a valid email address');
        } else {
            e.target.setCustomValidity('');
        }
    });

    // URL validation
    document.getElementById('venue_map_location_link').addEventListener('input', function(e) {
        const url = e.target.value;
        try {
            new URL(url);
            e.target.setCustomValidity('');
        } catch (_) {
            if(url) {
                e.target.setCustomValidity('Please enter a valid URL');
            } else {
                e.target.setCustomValidity('');
            }
        }
    });
</script>

@endsection

