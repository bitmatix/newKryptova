@extends('layouts.agent.default')
@section('title')
Profile
@endsection

@section('breadcrumbTitle')
Profile
@endsection
@section('content')

<div class="row gy-5 g-xl-8 d-flex align-items-center mt-lg-0 mb-10 mb-lg-15">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pt-8">
                <h3>Profile Edit</h3>
            </div>
            <div class="card-body">
                {{ Form::model($data, ['route' => ['rp-profile-update'], 'method' => 'post']) }}
                <div class="row">
                    <div class="form-group mb-10 col-lg-6">
                        <input class="form-control" type="text" name="name" placeholder="Enter Name"
                            value="{{$data->name}}">
                        @if ($errors->has('name'))
                            <span class="help-block font-red-mint text-danger">
                                {{ $errors->first('name') }}
                            </span>
                        @endif
                    </div>
                    <div class="form-group mb-10 col-lg-6">
                        <input class="form-control" type="email" name="email" placeholder="Enter Email"
                            value="{{$data->email}}">
                        @if ($errors->has('email'))
                            <span class="help-block font-red-mint text-danger">
                                {{ $errors->first('email') }}
                            </span>
                        @endif
                    </div>
                    <div class="form-group mb-10 col-lg-6">
                        <input class="form-control" type="password" placeholder="New Password" name="password">
                        @if ($errors->has('password'))
                            <span class="help-block font-red-mint text-danger">
                                {{ $errors->first('password') }}
                            </span>
                        @endif
                    </div>
                    <div class="form-group mb-10 col-lg-6">
                        <input class="form-control" type="password" placeholder="Re-type New Password"
                            name="password_confirmation">
                    </div>
                    <div class="form-group mb-10 col-lg-12">
                        <button type="submit" class="btn btn-success btn-sm me-3"> Submit </button>
                        <a href="{{ route('rp.dashboard') }}" class="btn btn-primary btn-sm">Cancel</a>
                    </div>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>
@endsection