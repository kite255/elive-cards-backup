@extends('scan-card.layouts.master')

@section('content')
<div class="container py-5 mb-5">
    <!-- Header Section -->
    <div class="header text-center mb-5">
        <div class="company-logo mb-3">
           <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" class="img-fluid" style="max-width: 120px;">
        </div>
        <p class="text-muted mb-2">Event Statistics</p>
        <h2 class="h4 event-title">{{ $event->name }}</h2>
    </div>

    <!-- First Row Statistics -->
    <div class="row g-3">
        <div class="col-6">
            <div class="stat-card h-100">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center justify-content-center text-center">

                        <div class="ms-3">
                            <h6 class="stat-label mb-1">Total Cards</h6>
                            <div class="stat-value display-6">{{ $statistics['totalCards'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="stat-card h-100">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center justify-content-center text-center">

                        <div class="ms-3">
                            <h6 class="stat-label mb-1">Single | Double | Group</h6>
                            <div class="stat-value display-6">
                                {{ $statistics['totalSingleCards'] }} | {{ $statistics['totalDoubleCards'] }} | {{ $statistics['totalFamilyCards'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row Statistics -->
    <div class="row g-3 mt-3">
        <div class="col-6">
            <div class="stat-card h-100">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center justify-content-center text-center">
                        <div class="ms-3">
                            <h6 class="stat-label mb-1">scanned cards</h6>
                            <div class="stat-value display-6">{{ $statistics['totalScannedCards'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="stat-card h-100">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center justify-content-center text-center">
                        <div class="ms-3">
                            <h6 class="stat-label mb-1">Not Scanned Cards</h6>
                            <div class="stat-value display-6">{{ $statistics['totalNotScannedCards'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons mt-5">
        <div class="row g-3 mt-3">
            <div class="col-12">
                <a href="https://staff.kcpcards.co.tz/download-invitees-report/{{$event->code}}" class="btn btn-primary w-100 btn-md py-3">
                    <svg class="me-2" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span class="button-text">Pakua ripoti ya waalikwa</span>
                </a>
            </div>
        </div>
        <!--<div class="row g-3 mt-3">-->
        <!--    <div class="col-12">-->
        <!--        <a href="#" class="btn btn-primary w-100 btn-md py-3">-->
        <!--            <svg class="me-2" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">-->
        <!--                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>-->
        <!--                <polyline points="14 2 14 8 20 8"></polyline>-->
        <!--                <line x1="16" y1="13" x2="8" y2="13"></line>-->
        <!--                <line x1="16" y1="17" x2="8" y2="17"></line>-->
        <!--                <polyline points="10 9 9 9 8 9"></polyline>-->
        <!--            </svg>-->
        <!--            <span class="button-text">Pakua ripoti ya wahuduriaji</span>-->
        <!--        </a>-->
        <!--    </div>-->
        <!--</div>-->
    </div>
    <!-- need help button -->
    <!-- Help Buttons -->
    <div class="row g-3 mt-3">
        <h5 class="text-center">Need help?</h5>
        <div class="d-flex justify-content-center gap-4">
            <a href="https://wa.me/+255762541514" class="contact-icon whatsapp-bg">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                </svg>
            </a>
            <a href="tel:+255762541514" class="contact-icon phone-bg">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
            </a>
        </div>
    </div>

</div>


<style>
    .header {
        position: relative;
    }

    .company-logo {
        position: relative;
    }

    .company-logo h1 {
        background: linear-gradient(45deg, #2196F3, #00BCD4);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: inline-block;
    }

    .event-title {
        color: #333;
        font-weight: 600;
    }

    .stat-card {
        height: 100%;
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .stat-icon svg {
        width: 24px;
        height: 24px;
        color: #333;
        stroke-width: 1.5;
    }

    .bg-primary-subtle svg {
        color: #2196F3;
    }

    .bg-success-subtle svg {
        color: #4CAF50;
    }

    .bg-info-subtle svg {
        color: #00BCD4;
    }

    .bg-warning-subtle svg {
        color: #FFC107;
    }

    .stat-label {
        color: #666;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-value {
        color: #333;
        font-weight: 600;
        line-height: 1.2;
        font-size: 1.5rem;
    }

    .action-buttons .btn {
        padding: 12px;
        border-radius: 12px;
        font-weight: 500;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .action-buttons .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .action-buttons .btn-primary {
        background: linear-gradient(45deg, #2196F3, #00BCD4);
        border: none;
    }

    .action-buttons .btn-outline-primary {
        border: 2px solid #2196F3;
        color: #2196F3;
    }

    .action-buttons .btn-outline-primary:hover {
        background: linear-gradient(45deg, #2196F3, #00BCD4);
        border-color: transparent;
        color: white;
    }

    .action-buttons svg {
        transition: transform 0.3s ease;
        flex-shrink: 0;
    }

    .action-buttons .btn:hover svg {
        transform: translateY(-2px);
    }

    /* Contact Icons Styling */
    .contact-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        color: white;
    }

    .contact-icon:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .whatsapp-bg {
        background-color: #25D366;
    }

    .phone-bg {
        background-color: #007bff;
    }

    @media (max-width: 576px) {
        .stat-icon {
            width: 40px;
            height: 40px;
        }

        .stat-icon svg {
            width: 20px;
            height: 20px;
        }

        .stat-value {
            font-size: 1.2rem;
        }

        .stat-label {
            font-size: 0.75rem;
        }

        .button-text {
            font-size: 0.85rem;
        }

        .action-buttons .btn {
            padding: 8px;
        }

        .action-buttons svg {
            width: 16px;
            height: 16px;
        }
    }
</style>
@endsection