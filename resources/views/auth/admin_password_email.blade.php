<!DOCTYPE html>
<html lang="en">
	<!--begin::Head-->
	<head>
		<title>{{ config('app.name') }} | Admin Reset Password</title>
		<meta charset="utf-8">
	    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	    <meta name="description" content="{{ config('app.name') }} Merchant Login">
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
			<!--begin::Authentication - New password -->
			<div class="d-flex flex-column flex-column-fluid bgi-position-y-bottom position-x-center bgi-no-repeat bgi-size-contain bgi-attachment-fixed" style="background-image: url('{{ storage_asset('/theme/assets/media/illustrations/sigma-1/14-dark.png') }}');">
				<!--begin::Content-->
				<div class="d-flex flex-center flex-column flex-column-fluid p-10 pb-lg-20">
					<!--begin::Logo-->
					<a href="{{ route('admin/login') }}" class="mb-12">
						<img alt="Logo" src="{{ storage_asset('theme/assets/images/logo.png') }}" style="width: 350px;" />
					</a>
					<!--end::Logo-->
					<!--begin::Wrapper-->
					<div class="w-lg-550px bg-body rounded shadow-sm p-10 p-lg-15 mx-auto">
						<!--begin::Form-->
						<form action="{{ route('admin-password-email') }}" method="post" class="form w-100" novalidate="novalidate" id="kt_new_password_form">
						{!! csrf_field() !!}
							<!--begin::Heading-->
							<div class="text-center mb-10">
								<!--begin::Title-->
								<h1 class="text-dark mb-3">Reset your password</h1>
								<!--end::Title-->
								<!--begin::Link-->
								<div class="text-gray-400 fw-bold fs-4">Enter your username or email address and we will send you a link to reset your password.</div>
								<!--end::Link-->
							</div>
							<!--begin::Heading-->
							<!--begin::Input group-->
							<div class="fv-row mb-10">
								<label class="form-label fw-bolder text-dark fs-6">Email</label>
								<input type="text" class="form-control form-control-solid" placeholder="Enter here" name="email" autocomplete="off">
                                @if ($errors->has('email'))
                                <span class="text-danger">
                                    {{ $errors->first('email') }}
                                </span>
                                @endif
							</div>
							<!--begin::Action-->
							<div class="text-center">
								<button type="button" id="kt_new_password_submit" class="btn btn-lg btn-primary fw-bolder g-recaptcha" data-sitekey="{{ config('app.captch_sitekey') }}" data-callback='onSubmit' data-action='submit'>
									<span class="indicator-label">Send password reset link</span>
									<span class="indicator-progress">Please wait...
									<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
								</button>
							</div>
							<!--end::Action-->
						</form>
						<!--end::Form-->
					</div>
					<!--end::Wrapper-->
				</div>
				<!--end::Content-->
				<!--begin::Footer-->
				<div class="d-flex flex-center flex-column-auto p-10">
					<!--begin::Links-->
					<div class="d-flex align-items-center fw-bold fs-6">
						<a href="https://keenthemes.com" class="text-muted text-hover-primary px-2">About</a>
						<a href="mailto:support@keenthemes.com" class="text-muted text-hover-primary px-2">Contact</a>
						<a href="https://1.envato.market/EA4JP" class="text-muted text-hover-primary px-2">Contact Us</a>
					</div>
					<!--end::Links-->
				</div>
				<!--end::Footer-->
			</div>
			<!--end::Authentication - New password-->
		</div>
		<!--end::Main-->
		<script>var hostUrl = "<?php echo storage_asset('theme/assets/'); ?>";</script>
		<!--begin::Javascript-->
		<!--begin::Global Javascript Bundle(used by all pages)-->
		<script src="{{ storage_asset('theme/assets/plugins/global/plugins.bundle.js') }}"></script>
		<script src="{{ storage_asset('theme/assets/js/scripts.bundle.js') }}"></script>
		<!--end::Global Javascript Bundle-->
		<!--begin::Page Custom Javascript(used by this page)-->
		<!-- <script src="{{ storage_asset('theme/assets/js/custom/authentication/password-reset/new-password.js') }}"></script> -->
		<!--end::Page Custom Javascript-->
		<!--end::Javascript-->

		<script src="https://www.google.com/recaptcha/api.js"></script>
	    <script>
	        function onSubmit(token) {
	        document.getElementById("kt_new_password_form").submit();
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

        @if(\Session::get('status'))
        <script type="text/javascript">
        	Swal.fire({
					text:"<?php echo Session::get('status');?>",
					icon:"success",buttonsStyling:!1,
					confirmButtonText:"Ok, got it!",
					customClass:{confirmButton:"btn btn-primary"}
				})
        </script>
        @endif
        {{ \Session::forget('status') }}
	</body>
	<!--end::Body-->
</html>