<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ config('app.name') }} | Merchant Reset Password</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ storage_asset('theme/images/Favicon.ico') }}">
    <link href="{{ storage_asset('theme/css/style.css') }}" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&family=Roboto:wght@100;300;400;500;700;900&display=swap"
        rel="stylesheet">
    <style type="text/css">
        .grecaptcha-badge {
            z-index: 1000;
        }

        .auth-form .text-danger {
            color: #842e2e !important;
        }

        .form-control:hover,
        .form-control:focus,
        .form-control.active {
            border-color: #E89C86;
        }

        .form-control:hover,
        .form-control:focus,
        .form-control.active,
        .select2-container--default .select2-selection--single .select2-selection__rendered,
        .form-control {
            color: #5a5a5a;
            font-size: 0.875rem;
        }
        .h-100vh {
            min-height: 100vh !important;
        }
        body{
            background-color: #FEFDFD;
        }
        .authincation-content{
            background-color: #FEFDFD;
        }
        .left-side{
            height:100vh;
            background-image: url("{{ storage_asset('theme/images/2_illustration_working_preview.gif') }}"); 
            background-size: 80% 100%;
            background-repeat: no-repeat;
            background-position: center;
        }
    </style>
</head>


<body class="h-100">
    <div class="authincation h-100">
        <div class="row justify-content-center h-100vh align-items-center">
            <div class="col-md-7 left-side"></div>
            <div class="col-md-4">
                <div class="authincation-content">
                    <div class="row no-gutters">
                        <div class="col-xl-12">
                            <div class="auth-form">
                                <div class="text-center">
                                    <a href="{{ route('login') }}">
                                        <img src="{{ storage_asset('theme/images/Logo.png') }}" alt="" width="300px">
                                    </a>
                                </div>
                                <h4 class="text-left mb-4 mt-4">Reset Password</h4>
                                <form method="POST" action="{{ route('rp-password-resetForm') }}"
                                    aria-label="{{ __('Reset Password') }}" id="form">
                                    {!! csrf_field() !!}
                                    <input type="hidden" name="token" value="{{ $token }}">
                                    @if(\Session::get('active'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        {{ $errors->first('active') }}
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">×</span>
                                        </button>
                                    </div>
                                    @endif
                                    @if(\Session::get('success'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        {{ \Session::get('success') }}
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">×</span>
                                        </button>
                                    </div>
                                    @endif
                                    {{ \Session::forget('success') }}
                                    @if(\Session::get('error'))
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        {{ \Session::get('error') }}
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">×</span>
                                        </button>
                                    </div>
                                    @endif
                                    {{ \Session::forget('error') }}

                                    <div class="form-group">
                                        <label class="mb-1"><strong>Email</strong></label>
                                        <input type="email" class="form-control box-shadow-blue"
                                            placeholder="Enter here" name="email"
                                            value="{{ request()->get('email') }}" readonly>
                                        @if ($errors->has('email'))
                                        <span class="help-block font-red-mint text-danger">
                                            <strong>{{ $errors->first('email') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label class="mb-1"><strong>Password</strong></label>
                                        <input type="password" class="form-control box-shadow-blue"
                                            placeholder="Enter here" name="password" autocomplete="off"
                                            id="password">
                                        @if ($errors->has('password'))
                                        <span class="help-block font-red-mint text-danger">
                                            <strong>{{ $errors->first('password') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label class="mb-1"><strong>Confirm Password</strong></label>
                                        <input type="password" class="form-control box-shadow-blue"
                                            placeholder="Enter here" name="password_confirmation"
                                            autocomplete="off" id="confirm_password">
                                        @if ($errors->has('password_confirmation'))
                                        <span class="help-block font-red-mint text-danger">
                                            <strong>{{ $errors->first('password_confirmation') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="text-center">
                                        <button class="btn bg-primary text-white btn-block g-recaptcha"
                                            data-sitekey="{{ config('app.captch_sitekey') }}"
                                            data-callback='onSubmit'
                                            data-action='submit'>{{ __('Reset Password') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-1"></div>
        </div>
    </div>

    <script src="{{ storage_asset('theme/vendor/global/global.min.js') }}"></script>
    <script src="{{ storage_asset('theme/vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ storage_asset('theme/js/custom.min.js') }}"></script>
    <script src="{{ storage_asset('theme/js/deznav-init.js') }}"></script>

    <script src="https://www.google.com/recaptcha/api.js"></script>
    <script>
        function onSubmit(token) {
                document.getElementById("form").submit();
            }
    </script>
</body>

</html>