@extends('venecardDashboard.layouts.master')
@section('main-contents')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Edit Guest Information</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('guests.update', $guest->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label class="form-label">Guest Name</label>
                            <input type="text" class="form-control" value="{{ $guest->guest_name }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="guest_phone" class="form-control" value="{{ $guest->guest_phone }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Card Type</label>
                            <input type="text" class="form-control" value="{{ $guest->card_type }}" readonly>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Phone Number</button>
                        <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

