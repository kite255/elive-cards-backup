@extends('venecardDashboard.layouts.master')

@section('main-contents')
    <div class="pagetitle">
        <h1>staff</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">staff</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <div class="container mt-4">
        <h3>Venecard staff members</h3>
        <div class="card">
            <div class="card-header">
                <div class="" style="float: right;">
                    <a href="{{ route('venecardstaff.create') }}" class="btn btn-sm btn-success"><i class="bi bi-person-plus-fill"></i> Add</a>
                    {{-- <a href="" class="btn btn-sm btn-warning">trashed</a> --}}
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Name</th>
                                <th scope="col">Phone</th>
                                <th scope="col">Email</th>
                                <th scope="col">Region</th>
                                <th scope="col">Rule</th>
                                <th scope="col">Joined date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($venecardStaffs as $index => $venecardStaff)
                            <tr>
                                @php
                                    $index =
                                        $venecardStaffs->currentPage() * $venecardStaffs->perPage() -
                                        $venecardStaffs->perPage() +
                                        $index +
                                        1;
                                @endphp
                                <td>{{ $index}}</td>
                                <td>{{ $venecardStaff->first_name.' '.$venecardStaff->last_name}}</td>
                                <td>{{ $venecardStaff->phone }}</td>
                                <td>{{ $venecardStaff->email }}</td>
                                <td>{{ $venecardStaff->region }}</td>
                                <td>{{ $venecardStaff->role }}</td>
                                <td>{{ date('d-m-Y', strtotime($venecardStaff->created_at)) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $venecardStaffs->links() }}
            </div>
        </div>
    </div>
@endsection
