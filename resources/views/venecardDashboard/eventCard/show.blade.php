@extends('venecardDashboard.layouts.master')

<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

@section('main-contents')
    <div class="pagetitle">
        <h1>Events</h1>

        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>

                <li class="breadcrumb-item active">
                    <a href="{{ route('events.index') }}">Event</a>
                </li>
            </ol>
        </nav>
    </div>

    <div class="container-fluid mt-2">
        <ul class="nav nav-pills" id="tab-navigation" role="tablist">
            <li class="nav-item">
                <a class="nav-link btn-sm active" data-toggle="pill" href="#card" role="tab">
                    Card
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link btn-sm" data-toggle="pill" href="#contribution" role="tab">
                    Contribution card caption
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link btn-sm" data-toggle="pill" href="#guests" role="tab">
                    Guests
                </a>
            </li>
        </ul>

        <div class="tab-content mt-3">
            <div id="card" class="tab-pane fade show active" role="tabpanel">
                @include('venecardDashboard.eventCard.layoutSections.card')
            </div>

            <div id="contribution" class="tab-pane fade" role="tabpanel">
                @include('venecardDashboard.eventCard.layoutSections.contribution-card-caption')
            </div>

            <div id="guests" class="tab-pane fade" role="tabpanel">
                @include('venecardDashboard.eventCard.layoutSections.guest')
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const tabStorageKey = "activeEventTab";
            const savedTab = localStorage.getItem(tabStorageKey);

            function activateTab(tabHref) {
                const tabLink = document.querySelector(`#tab-navigation a[href="${tabHref}"]`);
                const tabPane = document.querySelector(tabHref);

                if (!tabLink || !tabPane) {
                    return false;
                }

                document.querySelectorAll('#tab-navigation .nav-link').forEach(function (link) {
                    link.classList.remove('active');
                });

                document.querySelectorAll('.tab-pane').forEach(function (pane) {
                    pane.classList.remove('show', 'active');
                });

                tabLink.classList.add('active');
                tabPane.classList.add('show', 'active');

                return true;
            }

            if (savedTab) {
                const activated = activateTab(savedTab);

                if (!activated) {
                    localStorage.removeItem(tabStorageKey);
                    activateTab('#card');
                }
            } else {
                activateTab('#card');
            }

            document.querySelectorAll('#tab-navigation a[data-toggle="pill"]').forEach(function (tab) {
                tab.addEventListener("click", function () {
                    const tabHref = this.getAttribute("href");

                    localStorage.setItem(tabStorageKey, tabHref);
                    activateTab(tabHref);
                });
            });
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    @include('sweetalert::alert')
@endsection