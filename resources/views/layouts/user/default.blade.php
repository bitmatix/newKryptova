<!DOCTYPE html>
<html lang="en">
    <!--begin::Head-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>{{ config('app.name') }} | Merchant @yield('title')</title>

        <link rel="shortcut icon" href="{{ storage_asset('theme/assets/media/logos/favicon.ico') }}" />
        <!--begin::Fonts-->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
        <!--end::Fonts-->
        <!--begin::Page Vendor Stylesheets(used by this page)-->
        <link href="{{ storage_asset('theme/assets/plugins/custom/fullcalendar/fullcalendar.bundle.css') }}" rel="stylesheet" type="text/css" />
        <!--end::Page Vendor Stylesheets-->
        <!--begin::Global Stylesheets Bundle(used by all pages)-->
        <link href="{{ storage_asset('theme/assets/plugins/global/plugins.dark.bundle.rtl.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ storage_asset('theme/assets/css/style.dark.bundle.rtl.css') }}" rel="stylesheet" type="text/css" />
        <!--end::Global Stylesheets Bundle-->

        @yield('customeStyle')
        <script type="text/javascript">
            var DATE = "{{ date('d-m-Y') }}";
            var current_page_url = "<?php echo URL::current(); ?>";
            var current_page_fullurl = "<?php echo URL::full(); ?>";
            var CSRF_TOKEN = "{{ csrf_token() }}";
        </script>
        
        <script>
            var clicky_site_ids = clicky_site_ids || [];
            clicky_site_ids.push(101164380);
        </script>
        <script async src="//static.getclicky.com/js"></script>
    </head>
    <!--end::Head-->
    <!--begin::Body-->
    <body id="kt_body" class="dark-mode page-bg header-fixed header-tablet-and-mobile-fixed aside-enabled">

        <?php
        $currentPageURL = URL::current();
        $pageArray = explode('/', $currentPageURL);
        $pageActive = isset($pageArray[3]) ? $pageArray[3] : 'dashboardPage';
        if (\Auth::check()) {
            if(\Auth::user()->main_user_id != '0')
                $userID = \Auth::user()->main_user_id;
            else
                $userID = \Auth::user()->id;

            $count_notifications = count(getNotifications($userID, 'user', 5));
        }
        ?>
        <!--begin::Main-->
        <!--begin::Root-->
        <div class="d-flex flex-column flex-root">
            <!--begin::Page-->
            <div class="page d-flex flex-row flex-column-fluid">
                <!--begin::Wrapper-->
                <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
                    <!--begin::Header-->
                    <div id="kt_header" class="header align-items-stretch" data-kt-sticky="true" data-kt-sticky-name="header" data-kt-sticky-offset="{default: '200px', lg: '300px'}">
                        <!--begin::Container-->
                        <div class="header-container container-xxl d-flex align-items-center">
                            @include('layouts.user.header')
                        </div>
                        <!--end::Container-->
                    </div>
                    <!--end::Header-->
                    <!--begin::Container-->
                    <div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
                        <!--begin::Aside-->
                        <div id="kt_aside" class="aside" data-kt-drawer="true" data-kt-drawer-name="aside" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="auto" data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_aside_toggle">
                            <!--begin::Search-->
                            @include('layouts.user.search')
                            <!--end::Search-->
                            <!--begin::Menu-->
                            <div class="aside-menu flex-column-fluid pt-0 pb-5 py-lg-5" id="kt_aside_menu">
                                <!--begin::Aside menu-->
                                @include('layouts.user.sidebar')
                                <!--end::Aside menu-->
                            </div>
                            <!--end::Menu-->
                            <!--begin::Footer-->
                            <div class="aside-footer flex-column-auto pb-5 pb-lg-13" id="kt_aside_footer">
                                <!--begin::Menu-->
                                @include('layouts.user.sidefooter')
                                <!--end::Menu-->
                            </div>
                            <!--end::Footer-->
                        </div>
                        <!--end::Aside-->
                        <!--begin::Post-->
                        <div class="content flex-row-fluid" id="kt_content">
                            @yield('content')
                        </div>
                        <!--end::Post-->
                    </div>
                    <!--end::Container-->
                    <!--begin::Footer-->
                    @include('layouts.user.footer')
                    <!--end::Footer-->
                </div>
                <!--end::Wrapper-->
            </div>
            <!--end::Page-->
        </div>
        <!--end::Root-->

        @if(auth()->user()->is_rate_sent == 1)
        <button type="button" data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg" id="is_rate" style="display: none;"></button>
        <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Fee Schedule</h2>

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
                    </div>
                    <div class="modal-body py-lg-10 px-lg-10">
                        <p><strong>Congratulations.!</strong></p>
                        <p>
                        Your account has been 'Approved' with the below mentioned rates. <br>Click 'Accept' to proceed.
                        </p>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table">
                                    <tr class="border-bottom border-gray-300 border-bottom-dashed">
                                        <td><b> Visa -</b> Merchant Discount Rate (%)</td>
                                        <td>{{ \auth::user()->merchant_discount_rate }}</td>
                                    </tr>
                                    <tr><td colspan="2" style="padding: 5px 0px;"></td></tr>
                                    <tr class="border-bottom border-gray-300 border-bottom-dashed">
                                        <td><b> Visa -</b> Setup Fee</td>
                                        <td>{{ \auth::user()->setup_fee }}</td>
                                    </tr>
                                    <tr><td colspan="2" style="padding: 5px 0px;"></td></tr>
                                    <tr class="border-bottom border-gray-300 border-bottom-dashed">
                                        <td>Transaction Fee</td>
                                        <td>{{ \auth::user()->transaction_fee }}</td>
                                    </tr>
                                    <tr><td colspan="2" style="padding: 5px 0px;"></td></tr>
                                    <tr class="border-bottom border-gray-300 border-bottom-dashed">
                                        <td>Chargeback Fee</td>
                                        <td>{{ \auth::user()->chargeback_fee }}</td>
                                    </tr>
                                    <tr><td colspan="2" style="padding: 5px 0px;"></td></tr>
                                    <tr class="border-bottom border-gray-300 border-bottom-dashed">
                                        <td>Retrieval Fee</td>
                                        <td>{{ \auth::user()->retrieval_fee }}</td>
                                    </tr>
                                    <tr><td colspan="2" style="padding: 0px;"></td></tr>
                                </table>
                                <table class="table">
                                    <tr class="border-bottom border-gray-300 border-bottom-dashed">
                                        <td>Payment Frequency</td>
                                        <td>{{ config('custom.payment_frequency') }}</td>
                                    </tr>
                                    <tr><td colspan="2" style="padding: 0px;"></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table">
                                    <tr class="border-bottom border-gray-300 border-bottom-dashed">
                                        <td><b> Master -</b> Merchant Discount Rate (%)</td>
                                        <td>{{ \auth::user()->merchant_discount_rate_master_card }}</td>
                                    </tr>
                                    <tr><td colspan="2" style="padding: 5px 0px;"></td></tr>
                                    <tr class="border-bottom border-gray-300 border-bottom-dashed">
                                        <td><b> Master -</b> Setup Fee</td>
                                        <td>{{ \auth::user()->setup_fee_master_card }}</td>
                                    </tr>
                                    <tr><td colspan="2" style="padding: 5px 0px;"></td></tr>
                                    <tr class="border-bottom border-gray-300 border-bottom-dashed">
                                        <td>Rolling Reserve (%)</td>
                                        <td>{{ \auth::user()->rolling_reserve_paercentage }}</td>
                                    </tr>
                                    <tr><td colspan="2" style="padding: 5px 0px;"></td></tr>
                                    <tr class="border-bottom border-gray-300 border-bottom-dashed">
                                        <td>Refund Fee</td>
                                        <td>{{ \auth::user()->refund_fee }}</td>
                                    </tr>
                                    <tr><td colspan="2" style="padding: 5px 0px;"></td></tr>
                                    <tr class="border-bottom border-gray-300 border-bottom-dashed">
                                        <td>Suspicious Transaction Fee</td>
                                        <td>{{ \auth::user()->flagged_fee }}</td>
                                    </tr>
                                    <tr><td colspan="2" style="padding: 5px 0px;"></td></tr>
                                    <tr class="border-bottom border-gray-300 border-bottom-dashed">
                                        <td>Minimum Settlement Amount</td>
                                        <td>{{ config('custom.minimum_settlement_amount') }}</td>
                                    </tr>
                                    <tr><td colspan="2" style="padding: 0px;"></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-success rateAgree btn-sm" data-id="2">Accept</button>
                        <button type="button" class="btn btn-danger rateAgree btn-sm" data-id="3">Decline</button>
                    </div>
                </div>
            </div>
        </div>

        <button type="button" data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg1" id="is_rate_reason" style="display: none;"></button>
        <div class="modal fade bd-example-modal-lg1" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Decline Reason</h2>

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
                    </div>
                    <div class="modal-body py-lg-10 px-lg-10">
                        <textarea id="reclineReason" rows="3" placeholder="Enter here" class="form-control form-control-solid mb-8" name="reclineReason"></textarea>
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-danger rateAgreeReason btn-sm">Decline</button>
                        <button type="button" class="btn btn-warning rateAgreeReasonBack btn-sm">Back</button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!--begin::Scrolltop-->
        <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
            <!--begin::Svg Icon | path: icons/duotune/arrows/arr066.svg-->
            <span class="svg-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <rect opacity="0.5" x="13" y="6" width="13" height="2" rx="1" transform="rotate(90 13 6)" fill="black" />
                    <path d="M12.5657 8.56569L16.75 12.75C17.1642 13.1642 17.8358 13.1642 18.25 12.75C18.6642 12.3358 18.6642 11.6642 18.25 11.25L12.7071 5.70711C12.3166 5.31658 11.6834 5.31658 11.2929 5.70711L5.75 11.25C5.33579 11.6642 5.33579 12.3358 5.75 12.75C6.16421 13.1642 6.83579 13.1642 7.25 12.75L11.4343 8.56569C11.7467 8.25327 12.2533 8.25327 12.5657 8.56569Z" fill="black" />
                </svg>
            </span>
            <!--end::Svg Icon-->
        </div>
        <!--end::Scrolltop-->
        <!--end::Main-->
        <script>var hostUrl = "<?php echo storage_asset('theme/assets/'); ?>";</script>
        <!--begin::Javascript-->
        <!--begin::Global Javascript Bundle(used by all pages)-->
        <script src="{{ storage_asset('theme/assets/plugins/global/plugins.bundle.js') }}"></script>
        <script src="{{ storage_asset('theme/assets/js/scripts.bundle.js') }}"></script>
        <!--end::Global Javascript Bundle-->
        <!--begin::Page Vendors Javascript(used by this page)-->
        <script src="{{ storage_asset('theme/assets/plugins/custom/fullcalendar/fullcalendar.bundle.js') }}"></script>
        <!--end::Page Vendors Javascript-->
        <!--begin::Page Custom Javascript(used by this page)-->
        <script src="{{ storage_asset('theme/assets/js/custom/widgets.js') }}"></script>
        <script src="{{ storage_asset('theme/assets/js/custom/custom.js') }}"></script>
        <!--end::Page Custom Javascript-->
        <!--end::Javascript-->

        <script>
            window.hostname = '{{env("LARAVEL_ECHO_HOST")}}';
            window.laravel_echo_port = '{{env("LARAVEL_ECHO_PORT")}}';
            window.user_id = {{auth()->user()->id}};
            window.user_type='user';
        </script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.2.0/socket.io.js"></script>

        @if(auth()->user()->is_rate_sent == 1)
            <script type="text/javascript">
                $(document).ready(function(){
                    $("#is_rate").trigger("click");
                });
            </script>
        @endif
        
        @include('layouts.user.alert')
        @include('layouts.user.deleteModal')

        @yield('customScript')
    </body>
    <!--end::Body-->
</html>