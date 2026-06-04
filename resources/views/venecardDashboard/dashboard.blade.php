@extends('venecardDashboard.layouts.master')

@section('main-contents')

@php
    $formattedSmsBalance = is_numeric($message_balance)
        ? number_format((int) $message_balance) . ' SMS'
        : ucfirst($message_balance ?? 'pending');
@endphp

<section class="section dashboard">
    <div class="row">
        <!-- Stats Cards -->
        <div class="col-lg-4 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Total Events</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ number_format($stats['total_events'] ?? 0) }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Total Invitee's Cards</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ number_format($stats['total_cards'] ?? 0) }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card info-card message-card">
                <div class="card-body">
                    <h5 class="card-title">SMS Balance</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="background-color: #e0f8e9;">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $formattedSmsBalance }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Events -->
        <div class="col-12">
            <div class="card recent-events">
                <div class="card-body">
                    <h5 class="card-title">Recent Events</h5>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Event Name</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stats['recent_events'] ?? [] as $event)
                                    <tr>
                                        <td>{{ $event->name }}</td>
                                        <td>{{ $event->eventCategory->title ?? 'N/A' }}</td>
                                        <td>
                                            {{ $event->date ? $event->date->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $event->payment_status === 'paid' ? 'success' : 'secondary' }}">
                                                {{ number_format($event->eventGuests->count()) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No recent events found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

@endsection