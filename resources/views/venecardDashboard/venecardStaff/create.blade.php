@extends('venecardDashboard.layouts.master')

@section('main-contents')
    <div class="container mt-4">
        <div class="pagetitle">
            <h1>Staff</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active"><a href="{{ route('venecardstaff.index') }}">venecard staff</a></li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <div class="container mt-4">
            <h4>Add staff</h4>
            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    <div class="alert alert-warning">{{ $error }}</div>
                @endforeach
            @endif
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('venecardstaff.store') }}" method="POST" autocomplete="off">
                        @csrf
                        <div class="row mt-5">
                            <div class="form-group col-md-6">
                                <label for="" class="form-label">First name</label>
                                <input type="text" class="form-control" name="firstName">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="" class="form-label">Last name</label>
                                <input type="text" class="form-control" name="lastName">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="" class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" placeholder="07/06...">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="" class="form-label">Email</label>
                                <input type="text" class="form-control" name="email">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="" class="form-label">Region</label>
                                <select name="region" class="form-control">
                                    <option value="Arusha">Arusha</option>
                                    <option value="Dodoma">Dodoma</option>
                                    <option value="Dar es salaam" selected="selected">Dar es salaam</option>
                                    <option value="Geita">Geita</option>
                                    <option value="Iringa">Iringa</option>
                                    <option value="Katavi">Katavi</option>
                                    <option value="Kagera">Kagera</option>
                                    <option value="Kigoma">kigoma</option>
                                    <option value="Kilimanjaro">kilimanjaro</option>
                                    <option value="Lindi">Lindi</option>
                                    <option value="Manyara">Manyara</option>
                                    <option value="Mara">Mara</option>
                                    <option value="Mbeya">Mbeya</option>
                                    <option value="Morogoro">Morogoro</option>
                                    <option value="Mtwara">Mtwara</option>
                                    <option value="Mwanza">Mwanza</option>
                                    <option value="Njombe">Njombe</option>
                                    <option value="Pwani">Pwani</option>
                                    <option value="Rukwa">Rukwa</option>
                                    <option value="Ruvuma">Ruvuma</option>
                                    <option value="Shinyanga">Shinyanga</option>
                                    <option value="Simiyu">Simiyu</option>
                                    <option value="Singida">Singida</option>
                                    <option value="Songwe">Songwe</option>
                                    <option value="Tanga">Tanga</option>
                                    <option value="Tabora">Tabora</option>
                                    <option value="Zanzibar">Zanzibar</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="" class="form-label">Role</label>
                                <select class="form-control" name="role" id="">
                                    <option value="" selected="selected" disabled="disabled">--select position
                                    </option>
                                    <option value="Managing director">managing director</option>
                                    <option value="Developer">Developer</option>
                                    <option value="Graphic designer">Graphic designer</option>
                                    <option value="Developer & graphic designer">Developer & graphic designer</option>
                                    <option value="Tresure">Tresure</option>
                                    <option value="Sales and marketing">Sales and marketing</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group mt-4">
                                <button class="btn btn-primary" type="submit">submit</button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
