<!DOCTYPE html>
<html lang="en">
	<!--begin::Head-->
	<head>
		<title>{{ config('app.name') }} |  Bank OTP</title>
		<meta charset="utf-8">
	    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	    <meta name="description" content="{{ config('app.name') }}  Bank OTP">
	    <meta name="author" content="{{ config('app.name') }}">

		<!-- <link rel="canonical" href="https://preview.keenthemes.com/metronic8" /> -->
		<link rel="shortcut icon" href="{{ storage_asset('theme/assets/media/logos/favicon.ico') }}" />
		<!--begin::Fonts-->
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
		<!--end::Fonts-->
		<!--begin::Global Stylesheets Bundle(used by all pages)-->
		<link href="{{ storage_asset('theme/assets/plugins/global/plugins.dark.bundle.rtl.css') }}" rel="stylesheet" type="text/css" />
		<link href="{{ storage_asset('theme/assets/css/style.dark.bundle.rtl.css') }}" rel="stylesheet" type="text/css" />
		<!--end::Global Stylesheets Bundle-->
	</head>
	<!--end::Head-->
	<!--begin::Body-->
	<body id="kt_body">
		<!--begin::Main-->
		<div class="d-flex flex-column flex-root">
			<!--begin::Authentication - Sign-in -->
			<div class="d-flex flex-column flex-column-fluid bgi-position-y-bottom position-x-center bgi-no-repeat bgi-size-contain bgi-attachment-fixed" style="background-image: url('{{ storage_asset('/theme/assets/media/illustrations/sigma-1/14-dark.png') }}');">
				<!--begin::Content-->
				<div class="d-flex flex-center flex-column flex-column-fluid p-10 pb-lg-20">
					<!--begin::Logo-->
					<a href="{{ route('bank/login') }}" class="mb-12">
						<img alt="Logo" src="{{ storage_asset('theme/assets/images/logo.png') }}" style="width: 350px;" />
					</a>
					<!--end::Logo-->
					<!--begin::Wrapper-->
					<div class="w-lg-600px bg-body rounded shadow-sm p-10 p-lg-15 mx-auto">
						<!--begin::Form-->
						<form action="{{ route('bank.kryptova-otp-store') }}" id="kt_sing_in_two_steps_form" method="post" class="form w-100" novalidate="novalidate">
                        {!! csrf_field() !!}
							<!--begin::Heading-->
							<div class="text-center mb-10">
								<!--begin::Title-->
								<h1 class="text-dark mb-3">Two Step Verification</h1>
								<!--end::Title-->
								<!--begin::Sub-title-->
								<div class="text-muted fw-bold fs-5 mb-5">
									@if(\Session::get('success'))
							        	{{ \Session::get('success') }}
							        @endif
								</div>
								<!--end::Sub-title-->
							</div>
							<!--end::Heading-->
							<!--begin::Section-->
							<div class="mb-10 px-md-10">
								<!--begin::Label-->
								<div class="fw-bolder text-start text-dark fs-6 mb-1 ms-1">Type your 6 digit security code</div>
								<!--end::Label-->
								<!--begin::Input group-->
								<div class="d-flex flex-wrap flex-stack">
									<input type="text" name="otp[1]" data-inputmask="'mask': '9', 'placeholder': ''" maxlength="1" class="form-control form-control-solid h-60px w-60px fs-2qx text-center border-primary border-hover mx-1 my-2" value="" />
									<input type="text" name="otp[2]" data-inputmask="'mask': '9', 'placeholder': ''" maxlength="1" class="form-control form-control-solid h-60px w-60px fs-2qx text-center border-primary border-hover mx-1 my-2" value="" />
									<input type="text" name="otp[3]" data-inputmask="'mask': '9', 'placeholder': ''" maxlength="1" class="form-control form-control-solid h-60px w-60px fs-2qx text-center border-primary border-hover mx-1 my-2" value="" />
									<input type="text" name="otp[4]" data-inputmask="'mask': '9', 'placeholder': ''" maxlength="1" class="form-control form-control-solid h-60px w-60px fs-2qx text-center border-primary border-hover mx-1 my-2" value="" />
									<input type="text" name="otp[5]" data-inputmask="'mask': '9', 'placeholder': ''" maxlength="1" class="form-control form-control-solid h-60px w-60px fs-2qx text-center border-primary border-hover mx-1 my-2" value="" />
									<input type="text" name="otp[6]" data-inputmask="'mask': '9', 'placeholder': ''" maxlength="1" class="form-control form-control-solid h-60px w-60px fs-2qx text-center border-primary border-hover mx-1 my-2" value="" />
								</div>

								@if ($errors->has('otp'))
                                    <span class="help-block font-red-mint text-danger">
                                        {{ $errors->first('otp') }}
                                    </span>
                                @endif
								<!--begin::Input group-->
							</div>
							<!--end::Input group-->
							<!--begin::Actions-->
							<div class="text-center">
								<!--begin::Submit button-->
								<button type="submit" id="kt_sing_in_two_steps_submit" class="btn btn-lg btn-primary w-100 mb-5 g-recaptcha" data-sitekey="{{ config('app.captch_sitekey') }}" data-callback='onSubmit' data-action='submit'>
									<span class="indicator-label">Login</span>
									<span class="indicator-progress">Please wait...
									<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
								</button>
								<!--end::Submit button-->
							</div>
							<!--end::Actions-->
						</form>
						<!--end::Form-->

						<div class="login-otp-main">
                            <a href="{{ route('bank.resend-otp') }}" class="btn btn-lg btn-danger w-100 mb-5 disabled" disabled id="resendOTP">Resend OTP <span id="countdown" >60s</span></a>
                        </div>
					</div>
					<!--end::Wrapper-->
				</div>
				<!--end::Content-->
			</div>
			<!--end::Authentication - Sign-in-->
		</div>
		<!--end::Main-->
		<script>var hostUrl = "<?php echo storage_asset('theme/assets/'); ?>";</script>
		<!--begin::Javascript-->
		<!--begin::Global Javascript Bundle(used by all pages)-->
		<script src="{{ storage_asset('theme/assets/plugins/global/plugins.bundle.js') }}"></script>
		<script src="{{ storage_asset('theme/assets/js/scripts.bundle.js') }}"></script>
		<!--end::Global Javascript Bundle-->
		<!--begin::Page Custom Javascript(used by this page)-->
		<script src="{{ storage_asset('theme/assets/js/custom/authentication/sign-in/two-steps.js') }}"></script>
		<!--end::Page Custom Javascript-->
		<!--end::Javascript-->

		<script type="text/javascript">
	        var timeLeft = 60;
	                var elem = document.getElementById('countdown');
	                var timerId = setInterval(countdown, 1000);
	                function countdown() {
	                    document.getElementById("resendOTP").disabled = true;
	                    document.getElementById("resendOTP").setAttribute("href", "javascript:void(0);");
	                    if (timeLeft == 0) {
	                        document.getElementById("resendOTP").disabled = false;
	                        document.getElementById("resendOTP").setAttribute("href", "{{ route('bank.resend-otp') }}");
	                        elem.innerHTML = '';
	                        $('#resendOTP').removeClass('disabled');
	                    } else {
	                        elem.innerHTML = timeLeft+'s';
	                        timeLeft--;
	                    }
	                }
	    </script>

	    <script src="https://www.google.com/recaptcha/api.js"></script>
	    <script>
	    function onSubmit(token) {
	        document.getElementById("kt_sing_in_two_steps_form").submit();
	    }
	    </script>

	    @if(\Session::get('error'))
        <script type="text/javascript">
        	Swal.fire({
					text:"<?php echo Session::get('error');?>",
					icon:"error",buttonsStyling:!1,
					confirmButtonText:"Ok, got it!",
					customClass:{confirmButton:"btn btn-primary"}
				})
        </script>
        @endif
        {{ \Session::forget('error') }}

        @if(\Session::get('success'))
        <script type="text/javascript">
        	Swal.fire({
					text:"<?php echo Session::get('success');?>",
					icon:"success",buttonsStyling:!1,
					confirmButtonText:"Ok, got it!",
					customClass:{confirmButton:"btn btn-primary"}
				})
        </script>
        @endif
        {{ \Session::forget('success') }}

	</body>
	<!--end::Body-->
</html>
