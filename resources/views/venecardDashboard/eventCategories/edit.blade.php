@extends('venecardDashboard.layouts.master')

@section('main-contents')
    <div class="container mt-4">
        <div class="pagetitle">
            <h1>Event categories</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('eventcategories.index') }}">Event categories</a></li>
                    <li class="breadcrumb-item active">edit</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <div class="container mt-4">
            <div class="card">
                <div class="card-header">
                   <h5 class="card-title fw-bold text-primary mb-0">Edit event category</h5>
                </div>
                <div class="card-body">
                    <form action="{{route('eventcategories.update',encrypt($eventCategories->id))}}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="" class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" value="{{$eventCategories->title}}">
                        </div>
                        <div class="form-group mt-4">
                            <button class="btn btn-primary" type="submit">update category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
