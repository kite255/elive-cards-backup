@extends('venecardDashboard.layouts.master')


@section('main-contents')
    <div class="pagetitle">
        <h1>Event categories</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Event categories</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <div class="" style="float: right;">
                    <a href="{{ route('eventcategories.create') }}" class="btn btn-sm btn-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                        </svg>
                        Create
                    </a>
                    {{-- <a href="{{ route('eventcategories.trashed') }}" class="btn btn-sm btn-warning" >trashed</a> --}}

                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">SN</th>
                                <th scope="col">Title</th>
                                <th scope="col">Published by</th>
                                <th scope="col">Date</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($eventCategories as $index => $eventCategory)
                            <tr>
                                @php
                                    $index =
                                        $eventCategories->currentPage() * $eventCategories->perPage() -
                                        $eventCategories->perPage() +
                                        $index +
                                        1;
                                @endphp
                                <td>{{ $index }}</td>
                                <td>{{ $eventCategory->title }}</td>
                                <td>{{ $eventCategory->user->name }}</td>
                                <td>{{ date('d-m-Y', strtotime($eventCategory->created_at)) }}</td>
                                <td>
                                    <div class="d-flex justify-content-evenly">
                                        <a href="{{ route('eventcategories.edit', encrypt($eventCategory->id)) }}"
                                            class="btn btn-sm btn-info rounded-circle">
                                            <i class="bi bi-pencil-fill text-white"></i>
                                        </a>

                                        {{-- <button type="button"
                                            class="btn btn-sm btn-danger rounded-circle delete-category"
                                            data-id="{{ $eventCategory->id }}">
                                            <i class="bi bi-trash3-fill text-white"></i>
                                        </button> --}}

                                        {{-- <form id="delete-form-{{ $eventCategory->id }}"
                                            action="{{ route('eventcategories.destroy', $eventCategory->id) }}"
                                            method="POST" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form> --}}
                                    </div>
                                </td>

                            </tr>
                        @endforeach

                        </tbody>
                    </table>
                </div>
                {{ $eventCategories->links() }}
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('assets/js/eventcategories.js') }}"></script>

@endsection

