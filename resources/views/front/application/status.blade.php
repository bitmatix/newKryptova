@extends('layouts.user.default')

@section('title')
Dashboard
@endsection

@section('customeStyle')
@endsection

@section('content')
<div class="toolbar py-5 pt-lg-0" id="kt_toolbar">
	<!--begin::Container-->
	<div id="kt_toolbar_container" class="d-flex flex-stack flex-wrap" style="width: 100%;">
		<!--begin::Page title-->
		<div class="page-title d-flex flex-column me-3">
			<!--begin::Title-->
			<h1 class="d-flex text-gray-900 fw-bolder my-1 fs-3">Create Application</h1>
			<!--end::Title-->
			<!--begin::Breadcrumb-->
			<ul class="breadcrumb breadcrumb-line fw-bold fs-7 my-1">
				<!--begin::Item-->
				<li class="breadcrumb-item text-gray-700">
					<a href="#" class="text-gray-700 text-hover-primary">Dashboard</a>
				</li>
				<!--end::Item-->
				<!--begin::Item-->
				<li class="breadcrumb-item text-gray-700">Create Application</li>
				<!--end::Item-->
			</ul>
			<!--end::Breadcrumb-->
		</div>
		<!--end::Page title-->
		<!--begin::Actions-->
		<div class="d-flex align-items-center py-3 py-md-1">
			<!--begin::Button-->
			<a href="#" class="btn btn btn-dark" data-bs-toggle="modal" data-bs-target="#kt_modal_create_app" id="kt_toolbar_primary_button">Proceed</a>
			<!--end::Button-->
		</div>
		<!--end::Actions-->
	</div>
	<!--end::Container-->
</div>

@if(isset($data))
@if($data->status == 2)
@endif
@else
<div class="card">
	<!--begin::Card body-->
	<div class="card-body pb-0">
		<!--begin::Heading-->
		<div class="card-px text-center pt-20 pb-5">
			<!--begin::Title-->
			<h2 class="fs-2x fw-bolder mb-0">Please enter your details</h2>
			<!--end::Title-->
			<!--begin::Description-->
			<p class="text-gray-400 fs-4 fw-bold py-7">Please click on "Proceed" and follow the instructions to complete your application.</p>
			<!--end::Description-->
			<!--begin::Action-->
			<a href="#" class="btn btn-primary er fs-6 px-8 py-4" data-bs-toggle="modal" data-bs-target="#kt_modal_create_app">Proceed</a>
			<!--end::Action-->
		</div>
		<!--end::Heading-->
		<!--begin::Illustration-->
		<div class="text-center px-5">
			<img src="assets/media/illustrations/sigma-1/15.png" alt="" class="mw-100 h-200px h-sm-325px" />
		</div>
		<!--end::Illustration-->
		<div class="text-center px-5">
			<img src="{{ storage_asset('theme/assets/media/illustrations/sigma-1/15.png') }}" alt="" class="mw-100 h-200px h-sm-325px">
		</div>
	</div>
	<!--end::Card body-->
</div>
@endif

<div class="modal fade" id="kt_modal_create_app" tabindex="-1" aria-hidden="true">
	<!--begin::Modal dialog-->
	<div class="modal-dialog mw-1000px">
		<!--begin::Modal content-->
		<div class="modal-content">
			<!--begin::Modal header-->
			<div class="modal-header">
				<!--begin::Title-->
				<h2>Create Application</h2>
				<!--end::Title-->
				<!--begin::Close-->
				<div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
					<!--begin::Svg Icon | path: icons/duotune/arrows/arr061.svg-->
					<span class="svg-icon svg-icon-1">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
							<rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="black" />
							<rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="black" />
						</svg>
					</span>
					<!--end::Svg Icon-->
				</div>
				<!--end::Close-->
			</div>
			<!--end::Modal header-->
			<!--begin::Modal body-->
			<div class="modal-body scroll-y m-5">
				<!--begin::Stepper-->
				@include('partials.application.applicationFrom' ,['isEdit' => false])
				<!--end::Stepper-->
			</div>
			<!--end::Modal body-->
		</div>
		<!--end::Modal content-->
	</div>
	<!--end::Modal dialog-->
</div>
@endsection

@section('customScript')
<script src="{{ storage_asset('theme/assets/js/custom/modals/create-account.js') }}"></script>
<script>
    $(document).ready(function(){
        var value = $('.company_licenseOldValue').val()
        var oldIndustryTypeVal = $('.oldIndustryType').val()
        var oldProccessingCountryVal = $('.oldProccessingCountryVal').val()
       if(oldProccessingCountryVal){
           getProcessingCountryEdit(JSON.parse(oldProccessingCountryVal))
       }
        if(value) {
            getLicenseStatus(value)
        }
        otherIndustryType(oldIndustryTypeVal)
    });

    function getProcessingCountry(sel){
        var opts = [],
        opt;
        var len = sel.options.length;
        for (var i = 0; i < len; i++) {
             opt=sel.options[i];
            if (opt.selected) {
                  opts.push(opt.value);
            }
        } 

        getProcessingCountryEdit(opts)
       
    }

    function otherIndustryType(val){
        if(val == 28){
            $('.showOtherIndustryInput').removeClass('d-none')
        }
        else {
            $('.showOtherIndustryInputBox').val('')
            $('.showOtherIndustryInput').addClass('d-none')
        }
    }

    function getLicenseStatus(val){
        if(val == 0){
            $('.toggleLicenceDocs').removeClass('d-none')
        } else {
            $('.toggleLicenceDocs').addClass('d-none')
        }
    }

    function getProcessingCountryEdit(arr){
       
        var isExist = arr.filter(function(item) {
            return item == 'Others'
        })
        if(isExist.length > 0){
            $('.otherProcessingInput').removeClass('d-none')
        } else {
            $('.otherProcessingInput').addClass('d-none')
        }
    }
</script>
@endsection