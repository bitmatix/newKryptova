<div class="stepper stepper-links d-flex flex-column" id="kt_create_account_stepper">
	<!--begin::Nav-->
	<div class="stepper-nav py-5">
		<!--begin::Step 1-->
		<div class="stepper-item current" data-kt-stepper-element="nav">
			<h3 class="stepper-title">Business Type</h3>
		</div>
		<!--end::Step 1-->
		<!--begin::Step 2-->
		<div class="stepper-item" data-kt-stepper-element="nav">
			<h3 class="stepper-title">Business Details</h3>
		</div>
		<!--end::Step 2-->
		<!--begin::Step 3-->
		<div class="stepper-item" data-kt-stepper-element="nav">
			<h3 class="stepper-title">Document</h3>
		</div>
		<!--end::Step 3-->
	</div>
	<!--end::Nav-->
	<!--begin::Form-->
	<form class="mx-auto w-100 py-10" novalidate="novalidate" id="kt_create_account_form" enctype="multipart/form-data">
		<!--begin::Step 1-->
		<div class="current1" data-kt-stepper-element="content">
			<!--begin::Wrapper-->
			<div class="w-100">
				<div class="pb-10 pb-lg-5">
					<!--begin::Title-->
					<h2 class="fw-bolder d-flex align-items-center text-dark">Choose Business Type</h2>
					<!--end::Title-->
				</div>
				<!--begin::Input group-->
				<div class="fv-row">
					<!--begin::Row-->
					<div class="row">
						<!--begin::Col-->
						<div class="col-lg-4">
							<!--begin::Option-->
							<input type="radio" class="btn-check" name="business_type" value="Sole Proprietorship" checked="checked" id="kt_create_account_form_account_type_personal" />
							<label class="btn btn-outline btn-outline-dashed btn-outline-default p-7 d-flex align-items-center mb-10" for="kt_create_account_form_account_type_personal">
								<!--begin::Svg Icon | path: icons/duotune/communication/com005.svg-->
								<span class="svg-icon svg-icon-3x me-5">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
										<path opacity="0.3" d="M20 15H4C2.9 15 2 14.1 2 13V7C2 6.4 2.4 6 3 6H21C21.6 6 22 6.4 22 7V13C22 14.1 21.1 15 20 15ZM13 12H11C10.5 12 10 12.4 10 13V16C10 16.5 10.4 17 11 17H13C13.6 17 14 16.6 14 16V13C14 12.4 13.6 12 13 12Z" fill="black" />
										<path d="M14 6V5H10V6H8V5C8 3.9 8.9 3 10 3H14C15.1 3 16 3.9 16 5V6H14ZM20 15H14V16C14 16.6 13.5 17 13 17H11C10.5 17 10 16.6 10 16V15H4C3.6 15 3.3 14.9 3 14.7V18C3 19.1 3.9 20 5 20H19C20.1 20 21 19.1 21 18V14.7C20.7 14.9 20.4 15 20 15Z" fill="black" />
									</svg>
								</span>
								<!--end::Svg Icon-->
								<!--begin::Info-->
								<span class="d-block fw-bold text-start">
									<span class="text-dark fw-bolder d-block fs-4 mb-2">Sole Proprietorship</span>
								</span>
								<!--end::Info-->
							</label>
							<!--end::Option-->
						</div>
						<!--end::Col-->
						<!--begin::Col-->
						<div class="col-lg-4">
							<!--begin::Option-->
							<input type="radio" class="btn-check" name="business_type" value="Partnership" id="kt_create_account_form_account_type_corporate" />
							<label class="btn btn-outline btn-outline-dashed btn-outline-default p-7 d-flex align-items-center" for="kt_create_account_form_account_type_corporate">
								<!--begin::Svg Icon | path: icons/duotune/finance/fin006.svg-->
								<span class="svg-icon svg-icon-3x me-5">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
										<path opacity="0.3" d="M20 15H4C2.9 15 2 14.1 2 13V7C2 6.4 2.4 6 3 6H21C21.6 6 22 6.4 22 7V13C22 14.1 21.1 15 20 15ZM13 12H11C10.5 12 10 12.4 10 13V16C10 16.5 10.4 17 11 17H13C13.6 17 14 16.6 14 16V13C14 12.4 13.6 12 13 12Z" fill="black" />
										<path d="M14 6V5H10V6H8V5C8 3.9 8.9 3 10 3H14C15.1 3 16 3.9 16 5V6H14ZM20 15H14V16C14 16.6 13.5 17 13 17H11C10.5 17 10 16.6 10 16V15H4C3.6 15 3.3 14.9 3 14.7V18C3 19.1 3.9 20 5 20H19C20.1 20 21 19.1 21 18V14.7C20.7 14.9 20.4 15 20 15Z" fill="black" />
									</svg>
								</span>
								<!--end::Svg Icon-->
								<!--begin::Info-->
								<span class="d-block fw-bold text-start">
									<span class="text-dark fw-bolder d-block fs-4 mb-2">Partnership</span>
								</span>
								<!--end::Info-->
							</label>
							<!--end::Option-->
						</div>

						<div class="col-lg-4">
							<!--begin::Option-->
							<input type="radio" class="btn-check" name="business_type" value="Corporation/LTD" id="kt_create_account_form_account_type_corporate1" />
							<label class="btn btn-outline btn-outline-dashed btn-outline-default p-7 d-flex align-items-center" for="kt_create_account_form_account_type_corporate1">
								<!--begin::Svg Icon | path: icons/duotune/finance/fin006.svg-->
								<span class="svg-icon svg-icon-3x me-5">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
										<path opacity="0.3" d="M20 15H4C2.9 15 2 14.1 2 13V7C2 6.4 2.4 6 3 6H21C21.6 6 22 6.4 22 7V13C22 14.1 21.1 15 20 15ZM13 12H11C10.5 12 10 12.4 10 13V16C10 16.5 10.4 17 11 17H13C13.6 17 14 16.6 14 16V13C14 12.4 13.6 12 13 12Z" fill="black" />
										<path d="M14 6V5H10V6H8V5C8 3.9 8.9 3 10 3H14C15.1 3 16 3.9 16 5V6H14ZM20 15H14V16C14 16.6 13.5 17 13 17H11C10.5 17 10 16.6 10 16V15H4C3.6 15 3.3 14.9 3 14.7V18C3 19.1 3.9 20 5 20H19C20.1 20 21 19.1 21 18V14.7C20.7 14.9 20.4 15 20 15Z" fill="black" />
									</svg>
								</span>
								<!--end::Svg Icon-->
								<!--begin::Info-->
								<span class="d-block fw-bold text-start">
									<span class="text-dark fw-bolder d-block fs-4 mb-2">Corporation/LTD</span>
								</span>
								<!--end::Info-->
							</label>
							<!--end::Option-->
						</div>
						<!--end::Col-->
						
					</div>
					<!--end::Row-->
				</div>
				<!--end::Input group-->
			</div>
			<!--end::Wrapper-->
		</div>
		<!--end::Step 1-->
		<!--begin::Step 2-->
		<div class="current" data-kt-stepper-element="content">
			<!--begin::Wrapper-->
			<div class="w-100">
				<!--begin::Heading-->
				<div class="pb-10 pb-lg-5">
					<!--begin::Title-->
					<h2 class="fw-bolder text-dark">Business Details</h2>
					<!--end::Title-->
				</div>
				<!--end::Heading-->
				
				<!--begin::Input group-->
				<div class="fv-row">
					<!--begin::Row-->
					<div class="row">
						<!--begin::Col-->
						<div class="col-lg-4">
							<label class="form-label">Accepted Payment Methods <span class="text-danger">*</span></label>
							{!! Form::select('accept_card[]', ['Credit Card'=>'Credit Card','Debit Card'=>'Debit Card','Discover'=>'Discover','Amex'=>'Amex','Cryptocurrency'=>'Cryptocurrency'], isset($data->accept_card) ? json_decode($data->accept_card) : [], array('id' => 'accept_card','class' => 'form-select', 'data-control' => 'select2','multiple' => 'multiple', 'data-placeholder' =>'Select an option')) !!}
						</div>
						<div class="col-lg-4">
							<label class="form-label">Company Name <span class="text-danger">*</span></label>
							{!! Form::text('business_name', Input::get('business_name'), array('placeholder' => 'Enter here...','class' =>'form-control form-control-lg form-control-solid','id'=>'business_name')) !!}
						</div>

						<div class="col-lg-4">
							<label class="form-label">Your Website URL <span class="text-danger">*</span>
								<i class="fas fa-exclamation-circle ms-2 fs-7" data-bs-toggle="tooltip" title="" data-bs-original-title="https://example.com" aria-label="https://example.com"></i>
							</label>
							{!! Form::text('website_url', Input::get('website_url'), array('placeholder' => 'Enter here...','class' =>'form-control form-control-lg form-control-solid','id'=>'website_url')) !!}
						</div>
						<div class="col-lg-4">
							<label class="form-label mt-3">First Name <span class="text-danger">*</span></label>
							<input type="text" id="business_contact_first_name" class="form-control form-control-lg form-control-solid" name="business_contact_first_name" placeholder="Enter here..." value="{{ isset($data->business_contact_first_name) ? $data->business_contact_first_name : Input::old('business_contact_first_name') }}">
						</div>

						<div class="col-lg-4">
							<label class="form-label mt-3">Last Name <span class="text-danger">*</span></label>
							<input type="text" class="form-control form-control-lg form-control-solid" id="business_contact_last_name" name="business_contact_last_name" placeholder="Enter here..." value="{{ isset($data->business_contact_last_name) ? $data->business_contact_last_name : Input::old('business_contact_last_name') }}">
						</div>

						<div class="col-lg-4">
							<label class="form-label mt-3">Residential Address <span class="text-danger">*</span></label>
							<input type="text" id="residential_address" class="form-control form-control-lg form-control-solid" name="residential_address" placeholder="Enter here..." value="{{ isset($data->residential_address) ? $data->residential_address : Input::old('residential_address') }}">
						</div>
						<div class="col-lg-4">
							<label class="form-label mt-3">Company Address <span class="text-danger">*</span></label>
							<input type="text" id="business_address1" class="form-control form-control-lg form-control-solid" name="business_address1" placeholder="Enter here..." value="{{ isset($data->business_address1) ? $data->business_address1 : Input::old('business_address1') }}">
						</div>

						<div class="col-lg-4">
							<div class="row">
								<div class="col-lg-5">
									<label class="form-label mt-3">Country Code <span class="text-danger">*</span></label>
									<select class="form-select" data-control="select2" data-placeholder="Select an option" name="country_code">
					                    <option value="44" {{ old('country_code')== '44' ?'selected':'' }}>UK (+44)</option>
					                    <option value="1" {{ old('country_code')== '1' ?'selected':'' }}>USA (+1)</option>
					                    <option data-countryCode="DZ" {{ old('country_code')== '213' ?'selected':'' }} value="213">Algeria
					                        (+213)</option>
					                    <option data-countryCode="AD" {{ old('country_code')== '376' ?'selected':'' }} value="376">Andorra
					                        (+376)</option>
					                    <option data-countryCode="AO" {{ old('country_code')== '244' ?'selected':'' }} value="244">Angola
					                        (+244)</option>
					                    <option data-countryCode="AI" {{ old('country_code')== '1264' ?'selected':'' }} value="1264">
					                        Anguilla (+1264)</option>
					                    <option data-countryCode="AG" {{ old('country_code')== '1268' ?'selected':'' }} value="1268">Antigua
					                        &amp; Barbuda (+1268)</option>
					                    <option data-countryCode="AR" {{ old('country_code')== '54' ?'selected':'' }} value="54">Argentina
					                        (+54)</option>
					                    <option data-countryCode="AM" {{ old('country_code')== '374' ?'selected':'' }} value="374">Armenia
					                        (+374)</option>
					                    <option data-countryCode="AW" {{ old('country_code')== '297' ?'selected':'' }} value="297">Aruba
					                        (+297)</option>
					                    <option data-countryCode="AU" {{ old('country_code')== '61' ?'selected':'' }} value="61">Australia
					                        (+61)</option>
					                    <option data-countryCode="AT" {{ old('country_code')== '43' ?'selected':'' }} value="43">Austria
					                        (+43)</option>
					                    <option data-countryCode="AZ" {{ old('country_code')== '994' ?'selected':'' }} value="994">
					                        Azerbaijan (+994)</option>
					                    <option data-countryCode="BS" {{ old('country_code')== '1242' ?'selected':'' }} value="1242">Bahamas
					                        (+1242)</option>
					                    <option data-countryCode="BH" {{ old('country_code')== '973' ?'selected':'' }} value="973">Bahrain
					                        (+973)</option>
					                    <option data-countryCode="BD" {{ old('country_code')== '880' ?'selected':'' }} value="880">
					                        Bangladesh (+880)</option>
					                    <option data-countryCode="BB" {{ old('country_code')== '1246' ?'selected':'' }} value="1246">
					                        Barbados (+1246)</option>
					                    <option data-countryCode="BY" {{ old('country_code')== '375' ?'selected':'' }} value="375">Belarus
					                        (+375)</option>
					                    <option data-countryCode="BE" {{ old('country_code')== '32' ?'selected':'' }} value="32">Belgium
					                        (+32)</option>
					                    <option data-countryCode="BZ" {{ old('country_code')== '501' ?'selected':'' }} value="501">Belize
					                        (+501)</option>
					                    <option data-countryCode="BJ" {{ old('country_code')== '229' ?'selected':'' }} value="229">Benin
					                        (+229)</option>
					                    <option data-countryCode="BM" {{ old('country_code')== '1441' ?'selected':'' }} value="1441">Bermuda
					                        (+1441)</option>
					                    <option data-countryCode="BT" {{ old('country_code')== '975' ?'selected':'' }} value="975">Bhutan
					                        (+975)</option>
					                    <option data-countryCode="BO" {{ old('country_code')== '591' ?'selected':'' }} value="591">Bolivia
					                        (+591)</option>
					                    <option data-countryCode="BA" {{ old('country_code')== '387' ?'selected':'' }} value="387">Bosnia
					                        Herzegovina (+387)</option>
					                    <option data-countryCode="BW" {{ old('country_code')== '267' ?'selected':'' }} value="267">Botswana
					                        (+267)</option>
					                    <option data-countryCode="BR" {{ old('country_code')== '55' ?'selected':'' }} value="55">Brazil
					                        (+55)</option>
					                    <option data-countryCode="BN" {{ old('country_code')== '673' ?'selected':'' }} value="673">Brunei
					                        (+673)</option>
					                    <option data-countryCode="BG" {{ old('country_code')== '359' ?'selected':'' }} value="359">Bulgaria
					                        (+359)</option>
					                    <option data-countryCode="BF" {{ old('country_code')== '226' ?'selected':'' }} value="226">Burkina
					                        Faso (+226)</option>
					                    <option data-countryCode="BI" {{ old('country_code')== '257' ?'selected':'' }} value="257">Burundi
					                        (+257)</option>
					                    <option data-countryCode="KH" {{ old('country_code')== '855' ?'selected':'' }} value="855">Cambodia
					                        (+855)</option>
					                    <option data-countryCode="CM" {{ old('country_code')== '237' ?'selected':'' }} value="237">Cameroon
					                        (+237)</option>
					                    <option data-countryCode="CA" {{ old('country_code')== '1' ?'selected':'' }} value="1">Canada (+1)
					                    </option>
					                    <option data-countryCode="CV" {{ old('country_code')== '238' ?'selected':'' }} value="238">Cape
					                        Verde Islands (+238)</option>
					                    <option data-countryCode="KY" {{ old('country_code')== '1345' ?'selected':'' }} value="1345">Cayman
					                        Islands (+1345)</option>
					                    <option data-countryCode="CF" {{ old('country_code')== '236' ?'selected':'' }} value="236">Central
					                        African Republic (+236)</option>
					                    <option data-countryCode="CL" {{ old('country_code')== '56' ?'selected':'' }} value="56">Chile (+56)
					                    </option>
					                    <option data-countryCode="CN" {{ old('country_code')== '86' ?'selected':'' }} value="86">China (+86)
					                    </option>
					                    <option data-countryCode="CO" {{ old('country_code')== '57' ?'selected':'' }} value="57">Colombia
					                        (+57)</option>
					                    <option data-countryCode="KM" {{ old('country_code')== '269' ?'selected':'' }} value="269">Comoros
					                        (+269)</option>
					                    <option data-countryCode="CG" {{ old('country_code')== '242' ?'selected':'' }} value="242">Congo
					                        (+242)</option>
					                    <option data-countryCode="CK" {{ old('country_code')== '682' ?'selected':'' }} value="682">Cook
					                        Islands (+682)</option>
					                    <option data-countryCode="CR" {{ old('country_code')== '506' ?'selected':'' }} value="506">Costa
					                        Rica (+506)</option>
					                    <option data-countryCode="HR" {{ old('country_code')== '385' ?'selected':'' }} value="385">Croatia
					                        (+385)</option>
					                    <option data-countryCode="CU" {{ old('country_code')== '53' ?'selected':'' }} value="53">Cuba (+53)
					                    </option>
					                    <option data-countryCode="CY" {{ old('country_code')== '90392' ?'selected':'' }} value="90392">
					                        Cyprus North (+90392)</option>
					                    <option data-countryCode="CY" {{ old('country_code')== '357' ?'selected':'' }} value="357">Cyprus
					                        South (+357)</option>
					                    <option data-countryCode="CZ" {{ old('country_code')== '42' ?'selected':'' }} value="42">Czech
					                        Republic (+42)</option>
					                    <option data-countryCode="DK" {{ old('country_code')== '45' ?'selected':'' }} value="45">Denmark
					                        (+45)</option>
					                    <option data-countryCode="DJ" {{ old('country_code')== '253' ?'selected':'' }} value="253">Djibouti
					                        (+253)</option>
					                    <option data-countryCode="DM" {{ old('country_code')== '1767' ?'selected':'' }} value="1767">
					                        Dominica (+1767)</option>
					                    <option data-countryCode="DO" {{ old('country_code')== '1809' ?'selected':'' }} value="1809">
					                        Dominican Republic (+1809)</option>
					                    <option data-countryCode="EC" {{ old('country_code')== '593' ?'selected':'' }} value="593">Ecuador
					                        (+593)</option>
					                    <option data-countryCode="EG" {{ old('country_code')== '20' ?'selected':'' }} value="20">Egypt (+20)
					                    </option>
					                    <option data-countryCode="SV" {{ old('country_code')== '503' ?'selected':'' }} value="503">El
					                        Salvador (+503)</option>
					                    <option data-countryCode="GQ" {{ old('country_code')== '240' ?'selected':'' }} value="240">
					                        Equatorial Guinea (+240)</option>
					                    <option data-countryCode="ER" {{ old('country_code')== '291' ?'selected':'' }} value="291">Eritrea
					                        (+291)</option>
					                    <option data-countryCode="EE" {{ old('country_code')== '372' ?'selected':'' }} value="372">Estonia
					                        (+372)</option>
					                    <option data-countryCode="ET" {{ old('country_code')== '251' ?'selected':'' }} value="251">Ethiopia
					                        (+251)</option>
					                    <option data-countryCode="FK" {{ old('country_code')== '500' ?'selected':'' }} value="500">Falkland
					                        Islands (+500)</option>
					                    <option data-countryCode="FO" {{ old('country_code')== '298' ?'selected':'' }} value="298">Faroe
					                        Islands (+298)</option>
					                    <option data-countryCode="FJ" {{ old('country_code')== '679' ?'selected':'' }} value="679">Fiji
					                        (+679)</option>
					                    <option data-countryCode="FI" {{ old('country_code')== '358' ?'selected':'' }} value="358">Finland
					                        (+358)</option>
					                    <option data-countryCode="FR" {{ old('country_code')== '33' ?'selected':'' }} value="33">France
					                        (+33)</option>
					                    <option data-countryCode="GF" {{ old('country_code')== '594' ?'selected':'' }} value="594">French
					                        Guiana (+594)</option>
					                    <option data-countryCode="PF" {{ old('country_code')== '689' ?'selected':'' }} value="689">French
					                        Polynesia (+689)</option>
					                    <option data-countryCode="GA" {{ old('country_code')== '241' ?'selected':'' }} value="241">Gabon
					                        (+241)</option>
					                    <option data-countryCode="GM" {{ old('country_code')== '220' ?'selected':'' }} value="220">Gambia
					                        (+220)</option>
					                    <option data-countryCode="GE" {{ old('country_code')== '995' ?'selected':'' }} value="995">Georgia
					                        (+995)</option>
					                    <option data-countryCode="DE" {{ old('country_code')== '49' ?'selected':'' }} value="49">Germany
					                        (+49)</option>
					                    <option data-countryCode="GH" {{ old('country_code')== '233' ?'selected':'' }} value="233">Ghana
					                        (+233)</option>
					                    <option data-countryCode="GI" {{ old('country_code')== '350' ?'selected':'' }} value="350">Gibraltar
					                        (+350)</option>
					                    <option data-countryCode="GR" {{ old('country_code')== '30' ?'selected':'' }} value="30">Greece
					                        (+30)</option>
					                    <option data-countryCode="GL" {{ old('country_code')== '299' ?'selected':'' }} value="299">Greenland
					                        (+299)</option>
					                    <option data-countryCode="GD" {{ old('country_code')== '1473' ?'selected':'' }} value="1473">Grenada
					                        (+1473)</option>
					                    <option data-countryCode="GP" {{ old('country_code')== '590' ?'selected':'' }} value="590">
					                        Guadeloupe (+590)</option>
					                    <option data-countryCode="GU" {{ old('country_code')== '671' ?'selected':'' }} value="671">Guam
					                        (+671)</option>
					                    <option data-countryCode="GT" {{ old('country_code')== '502' ?'selected':'' }} value="502">Guatemala
					                        (+502)</option>
					                    <option data-countryCode="GN" {{ old('country_code')== '224' ?'selected':'' }} value="224">Guinea
					                        (+224)</option>
					                    <option data-countryCode="GW" {{ old('country_code')== '245' ?'selected':'' }} value="245">Guinea -
					                        Bissau (+245)</option>
					                    <option data-countryCode="GY" {{ old('country_code')== '592' ?'selected':'' }} value="592">Guyana
					                        (+592)</option>
					                    <option data-countryCode="HT" {{ old('country_code')== '509' ?'selected':'' }} value="509">Haiti
					                        (+509)</option>
					                    <option data-countryCode="HN" {{ old('country_code')== '504' ?'selected':'' }} value="504">Honduras
					                        (+504)</option>
					                    <option data-countryCode="HK" {{ old('country_code')== '852' ?'selected':'' }} value="852">Hong Kong
					                        (+852)</option>
					                    <option data-countryCode="HU" {{ old('country_code')== '36' ?'selected':'' }} value="36">Hungary
					                        (+36)</option>
					                    <option data-countryCode="IS" {{ old('country_code')== '354' ?'selected':'' }} value="354">Iceland
					                        (+354)</option>
					                    <option data-countryCode="IN" {{ old('country_code')== '91' ?'selected':'' }} value="91">India (+91)
					                    </option>
					                    <option data-countryCode="ID" {{ old('country_code')== '62' ?'selected':'' }} value="62">Indonesia
					                        (+62)</option>
					                    <option data-countryCode="IR" {{ old('country_code')== '98' ?'selected':'' }} value="98">Iran (+98)
					                    </option>
					                    <option data-countryCode="IQ" {{ old('country_code')== '964' ?'selected':'' }} value="964">Iraq
					                        (+964)</option>
					                    <option data-countryCode="IE" {{ old('country_code')== '353' ?'selected':'' }} value="353">Ireland
					                        (+353)</option>
					                    <option data-countryCode="IL" {{ old('country_code')== '972' ?'selected':'' }} value="972">Israel
					                        (+972)</option>
					                    <option data-countryCode="IT" {{ old('country_code')== '39' ?'selected':'' }} value="39">Italy (+39)
					                    </option>
					                    <option data-countryCode="JM" {{ old('country_code')== '1876' ?'selected':'' }} value="1876">Jamaica
					                        (+1876)</option>
					                    <option data-countryCode="JP" {{ old('country_code')== '81' ?'selected':'' }} value="81">Japan (+81)
					                    </option>
					                    <option data-countryCode="JO" {{ old('country_code')== '962' ?'selected':'' }} value="962">Jordan
					                        (+962)</option>
					                    <option data-countryCode="KZ" {{ old('country_code')== '7' ?'selected':'' }} value="7">Kazakhstan
					                        (+7)</option>
					                    <option data-countryCode="KE" {{ old('country_code')== '254' ?'selected':'' }} value="254">Kenya
					                        (+254)</option>
					                    <option data-countryCode="KI" {{ old('country_code')== '686' ?'selected':'' }} value="686">Kiribati
					                        (+686)</option>
					                    <option data-countryCode="KP" {{ old('country_code')== '850' ?'selected':'' }} value="850">Korea
					                        North (+850)</option>
					                    <option data-countryCode="KR" {{ old('country_code')== '82' ?'selected':'' }} value="82">Korea South
					                        (+82)</option>
					                    <option data-countryCode="KW" {{ old('country_code')== '965' ?'selected':'' }} value="965">Kuwait
					                        (+965)</option>
					                    <option data-countryCode="KG" {{ old('country_code')== '996' ?'selected':'' }} value="996">
					                        Kyrgyzstan (+996)</option>
					                    <option data-countryCode="LA" {{ old('country_code')== '856' ?'selected':'' }} value="856">Laos
					                        (+856)</option>
					                    <option data-countryCode="LV" {{ old('country_code')== '371' ?'selected':'' }} value="371">Latvia
					                        (+371)</option>
					                    <option data-countryCode="LB" {{ old('country_code')== '961' ?'selected':'' }} value="961">Lebanon
					                        (+961)</option>
					                    <option data-countryCode="LS" {{ old('country_code')== '266' ?'selected':'' }} value="266">Lesotho
					                        (+266)</option>
					                    <option data-countryCode="LR" {{ old('country_code')== '231' ?'selected':'' }} value="231">Liberia
					                        (+231)</option>
					                    <option data-countryCode="LY" {{ old('country_code')== '218' ?'selected':'' }} value="218">Libya
					                        (+218)</option>
					                    <option data-countryCode="LI" {{ old('country_code')== '417' ?'selected':'' }} value="417">
					                        Liechtenstein (+417)</option>
					                    <option data-countryCode="LT" {{ old('country_code')== '370' ?'selected':'' }} value="370">Lithuania
					                        (+370)</option>
					                    <option data-countryCode="LU" {{ old('country_code')== '352' ?'selected':'' }} value="352">
					                        Luxembourg (+352)</option>
					                    <option data-countryCode="MO" {{ old('country_code')== '853' ?'selected':'' }} value="853">Macao
					                        (+853)</option>
					                    <option data-countryCode="MK" {{ old('country_code')== '389' ?'selected':'' }} value="389">Macedonia
					                        (+389)</option>
					                    <option data-countryCode="MG" {{ old('country_code')== '261' ?'selected':'' }} value="261">
					                        Madagascar (+261)</option>
					                    <option data-countryCode="MW" {{ old('country_code')== '265' ?'selected':'' }} value="265">Malawi
					                        (+265)</option>
					                    <option data-countryCode="MY" {{ old('country_code')== '60' ?'selected':'' }} value="60">Malaysia
					                        (+60)</option>
					                    <option data-countryCode="MV" {{ old('country_code')== '960' ?'selected':'' }} value="960">Maldives
					                        (+960)</option>
					                    <option data-countryCode="ML" {{ old('country_code')== '223' ?'selected':'' }} value="223">Mali
					                        (+223)</option>
					                    <option data-countryCode="MT" {{ old('country_code')== '356' ?'selected':'' }} value="356">Malta
					                        (+356)</option>
					                    <option data-countryCode="MH" {{ old('country_code')== '692' ?'selected':'' }} value="692">Marshall
					                        Islands (+692)</option>
					                    <option data-countryCode="MQ" {{ old('country_code')== '596' ?'selected':'' }} value="596">
					                        Martinique (+596)</option>
					                    <option data-countryCode="MR" {{ old('country_code')== '222' ?'selected':'' }} value="222">
					                        Mauritania (+222)</option>
					                    <option data-countryCode="YT" {{ old('country_code')== '269' ?'selected':'' }} value="269">Mayotte
					                        (+269)</option>
					                    <option data-countryCode="MX" {{ old('country_code')== '52' ?'selected':'' }} value="52">Mexico
					                        (+52)</option>
					                    <option data-countryCode="FM" {{ old('country_code')== '691' ?'selected':'' }} value="691">
					                        Micronesia (+691)</option>
					                    <option data-countryCode="MD" {{ old('country_code')== '373' ?'selected':'' }} value="373">Moldova
					                        (+373)</option>
					                    <option data-countryCode="MC" {{ old('country_code')== '377' ?'selected':'' }} value="377">Monaco
					                        (+377)</option>
					                    <option data-countryCode="MN" {{ old('country_code')== '976' ?'selected':'' }} value="976">Mongolia
					                        (+976)</option>
					                    <option data-countryCode="MS" {{ old('country_code')== '1664' ?'selected':'' }} value="1664">
					                        Montserrat (+1664)</option>
					                    <option data-countryCode="MA" {{ old('country_code')== '212' ?'selected':'' }} value="212">Morocco
					                        (+212)</option>
					                    <option data-countryCode="MZ" {{ old('country_code')== '258' ?'selected':'' }} value="258">
					                        Mozambique (+258)</option>
					                    <option data-countryCode="MN" {{ old('country_code')== '95' ?'selected':'' }} value="95">Myanmar
					                        (+95)</option>
					                    <option data-countryCode="NA" {{ old('country_code')== '264' ?'selected':'' }} value="264">Namibia
					                        (+264)</option>
					                    <option data-countryCode="NR" {{ old('country_code')== '674' ?'selected':'' }} value="674">Nauru
					                        (+674)</option>
					                    <option data-countryCode="NP" {{ old('country_code')== '977' ?'selected':'' }} value="977">Nepal
					                        (+977)</option>
					                    <option data-countryCode="NL" {{ old('country_code')== '31' ?'selected':'' }} value="31">Netherlands
					                        (+31)</option>
					                    <option data-countryCode="NC" {{ old('country_code')== '687' ?'selected':'' }} value="687">New
					                        Caledonia (+687)</option>
					                    <option data-countryCode="NZ" {{ old('country_code')== '64' ?'selected':'' }} value="64">New Zealand
					                        (+64)</option>
					                    <option data-countryCode="NI" {{ old('country_code')== '505' ?'selected':'' }} value="505">Nicaragua
					                        (+505)</option>
					                    <option data-countryCode="NE" {{ old('country_code')== '227' ?'selected':'' }} value="227">Niger
					                        (+227)</option>
					                    <option data-countryCode="NG" {{ old('country_code')== '234' ?'selected':'' }} value="234">Nigeria
					                        (+234)</option>
					                    <option data-countryCode="NU" {{ old('country_code')== '683' ?'selected':'' }} value="683">Niue
					                        (+683)</option>
					                    <option data-countryCode="NF" {{ old('country_code')== '672' ?'selected':'' }} value="672">Norfolk
					                        Islands (+672)</option>
					                    <option data-countryCode="NP" {{ old('country_code')== '670' ?'selected':'' }} value="670">Northern
					                        Marianas (+670)</option>
					                    <option data-countryCode="NO" {{ old('country_code')== '47' ?'selected':'' }} value="47">Norway
					                        (+47)</option>
					                    <option data-countryCode="OM" {{ old('country_code')== '968' ?'selected':'' }} value="968">Oman
					                        (+968)</option>
					                    <option data-countryCode="PW" {{ old('country_code')== '680' ?'selected':'' }} value="680">Palau
					                        (+680)</option>
					                    <option data-countryCode="PA" {{ old('country_code')== '507' ?'selected':'' }} value="507">Panama
					                        (+507)</option>
					                    <option data-countryCode="PG" {{ old('country_code')== '675' ?'selected':'' }} value="675">Papua New
					                        Guinea (+675)</option>
					                    <option data-countryCode="PY" {{ old('country_code')== '595' ?'selected':'' }} value="595">Paraguay
					                        (+595)</option>
					                    <option data-countryCode="PE" {{ old('country_code')== '51' ?'selected':'' }} value="51">Peru (+51)
					                    </option>
					                    <option data-countryCode="PH" {{ old('country_code')== '63' ?'selected':'' }} value="63">Philippines
					                        (+63)</option>
					                    <option data-countryCode="PL" {{ old('country_code')== '48' ?'selected':'' }} value="48">Poland
					                        (+48)</option>
					                    <option data-countryCode="PT" {{ old('country_code')== '351' ?'selected':'' }} value="351">Portugal
					                        (+351)</option>
					                    <option data-countryCode="PR" {{ old('country_code')== '1787' ?'selected':'' }} value="1787">Puerto
					                        Rico (+1787)</option>
					                    <option data-countryCode="QA" {{ old('country_code')== '974' ?'selected':'' }} value="974">Qatar
					                        (+974)</option>
					                    <option data-countryCode="RE" {{ old('country_code')== '262' ?'selected':'' }} value="262">Reunion
					                        (+262)</option>
					                    <option data-countryCode="RO" {{ old('country_code')== '40' ?'selected':'' }} value="40">Romania
					                        (+40)</option>
					                    <option data-countryCode="RU" {{ old('country_code')== '7' ?'selected':'' }} value="7">Russia (+7)
					                    </option>
					                    <option data-countryCode="RW" {{ old('country_code')== '250' ?'selected':'' }} value="250">Rwanda
					                        (+250)</option>
					                    <option data-countryCode="SM" {{ old('country_code')== '378' ?'selected':'' }} value="378">San
					                        Marino (+378)</option>
					                    <option data-countryCode="ST" {{ old('country_code')== '239' ?'selected':'' }} value="239">Sao Tome
					                        &amp; Principe (+239)</option>
					                    <option data-countryCode="SA" {{ old('country_code')== '966' ?'selected':'' }} value="966">Saudi
					                        Arabia (+966)</option>
					                    <option data-countryCode="SN" {{ old('country_code')== '221' ?'selected':'' }} value="221">Senegal
					                        (+221)</option>
					                    <option data-countryCode="CS" {{ old('country_code')== '381' ?'selected':'' }} value="381">Serbia
					                        (+381)</option>
					                    <option data-countryCode="SC" {{ old('country_code')== '248' ?'selected':'' }} value="248">
					                        Seychelles (+248)</option>
					                    <option data-countryCode="SL" {{ old('country_code')== '232' ?'selected':'' }} value="232">Sierra
					                        Leone (+232)</option>
					                    <option data-countryCode="SG" {{ old('country_code')== '65' ?'selected':'' }} value="65">Singapore
					                        (+65)</option>
					                    <option data-countryCode="SK" {{ old('country_code')== '421' ?'selected':'' }} value="421">Slovak
					                        Republic (+421)</option>
					                    <option data-countryCode="SI" {{ old('country_code')== '386' ?'selected':'' }} value="386">Slovenia
					                        (+386)</option>
					                    <option data-countryCode="SB" {{ old('country_code')== '677' ?'selected':'' }} value="677">Solomon
					                        Islands (+677)</option>
					                    <option data-countryCode="SO" {{ old('country_code')== '252' ?'selected':'' }} value="252">Somalia
					                        (+252)</option>
					                    <option data-countryCode="ZA" {{ old('country_code')== '27' ?'selected':'' }} value="27">South
					                        Africa (+27)</option>
					                    <option data-countryCode="ES" {{ old('country_code')== '34' ?'selected':'' }} value="34">Spain (+34)
					                    </option>
					                    <option data-countryCode="LK" {{ old('country_code')== '94' ?'selected':'' }} value="94">Sri Lanka
					                        (+94)</option>
					                    <option data-countryCode="SH" {{ old('country_code')== '290' ?'selected':'' }} value="290">St.
					                        Helena (+290)</option>
					                    <option data-countryCode="KN" {{ old('country_code')== '1869' ?'selected':'' }} value="1869">St.
					                        Kitts (+1869)</option>
					                    <option data-countryCode="SC" {{ old('country_code')== '1758' ?'selected':'' }} value="1758">St.
					                        Lucia (+1758)</option>
					                    <option data-countryCode="SD" {{ old('country_code')== '249' ?'selected':'' }} value="249">Sudan
					                        (+249)</option>
					                    <option data-countryCode="SR" {{ old('country_code')== '597' ?'selected':'' }} value="597">Suriname
					                        (+597)</option>
					                    <option data-countryCode="SZ" {{ old('country_code')== '268' ?'selected':'' }} value="268">Swaziland
					                        (+268)</option>
					                    <option data-countryCode="SE" {{ old('country_code')== '46' ?'selected':'' }} value="46">Sweden
					                        (+46)</option>
					                    <option data-countryCode="CH" {{ old('country_code')== '41' ?'selected':'' }} value="41">Switzerland
					                        (+41)</option>
					                    <option data-countryCode="SI" {{ old('country_code')== '963' ?'selected':'' }} value="963">Syria
					                        (+963)</option>
					                    <option data-countryCode="TW" {{ old('country_code')== '886' ?'selected':'' }} value="886">Taiwan
					                        (+886)</option>
					                    <option data-countryCode="TJ" {{ old('country_code')== '992' ?'selected':'' }} value="992">Tajikstan
					                        (+992)</option>
					                    <option data-countryCode="TH" {{ old('country_code')== '66' ?'selected':'' }} value="66">Thailand
					                        (+66)</option>
					                    <option data-countryCode="TG" {{ old('country_code')== '228' ?'selected':'' }} value="228">Togo
					                        (+228)</option>
					                    <option data-countryCode="TO" {{ old('country_code')== '676' ?'selected':'' }} value="676">Tonga
					                        (+676)</option>
					                    <option data-countryCode="TT" {{ old('country_code')== '1868' ?'selected':'' }} value="1868">
					                        Trinidad &amp; Tobago (+1868)</option>
					                    <option data-countryCode="TN" {{ old('country_code')== '216' ?'selected':'' }} value="216">Tunisia
					                        (+216)</option>
					                    <option data-countryCode="TR" {{ old('country_code')== '90' ?'selected':'' }} value="90">Turkey
					                        (+90)</option>
					                    <option data-countryCode="TM" {{ old('country_code')== '7' ?'selected':'' }} value="7">Turkmenistan
					                        (+7)</option>
					                    <option data-countryCode="TM" {{ old('country_code')== '993' ?'selected':'' }} value="993">
					                        Turkmenistan (+993)</option>
					                    <option data-countryCode="TC" {{ old('country_code')== '1649' ?'selected':'' }} value="1649">Turks
					                        &amp; Caicos Islands (+1649)</option>
					                    <option data-countryCode="TV" {{ old('country_code')== '688' ?'selected':'' }} value="688">Tuvalu
					                        (+688)</option>
					                    <option data-countryCode="UG" {{ old('country_code')== '256' ?'selected':'' }} value="256">Uganda
					                        (+256)</option>
					                    <option data-countryCode="UA" {{ old('country_code')== '380' ?'selected':'' }} value="380">Ukraine
					                        (+380)</option>
					                    <option data-countryCode="AE" {{ old('country_code')== '971' ?'selected':'' }} value="971">United
					                        Arab Emirates (+971)</option>
					                    <option data-countryCode="UY" {{ old('country_code')== '598' ?'selected':'' }} value="598">Uruguay
					                        (+598)</option>
					                    <option data-countryCode="UZ" {{ old('country_code')== '998' ?'selected':'' }} value="998">
					                        Uzbekistan (+998)</option>
					                    <option data-countryCode="VU" {{ old('country_code')== '678' ?'selected':'' }} value="678">Vanuatu
					                        (+678)</option>
					                    <option data-countryCode="VA" {{ old('country_code')== '379' ?'selected':'' }} value="379">Vatican
					                        City (+379)</option>
					                    <option data-countryCode="VE" {{ old('country_code')== '58' ?'selected':'' }} value="58">Venezuela
					                        (+58)</option>
					                    <option data-countryCode="VN" {{ old('country_code')== '84' ?'selected':'' }} value="84">Vietnam
					                        (+84)</option>
					                    <option data-countryCode="VG" {{ old('country_code')== '84' ?'selected':'' }} value="84">Virgin
					                        Islands - British (+1284)</option>
					                    <option data-countryCode="VI" {{ old('country_code')== '84' ?'selected':'' }} value="84">Virgin
					                        Islands - US (+1340)</option>
					                    <option data-countryCode="WF" {{ old('country_code')== '681' ?'selected':'' }} value="681">Wallis
					                        &amp; Futuna (+681)</option>
					                    <option data-countryCode="YE" {{ old('country_code')== '969' ?'selected':'' }} value="969">Yemen
					                        (North)(+969)</option>
					                    <option data-countryCode="YE" {{ old('country_code')== '967' ?'selected':'' }} value="967">Yemen
					                        (South)(+967)</option>
					                    <option data-countryCode="ZM" {{ old('country_code')== '260' ?'selected':'' }} value="260">Zambia
					                        (+260)</option>
					                    <option data-countryCode="ZW" {{ old('country_code')== '263' ?'selected':'' }} value="263">Zimbabwe
					                        (+263)</option>
					                </select>
								</div>
								<div class="col-lg-7">
									<label class="form-label mt-3">Phone Number <span class="text-danger">*</span></label>
									<input type="text" id="phone_no" class="form-control form-control-lg form-control-solid" name="phone_no" placeholder="Enter here..." value="{{ isset($data->phone_no) ? $data->phone_no : Input::old('phone_no') }}">
								</div>
							</div>
						</div>

						<div class="col-lg-4">
							<label class="form-label mt-3">
								Contact Details <span class="text-danger">*</span>
								<i class="fas fa-exclamation-circle ms-2 fs-7" data-bs-toggle="tooltip" title="" data-bs-original-title="(skype/telegram/etc)" aria-label="(skype/telegram/etc)"></i>
							</label>
							<input type="text" id="skype_id" class="form-control form-control-lg form-control-solid" name="skype_id" placeholder="Enter here..." value="{{ isset($data->skype_id) ? $data->skype_id : Input::old('skype_id') }}">
						</div>

						<div class="col-lg-4">
							<label class="form-label mt-3">Country Of Incorporation <span class="text-danger">*</span></label>
							{!! Form::select('country', [''=>'Select']+getCountry(), [isset($data->country) ? $data->country : null], array('class' => 'form-select', 'data-control'=>'select2', 'data-placeholder' => 'Select an option','id'=>'country','data-width'=>'100%')) !!}
						</div>

						<div class="col-lg-4">
							
							@if($isEdit)
						    <input type="hidden" class="oldProccessingCountryVal" value="{{  $data->processing_country }}" />
						    @else
						    <input type="hidden" class="oldProccessingCountryVal" value="{{  old('processing_country[]') }}" />
						    @endif
							<label class="form-label mt-3">Processing Country <span class="text-danger">*</span></label>
							{!! Form::select('processing_country[]', ['UK'=>'UK','EU'=>'EU','US/CANADA'=>'US/CANADA','Others'=>'Others'], isset($data->processing_country) ? json_decode($data->processing_country) : [], array('id' => 'processing_country','class' => 'form-select processing_country', 'data-control'=>'select2', 'multiple' => 'multiple', 'data-placeholder' =>'Select an option', 'onchange' => 'getProcessingCountry(this)')) !!}

							<div class="form-group mt-2 d-none otherProcessingInput">
						        <label for="processing_country">Other Processing Country<span class="text-danger">*</span></label>
						        <div class="input-div">
						            <input type="text" class="form-control form-control-lg form-control-solid otherProcessingInputBox" placeholder="Enter here.." name="other_processing_country" @if($isEdit) value="{{ $data->other_processing_country }}" @else value="{{ old('other_processing_country') }}" @endif />
						        </div>
						    </div>
						</div>

						<div class="col-lg-4">
							<label class="form-label mt-3">Processing Currency <span class="text-danger">*</span></label>
							{!! Form::select('processing_currency[]', config('currency.three_letter'), isset($data->processing_currency) ? json_decode($data->processing_currency) : [], array('id' => 'processing_currency','class' => 'form-select','data-control'=>'select2', 'multiple' => 'multiple', 'data-placeholder' =>'Select an option')) !!}
						</div>

						<div class="col-lg-4">
							<label class="form-label mt-3">Industry Type <span class="text-danger">*</span></label>

							@if ($isEdit)
						    <input type="hidden" class="oldIndustryType" value="{{ $data->category_id }}" />
						    @else
						    <input type="hidden" class="oldIndustryType" value="{{ old('category_id') }}" />
						    @endif

						    <div class="input-div">
						        <select name="category_id" id="category_id" class="form-select" data-width="100%" data-control="select2" data-placeholder="Select an option"
						            onchange="otherIndustryType(this.value)">
						            <option value="" selected disabled>Select</option>
						            @foreach ($category as $key => $value)
						            <option value="{{ $key }}" {{ old('category_id')== $key?'selected':'' }}
						                {{ isset($data->category_id) ? $data->category_id == $key ? 'selected' : '' : '' }}>
						                {{ $value }}
						            </option>
						            @endforeach
						        </select>
						    </div>

						    <div class="form-group mt-2 d-none showOtherIndustryInput">
						        <label for="category_id">Miscellaneous<span class="text-danger">*</span></label>
						        <input type="text" class="form-control form-control-lg form-control-solid showOtherIndustryInputBox" placeholder="Enter here.."
						            name="other_industry_type" @if($isEdit) value="{{ $data->other_industry_type }}" @else
						            value="{{ old('other_industry_type') }}" @endif />
						    </div>
						</div>

						<div class="col-lg-4">
							<label class="form-label mt-3">Integration Preference <span class="text-danger">*</span></label>
							{!! Form::select('technology_partner_id[]', $technologypartners, isset($data->technology_partner_id) ? json_decode($data->technology_partner_id) : [], array('id' => 'technology_partner_id','class' => 'form-select','data-control'=>'select2', 'multiple' => 'multiple', 'data-placeholder' =>'Select an option')) !!}
						</div>

						<div class="col-lg-4">
							<label class="form-label mt-3">Monthly Volume <span class="text-danger">*</span></label>

							<div class="row">
					            <div class="col-lg-4 pr-0">
					                {!! Form::select('monthly_volume_currency', ['USD'=>'USD','GBP'=>'GBP','EUR'=>'EUR','Others'=>'Others'], isset($data->monthly_volume_currency) ?
					                $data->monthly_volume_currency : '', array('id' => 'monthly_volume_currency','class' =>
					                'form-select','data-control'=>'select2')) !!}
					            </div>
					            <div class="col-lg-8">
					                <input type="number" name="monthly_volume" class="form-control form-control-lg form-control-solid" placeholder="Enter here..." value="{{ isset($data->monthly_volume) ? $data->monthly_volume : Input::old('monthly_volume') }}" />
					            </div>
					        </div>
						</div>

						<div class="col-lg-4">
							<label class="form-label mt-3">Licence Status <span class="text-danger">*</span></label>

							@if ($isEdit)
					        <input type="hidden" class="company_licenseOldValue"
					            value="{{ old('company_license') == '0' ? old('company_license'):  $data->company_license }}" />
					        @else
					        <input type="hidden" class="company_licenseOldValue" value="{{ old('company_license') }}" />
					        @endif
					        <select name="company_license" id="company_license" class="form-select" data-width="100%" data-control="select2" data-placeholder="Select an option"
					            onchange="getLicenseStatus(this.value)">
					            <option value="" selected disabled>Select</option>
					            <option value="0" {{ isset($data->company_license) ? $data->company_license == 0 ? 'selected' : '' : ''}}
					                {{ old('company_license')== '0' ?'selected':'' }}>Yes</option>
					            <option value="1" {{ isset($data->company_license) ? $data->company_license == 1 ? 'selected' : '' : ''}}
					                {{ old('company_license')== '1' ?'selected':'' }}>No</option>
					            <option value="2" {{ isset($data->company_license) ? $data->company_license == 2 ? 'selected' : '' : ''}}
					                {{ old('company_license')== '2' ?'selected':'' }}>NA</option>
					        </select>
						</div>

					</div>
				</div>
				<!--end::Input group-->
			</div>
			<!--end::Wrapper-->
		</div>
		<!--end::Step 2-->
		<!--begin::Step 3-->
		<div data-kt-stepper-element="content">
			<!--begin::Wrapper-->
			<div class="w-100">
				<!--begin::Heading-->
				<div class="pb-10 pb-lg-12">
					<!--begin::Title-->
					<h2 class="fw-bolder text-dark">Business Details</h2>
					<!--end::Title-->
					<!--begin::Notice-->
					<div class="text-muted fw-bold fs-6">If you need more info, please check out
					<a href="#" class="link-primary fw-bolder">Help Page</a>.</div>
					<!--end::Notice-->
				</div>
				<!--end::Heading-->
				<!--begin::Input group-->
				<div class="fv-row mb-10">
					<!--begin::Label-->
					<label class="form-label required">Business Name</label>
					<!--end::Label-->
					<!--begin::Input-->
					<input name="business_name" class="form-control form-control-lg form-control-solid" value="Keenthemes Inc." />
					<!--end::Input-->
				</div>
				<!--end::Input group-->
				<!--begin::Input group-->
				<div class="fv-row mb-10">
					<!--begin::Label-->
					<label class="d-flex align-items-center form-label">
						<span class="required">Shortened Descriptor</span>
						<i class="fas fa-exclamation-circle ms-2 fs-7" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-content="&lt;div class='p-4 rounded bg-light'&gt; &lt;div class='d-flex flex-stack text-muted mb-4'&gt; &lt;i class='fas fa-university fs-3 me-3'&gt;&lt;/i&gt; &lt;div class='fw-bold'&gt;INCBANK **** 1245 STATEMENT&lt;/div&gt; &lt;/div&gt; &lt;div class='d-flex flex-stack fw-bold text-gray-600'&gt; &lt;div&gt;Amount&lt;/div&gt; &lt;div&gt;Transaction&lt;/div&gt; &lt;/div&gt; &lt;div class='separator separator-dashed my-2'&gt;&lt;/div&gt; &lt;div class='d-flex flex-stack text-dark fw-bolder mb-2'&gt; &lt;div&gt;USD345.00&lt;/div&gt; &lt;div&gt;KEENTHEMES*&lt;/div&gt; &lt;/div&gt; &lt;div class='d-flex flex-stack text-muted mb-2'&gt; &lt;div&gt;USD75.00&lt;/div&gt; &lt;div&gt;Hosting fee&lt;/div&gt; &lt;/div&gt; &lt;div class='d-flex flex-stack text-muted'&gt; &lt;div&gt;USD3,950.00&lt;/div&gt; &lt;div&gt;Payrol&lt;/div&gt; &lt;/div&gt; &lt;/div&gt;"></i>
					</label>
					<!--end::Label-->
					<!--begin::Input-->
					<input name="business_descriptor" class="form-control form-control-lg form-control-solid" value="KEENTHEMES" />
					<!--end::Input-->
					<!--begin::Hint-->
					<div class="form-text">Customers will see this shortened version of your statement descriptor</div>
					<!--end::Hint-->
				</div>
				<!--end::Input group-->
				<!--begin::Input group-->
				<div class="fv-row mb-10">
					<!--begin::Label-->
					<label class="form-label required">Corporation Type</label>
					<!--end::Label-->
					<!--begin::Input-->
					<select name="business_type" class="form-select form-select-lg form-select-solid" data-control="select2" data-placeholder="Select..." data-allow-clear="true" data-hide-search="true">
						<option></option>
						<option value="1">S Corporation</option>
						<option value="1">C Corporation</option>
						<option value="2">Sole Proprietorship</option>
						<option value="3">Non-profit</option>
						<option value="4">Limited Liability</option>
						<option value="5">General Partnership</option>
					</select>
					<!--end::Input-->
				</div>
				<!--end::Input group-->
				<!--begin::Input group-->
				<div class="fv-row mb-10">
					<!--end::Label-->
					<label class="form-label">Business Description</label>
					<!--end::Label-->
					<!--begin::Input-->
					<textarea name="business_description" class="form-control form-control-lg form-control-solid" rows="3"></textarea>
					<!--end::Input-->
				</div>
				<!--end::Input group-->
				<!--begin::Input group-->
				<div class="fv-row mb-0">
					<!--begin::Label-->
					<label class="fs-6 fw-bold form-label required">Contact Email</label>
					<!--end::Label-->
					<!--begin::Input-->
					<input name="business_email" class="form-control form-control-lg form-control-solid" value="corp@support.com" />
					<!--end::Input-->
				</div>
				<!--end::Input group-->
			</div>
			<!--end::Wrapper-->
		</div>
		<!--end::Step 3-->
		<!--begin::Step 4-->
		<div data-kt-stepper-element="content">
			<!--begin::Wrapper-->
			<div class="w-100">
				<!--begin::Heading-->
				<div class="pb-10 pb-lg-15">
					<!--begin::Title-->
					<h2 class="fw-bolder text-dark">Billing Details</h2>
					<!--end::Title-->
					<!--begin::Notice-->
					<div class="text-muted fw-bold fs-6">If you need more info, please check out
					<a href="#" class="text-primary fw-bolder">Help Page</a>.</div>
					<!--end::Notice-->
				</div>
				<!--end::Heading-->
				<!--begin::Input group-->
				<div class="d-flex flex-column mb-7 fv-row">
					<!--begin::Label-->
					<label class="d-flex align-items-center fs-6 fw-bold form-label mb-2">
						<span class="required">Name On Card</span>
						<i class="fas fa-exclamation-circle ms-2 fs-7" data-bs-toggle="tooltip" title="Specify a card holder's name"></i>
					</label>
					<!--end::Label-->
					<input type="text" class="form-control form-control-solid" placeholder="" name="card_name" value="Max Doe" />
				</div>
				<!--end::Input group-->
				<!--begin::Input group-->
				<div class="d-flex flex-column mb-7 fv-row">
					<!--begin::Label-->
					<label class="required fs-6 fw-bold form-label mb-2">Card Number</label>
					<!--end::Label-->
					<!--begin::Input wrapper-->
					<div class="position-relative">
						<!--begin::Input-->
						<input type="text" class="form-control form-control-solid" placeholder="Enter card number" name="card_number" value="4111 1111 1111 1111" />
						<!--end::Input-->
						<!--begin::Card logos-->
						<div class="position-absolute translate-middle-y top-50 end-0 me-5">
							<img src="assets/media/svg/card-logos/visa.svg" alt="" class="h-25px" />
							<img src="assets/media/svg/card-logos/mastercard.svg" alt="" class="h-25px" />
							<img src="assets/media/svg/card-logos/american-express.svg" alt="" class="h-25px" />
						</div>
						<!--end::Card logos-->
					</div>
					<!--end::Input wrapper-->
				</div>
				<!--end::Input group-->
				<!--begin::Input group-->
				<div class="row mb-10">
					<!--begin::Col-->
					<div class="col-md-8 fv-row">
						<!--begin::Label-->
						<label class="required fs-6 fw-bold form-label mb-2">Expiration Date</label>
						<!--end::Label-->
						<!--begin::Row-->
						<div class="row fv-row">
							<!--begin::Col-->
							<div class="col-6">
								<select name="card_expiry_month" class="form-select form-select-solid" data-control="select2" data-hide-search="true" data-placeholder="Month">
									<option></option>
									<option value="1">1</option>
									<option value="2">2</option>
									<option value="3">3</option>
									<option value="4">4</option>
									<option value="5">5</option>
									<option value="6">6</option>
									<option value="7">7</option>
									<option value="8">8</option>
									<option value="9">9</option>
									<option value="10">10</option>
									<option value="11">11</option>
									<option value="12">12</option>
								</select>
							</div>
							<!--end::Col-->
							<!--begin::Col-->
							<div class="col-6">
								<select name="card_expiry_year" class="form-select form-select-solid" data-control="select2" data-hide-search="true" data-placeholder="Year">
									<option></option>
									<option value="2021">2021</option>
									<option value="2022">2022</option>
									<option value="2023">2023</option>
									<option value="2024">2024</option>
									<option value="2025">2025</option>
									<option value="2026">2026</option>
									<option value="2027">2027</option>
									<option value="2028">2028</option>
									<option value="2029">2029</option>
									<option value="2030">2030</option>
									<option value="2031">2031</option>
								</select>
							</div>
							<!--end::Col-->
						</div>
						<!--end::Row-->
					</div>
					<!--end::Col-->
					<!--begin::Col-->
					<div class="col-md-4 fv-row">
						<!--begin::Label-->
						<label class="d-flex align-items-center fs-6 fw-bold form-label mb-2">
							<span class="required">CVV</span>
							<i class="fas fa-exclamation-circle ms-2 fs-7" data-bs-toggle="tooltip" title="Enter a card CVV code"></i>
						</label>
						<!--end::Label-->
						<!--begin::Input wrapper-->
						<div class="position-relative">
							<!--begin::Input-->
							<input type="text" class="form-control form-control-solid" minlength="3" maxlength="4" placeholder="CVV" name="card_cvv" />
							<!--end::Input-->
							<!--begin::CVV icon-->
							<div class="position-absolute translate-middle-y top-50 end-0 me-3">
								<!--begin::Svg Icon | path: icons/duotune/finance/fin002.svg-->
								<span class="svg-icon svg-icon-2hx">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
										<path d="M22 7H2V11H22V7Z" fill="black" />
										<path opacity="0.3" d="M21 19H3C2.4 19 2 18.6 2 18V6C2 5.4 2.4 5 3 5H21C21.6 5 22 5.4 22 6V18C22 18.6 21.6 19 21 19ZM14 14C14 13.4 13.6 13 13 13H5C4.4 13 4 13.4 4 14C4 14.6 4.4 15 5 15H13C13.6 15 14 14.6 14 14ZM16 15.5C16 16.3 16.7 17 17.5 17H18.5C19.3 17 20 16.3 20 15.5C20 14.7 19.3 14 18.5 14H17.5C16.7 14 16 14.7 16 15.5Z" fill="black" />
									</svg>
								</span>
								<!--end::Svg Icon-->
							</div>
							<!--end::CVV icon-->
						</div>
						<!--end::Input wrapper-->
					</div>
					<!--end::Col-->
				</div>
				<!--end::Input group-->
				<!--begin::Input group-->
				<div class="d-flex flex-stack">
					<!--begin::Label-->
					<div class="me-5">
						<label class="fs-6 fw-bold form-label">Save Card for further billing?</label>
						<div class="fs-7 fw-bold text-muted">If you need more info, please check budget planning</div>
					</div>
					<!--end::Label-->
					<!--begin::Switch-->
					<label class="form-check form-switch form-check-custom form-check-solid">
						<input class="form-check-input" type="checkbox" value="1" checked="checked" />
						<span class="form-check-label fw-bold text-muted">Save Card</span>
					</label>
					<!--end::Switch-->
				</div>
				<!--end::Input group-->
			</div>
			<!--end::Wrapper-->
		</div>
		<!--end::Step 4-->
		<!--begin::Step 5-->
		<div data-kt-stepper-element="content">
			<!--begin::Wrapper-->
			<div class="w-100">
				<!--begin::Heading-->
				<div class="pb-8 pb-lg-10">
					<!--begin::Title-->
					<h2 class="fw-bolder text-dark">Your Are Done!</h2>
					<!--end::Title-->
					<!--begin::Notice-->
					<div class="text-muted fw-bold fs-6">If you need more info, please
					<a href="../../demo12/dist/authentication/sign-in/basic.html" class="link-primary fw-bolder">Sign In</a>.</div>
					<!--end::Notice-->
				</div>
				<!--end::Heading-->
				<!--begin::Body-->
				<div class="mb-0">
					<!--begin::Text-->
					<div class="fs-6 text-gray-600 mb-5">Writing headlines for blog posts is as much an art as it is a science and probably warrants its own post, but for all advise is with what works for your great &amp; amazing audience.</div>
					<!--end::Text-->
					<!--begin::Alert-->
					<!--begin::Notice-->
					<div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6">
						<!--begin::Icon-->
						<!--begin::Svg Icon | path: icons/duotune/general/gen044.svg-->
						<span class="svg-icon svg-icon-2tx svg-icon-warning me-4">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
								<rect opacity="0.3" x="2" y="2" width="20" height="20" rx="10" fill="black" />
								<rect x="11" y="14" width="7" height="2" rx="1" transform="rotate(-90 11 14)" fill="black" />
								<rect x="11" y="17" width="2" height="2" rx="1" transform="rotate(-90 11 17)" fill="black" />
							</svg>
						</span>
						<!--end::Svg Icon-->
						<!--end::Icon-->
						<!--begin::Wrapper-->
						<div class="d-flex flex-stack flex-grow-1">
							<!--begin::Content-->
							<div class="fw-bold">
								<h4 class="text-gray-900 fw-bolder">We need your attention!</h4>
								<div class="fs-6 text-gray-700">To start using great tools, please, please
								<a href="#" class="fw-bolder">Create Team Platform</a></div>
							</div>
							<!--end::Content-->
						</div>
						<!--end::Wrapper-->
					</div>
					<!--end::Notice-->
					<!--end::Alert-->
				</div>
				<!--end::Body-->
			</div>
			<!--end::Wrapper-->
		</div>
		<!--end::Step 5-->
		<!--begin::Actions-->
		<div class="d-flex flex-stack pt-15">
			<!--begin::Wrapper-->
			<div class="mr-2">
				<button type="button" class="btn btn-lg btn-light-primary me-3" data-kt-stepper-action="previous">
				<!--begin::Svg Icon | path: icons/duotune/arrows/arr063.svg-->
				<span class="svg-icon svg-icon-4 me-1">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
						<rect opacity="0.5" x="6" y="11" width="13" height="2" rx="1" fill="black" />
						<path d="M8.56569 11.4343L12.75 7.25C13.1642 6.83579 13.1642 6.16421 12.75 5.75C12.3358 5.33579 11.6642 5.33579 11.25 5.75L5.70711 11.2929C5.31658 11.6834 5.31658 12.3166 5.70711 12.7071L11.25 18.25C11.6642 18.6642 12.3358 18.6642 12.75 18.25C13.1642 17.8358 13.1642 17.1642 12.75 16.75L8.56569 12.5657C8.25327 12.2533 8.25327 11.7467 8.56569 11.4343Z" fill="black" />
					</svg>
				</span>
				<!--end::Svg Icon-->Back</button>
			</div>
			<!--end::Wrapper-->
			<!--begin::Wrapper-->
			<div>
				<button type="button" class="btn btn-lg btn-primary me-3" data-kt-stepper-action="submit">
					<span class="indicator-label">Submit
					<!--begin::Svg Icon | path: icons/duotune/arrows/arr064.svg-->
					<span class="svg-icon svg-icon-3 ms-2 me-0">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
							<rect opacity="0.5" x="18" y="13" width="13" height="2" rx="1" transform="rotate(-180 18 13)" fill="black" />
							<path d="M15.4343 12.5657L11.25 16.75C10.8358 17.1642 10.8358 17.8358 11.25 18.25C11.6642 18.6642 12.3358 18.6642 12.75 18.25L18.2929 12.7071C18.6834 12.3166 18.6834 11.6834 18.2929 11.2929L12.75 5.75C12.3358 5.33579 11.6642 5.33579 11.25 5.75C10.8358 6.16421 10.8358 6.83579 11.25 7.25L15.4343 11.4343C15.7467 11.7467 15.7467 12.2533 15.4343 12.5657Z" fill="black" />
						</svg>
					</span>
					<!--end::Svg Icon--></span>
					<span class="indicator-progress">Please wait...
					<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
				</button>
				<button type="button" class="btn btn-lg btn-primary" data-kt-stepper-action="next">Continue
				<!--begin::Svg Icon | path: icons/duotune/arrows/arr064.svg-->
				<span class="svg-icon svg-icon-4 ms-1 me-0">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
						<rect opacity="0.5" x="18" y="13" width="13" height="2" rx="1" transform="rotate(-180 18 13)" fill="black" />
						<path d="M15.4343 12.5657L11.25 16.75C10.8358 17.1642 10.8358 17.8358 11.25 18.25C11.6642 18.6642 12.3358 18.6642 12.75 18.25L18.2929 12.7071C18.6834 12.3166 18.6834 11.6834 18.2929 11.2929L12.75 5.75C12.3358 5.33579 11.6642 5.33579 11.25 5.75C10.8358 6.16421 10.8358 6.83579 11.25 7.25L15.4343 11.4343C15.7467 11.7467 15.7467 12.2533 15.4343 12.5657Z" fill="black" />
					</svg>
				</span>
				<!--end::Svg Icon--></button>
			</div>
			<!--end::Wrapper-->
		</div>
		<!--end::Actions-->
	</form>
	<!--end::Form-->
</div>