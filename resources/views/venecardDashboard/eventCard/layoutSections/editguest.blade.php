@extends('venecardDashboard.layouts.master')

@section('main-contents')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Please fix the following error:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Edit Guest Information</h4>

                    <a href="{{ url()->previous() }}" class="btn btn-sm btn-secondary">
                        Back
                    </a>
                </div>

                <div class="card-body">
                    <form action="{{ route('guests.update', encrypt($guest->id)) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Guest Name</label>
                            <input
                                type="text"
                                name="guest_name"
                                class="form-control @error('guest_name') is-invalid @enderror"
                                value="{{ old('guest_name', $guest->guest_name) }}"
                                placeholder="Enter guest name"
                            >

                            @error('guest_name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input
                                type="text"
                                name="guest_phone"
                                class="form-control @error('guest_phone') is-invalid @enderror"
                                value="{{ old('guest_phone', strlen($guest->guest_phone) == 9 ? '0' . $guest->guest_phone : $guest->guest_phone) }}"
                                placeholder="Example: 0768461644"
                            >

                            @error('guest_phone')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <small class="text-muted">
                                Enter 10 digits, example: 0768461644
                            </small>
                        </div>

                        @php
                            $currentCardType = old('card_type', str_starts_with($guest->card_type, 'WATU ') ? 'GROUP' : $guest->card_type);
                            $currentGroupSize = old('group_size', str_starts_with($guest->card_type, 'WATU ') ? trim(str_replace('WATU ', '', $guest->card_type)) : '');
                        @endphp

                        <div class="mb-3">
                            <label class="form-label">Card Type</label>

                            <select
                                name="card_type"
                                id="card_type"
                                class="form-select @error('card_type') is-invalid @enderror"
                                onchange="toggleGroupSize()"
                            >
                                <option value="">-- Select Card Type --</option>

                                <option value="SINGLE" {{ $currentCardType == 'SINGLE' ? 'selected' : '' }}>
                                    SINGLE
                                </option>

                                <option value="DOUBLE" {{ $currentCardType == 'DOUBLE' ? 'selected' : '' }}>
                                    DOUBLE
                                </option>

                                <option value="VIP" {{ $currentCardType == 'VIP' ? 'selected' : '' }}>
                                    VIP
                                </option>

                                <option value="VVIP" {{ $currentCardType == 'VVIP' ? 'selected' : '' }}>
                                    VVIP
                                </option>

                                <option value="GROUP" {{ $currentCardType == 'GROUP' ? 'selected' : '' }}>
                                    GROUP
                                </option>
                            </select>

                            @error('card_type')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3" id="groupSizeWrapper" style="display: none;">
                            <label class="form-label">Group Size</label>
                            <input
                                type="number"
                                name="group_size"
                                id="group_size"
                                class="form-control @error('group_size') is-invalid @enderror"
                                value="{{ $currentGroupSize }}"
                                min="1"
                                max="100"
                                placeholder="Example: 5"
                            >

                            @error('group_size')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <small class="text-muted">
                                This field is required only when card type is GROUP.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Note</label>
                            <textarea
                                name="note"
                                class="form-control @error('note') is-invalid @enderror"
                                rows="3"
                                placeholder="Optional note"
                            >{{ old('note', $guest->note ?? '') }}</textarea>

                            @error('note')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                Update Guest
                            </button>

                            <a href="{{ url()->previous() }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function toggleGroupSize() {
        const cardType = document.getElementById('card_type').value;
        const groupSizeWrapper = document.getElementById('groupSizeWrapper');

        if (cardType === 'GROUP') {
            groupSizeWrapper.style.display = 'block';
        } else {
            groupSizeWrapper.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        toggleGroupSize();
    });
</script>
@endsection
