@extends('venecardDashboard.layouts.master')

@section('main-contents')
    <div class="pagetitle">
        <h1>Event categories</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('eventcategories.index') }}">Event categories</a></li>
                <li class="breadcrumb-item active">trashed</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <div class="container mt-4">
        <h3>trashed categories</h3>
        <div class="card">
           
            <div class="card-body mt-3">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Title</th>
                                <th scope="col">Published by</th>
                                <th scope="col">Date</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($eventCategories as $index => $eventCategory)
                                @php
                                    $index =
                                        $eventCategories->currentPage() * $eventCategories->perPage() -
                                        $eventCategories->perPage() +
                                        $index +
                                        1;
                                @endphp
                                <tr>
                                    <td>{{ $index }}</td>
                                    <td>{{ $eventCategory->title }}</td>
                                    <td>{{ $eventCategory->publisher_id }}</td>
                                    <td>{{ date('d-m-Y', strtotime($eventCategory->created_at)) }}</td>
                                    <td>
                                        <div class="d-flex justify-content-evenly">
                                            <a class="btn btn-sm btn-success"
                                                href="{{ route('eventcategories.restore', encrypt($eventCategory->id)) }}">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </a>
                                            <form id="delete-form-{{ $eventCategory->id }}"
                                                action="{{ route('eventcategories.force-delete', $eventCategory->id) }}"
                                                method="POST" style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                            <button type="button" class="btn btn-sm btn-danger delete-category"
                                                data-id="{{ $eventCategory->id }}">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $eventCategories->links() }}
                </div>


            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelectorAll('.delete-category').forEach(button => {
            button.addEventListener('click', function() {
                const categoryId = this.getAttribute('data-id');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('delete-form-' + categoryId).submit();
                    }
                });
            });
        });
    </script>
@endsection
