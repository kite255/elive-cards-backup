@extends('venecardDashboard.layouts.master')

@section('main-contents')
    <div class="container mt-4">
        <div class="pagetitle">
            <h1>Event categories</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('eventcategories.index') }}">Event categories</a></li>
                    <li class="breadcrumb-item active">add category</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <div class="container mt-4">
            <h4>add category</h4>
            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    <div class="alert alert-warning">{{ $error }}</div>
                @endforeach
            @endif
            <div class="card">
                {{-- <div class="card-header">
                  
                </div> --}}
                <div class="card-body mt-2">
                    <form action="{{ route('eventcategories.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="" class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="form-group mt-4">
                            <button class="btn btn-primary" type="submit">submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
