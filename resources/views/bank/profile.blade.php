@extends('layouts.bank.default')

@section('title')
Edit Profile
@endsection

@section('breadcrumbTitle')
<a href="{{ route('dashboardPage') }}">Dashboard</a> / Edit Profile
@endsection

@section('content')
<div class="row">
    <div class="col-xl-6 col-lg-12">
        {{ Form::model($data, ['route' => ['bank-profile-update', $data->id], 'method' => 'patch']) }}
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Personal Info</h4>
            </div>

            <div class="card-body">
                <div class="basic-form">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label> &nbsp; &nbsp; Bank name</label>
                            <input class="form-control" type="text" name="bank_name" placeholder="Enter Name"
                                value="{{$data->bank_name}}">
                            @if ($errors->has('bank_name'))
                            <span class="help-block text-danger">
                                {{ $errors->first('bank_name') }}
                            </span>
                            @endif
                        </div>
                        <div class="form-group col-md-6">
                            <label> &nbsp; &nbsp; Email</label>
                            <!-- <input class="form-control" type="email" name="email" placeholder="Enter Email"
                                value="{{$data->email}}" {{(!empty($data->token))?'disabled':''}}> -->
                                <input class="form-control" type="email" name="email" placeholder="Enter Email"
                                value="{{$data->email}}" >
                            @if ($errors->has('email'))
                            <span class="help-block text-danger">
                                {{ $errors->first('email') }}
                            </span>
                            @endif

                            @if((!empty($data->email_changes)))
                            <div class="text-right">
                                <code>Note:-Your email change request has been pending.</code>

                                <a href="{{ route('resend.admin.profile') }}" class="btn btn-danger text-right"> Resend
                                    Mail </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary submit-btn-2">Save Changes </button>
                <!-- <a href="javascript:;" class="btn btn-danger">Cancel</a> -->
            </div>
        </div>
        {{ Form::close() }}
    </div>

    <div class="col-xl-6 col-lg-12">
        <form action="{{ route('bank-change-pass') }}" method="post">
            {{ csrf_field() }}
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Change Password</h4>
                </div>

                <div class="card-body">
                    <div class="basic-form">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Current Password</label>
                                <input class="form-control" type="password" placeholder="Enter here"
                                    name="current_password">
                                @if ($errors->has('current_password'))
                                <span class="help-block text-danger">
                                    {{ $errors->first('current_password') }}
                                </span>
                                @endif
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>New Password</label>
                                <input class="form-control" type="password" placeholder="Enter here" name="password">
                                @if ($errors->has('password'))
                                <span class="help-block text-danger">
                                    {{ $errors->first('password') }}
                                </span>
                                @endif
                            </div>
                            <div class="form-group col-md-6">
                                <label>Confirm Password</label>
                                <input class="form-control" type="password" placeholder="Enter here"
                                    name="password_confirmation">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary submit-btn"> Change Password </button>
                    <a href="{{ route('bank-dashboard') }}" class="btn btn-danger cancel-btn">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
