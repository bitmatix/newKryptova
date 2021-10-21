<!DOCTYPE html>
<html lang="en">
	<!--begin::Head-->
	<title>{{ config('app.name') }} | Merchant Register Verify</title>
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
	<body id="kt_body" class="bg-body">
		<!--begin::Main-->
		<div class="d-flex flex-column flex-root">
			<!--begin::Authentication - Signup Verify Email -->
			<div class="d-flex flex-column flex-column-fluid">
				<!--begin::Content-->
				<div class="d-flex flex-column flex-column-fluid text-center p-10 py-lg-15">
					<!--begin::Logo-->
					<a href="{{ route('login') }}" class="pt-lg-10">
                        <img alt="Logo" src="{{ storage_asset('theme/assets/images/logo.png') }}" style="width: 350px;" />
                    </a>
					<!--end::Logo-->
					<!--begin::Wrapper-->
					<div class="pt-lg-10 mb-10">
						<!--begin::Logo-->
						<h1 class="fw-bolder fs-2qx text-gray-800 mb-7">Verify Your Email</h1>
						<!--end::Logo-->
						<!--begin::Message-->
						<div class="fs-3 fw-bold text-muted mb-10">We have sent an email to
						<a href="#" class="link-primary fw-bolder">{{ config('app.email_support') }}</a>
						<br />pelase check and verify your email.</div>
						<!--end::Message-->
						<!--begin::Action-->
						<div class="text-center mb-10">
							<a href="{{ route('login') }}" class="btn btn-lg btn-primary fw-bolder">Return to Sign In</a>
						</div>
						<!--end::Action-->
					</div>
					<!--end::Wrapper-->
					<!--begin::Illustration-->
					<div class="d-flex flex-row-auto bgi-no-repeat bgi-position-x-center bgi-size-contain bgi-position-y-bottom min-h-100px min-h-lg-350px" style="background-image: url('{{ storage_asset('/theme/assets/media/illustrations/sigma-1/17-dark.png') }});"></div>
					<!--end::Illustration-->
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
			<!--end::Authentication - Signup Verify Email-->
		</div>
		<!--end::Main-->
		<script>var hostUrl = "<?php echo storage_asset('theme/assets/'); ?>";</script>
        <!--begin::Javascript-->
        <!--begin::Global Javascript Bundle(used by all pages)-->
        <script src="{{ storage_asset('theme/assets/plugins/global/plugins.bundle.js') }}"></script>
        <script src="{{ storage_asset('theme/assets/js/scripts.bundle.js') }}"></script>
        <!--end::Global Javascript Bundle-->
        <!--end::Javascript-->
	</body>
	<!--end::Body-->
</html>