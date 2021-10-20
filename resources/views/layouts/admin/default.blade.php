<!DOCTYPE html>
<html lang="en">
    <!--begin::Head-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>{{ config('app.name') }} | Admin @yield('title')</title>

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

        <link href="{{ storage_asset('theme/assets/css/custom.css') }}" rel="stylesheet" type="text/css" />

        @yield('customeStyle')
        <?php
        $currentPageURL = URL::current();
        $pageArray = explode('/', $currentPageURL);
        $pageActive = isset($pageArray[4]) ? $pageArray[4] : 'dashboard';
        $count_notifications = count(getNotificationsForAdmin());
        ?>
    </head>
    <!--end::Head-->
    <!--begin::Body-->
    <body id="kt_body" class="dark-mode page-bg header-fixed header-tablet-and-mobile-fixed aside-enabled">
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
                            @include('layouts.admin.header')
                        </div>
                        <!--end::Container-->
                    </div>
                    <!--end::Header-->
                    <!--begin::Container-->
                    <div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
                        <!--begin::Aside-->
                        <div id="kt_aside" class="aside" data-kt-drawer="true" data-kt-drawer-name="aside" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="auto" data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_aside_toggle">
                            <!--begin::Search-->
                            @include('layouts.admin.search')
                            <!--end::Search-->
                            <!--begin::Menu-->
                            <div class="aside-menu flex-column-fluid pt-0 pb-5 py-lg-5" id="kt_aside_menu">
                                <!--begin::Aside menu-->
                                @include('layouts.admin.sidebar')
                                <!--end::Aside menu-->
                            </div>
                            <!--end::Menu-->
                            <!--begin::Footer-->
                            <div class="aside-footer flex-column-auto pb-5 pb-lg-13" id="kt_aside_footer">
                                <!--begin::Menu-->
                                @include('layouts.admin.sidefooter')
                                <!--end::Menu-->
                            </div>
                            <!--end::Footer-->
                        </div>
                        <!--end::Aside-->
                        <!--begin::Post-->
                        <div class="content flex-row-fluid" id="kt_content">
                            <!--begin::Row-->
                            <div class="row gy-5 g-xl-8 d-flex align-items-center mt-lg-0 mb-10 mb-lg-15">
                                <!--begin::Col-->
                                <div class="col-xl-6 d-flex align-items-center">
                                    <h1 class="fs-2hx">
                                        Hi {{ucwords(\Session::get('user_name'))}},<br>
                                        Welcome To {{ config('app.name') }} <img src="{{ storage_asset('theme/assets/media/smiles/happy.png') }}">
                                    </h1>
                                </div>
                                <!--end::Col-->
                                <!--begin::Col-->
                                <div class="col-xl-6">
                                    <div class="d-flex flex-stack ps-lg-20">
                                        <a href="#" class="btn btn-icon btn-outline btn-nav active h-50px w-50px h-lg-70px w-lg-70px ms-2" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="bottom" data-bs-original-title="Dashboard">
                                            <!--begin::Svg Icon | path: icons/duotune/abstract/abs038.svg-->
                                            <span class="svg-icon svg-icon-1 svg-icon-lg-2hx">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                    <rect x="2" y="2" width="9" height="9" rx="2" fill="black" />
                                                    <rect opacity="0.3" x="13" y="2" width="9" height="9" rx="2" fill="black" />
                                                    <rect opacity="0.3" x="13" y="13" width="9" height="9" rx="2" fill="black" />
                                                    <rect opacity="0.3" x="2" y="13" width="9" height="9" rx="2" fill="black" />
                                                </svg>
                                            </span>
                                            <!--end::Svg Icon-->
                                        </a>
                                        <a href="#" class="btn btn-icon btn-outline btn-nav h-50px w-50px h-lg-70px w-lg-70px ms-2" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="bottom" data-bs-original-title="Referral Partners">
                                            <!--begin::Svg Icon | path: icons/duotune/art/art002.svg-->
                                            <span class="svg-icon svg-icon-1 svg-icon-lg-2hx">
                                                <img src="https://img.icons8.com/external-konkapp-detailed-outline-konkapp/30/FFFFFF/external-referral-marketing-and-growth-konkapp-detailed-outline-konkapp.png"/>
                                            </span>
                                            <!--end::Svg Icon-->
                                        </a>
                                        <a href="#" class="btn btn-icon btn-outline btn-nav h-50px w-50px h-lg-70px w-lg-70px ms-2" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="bottom" data-bs-original-title="Bank User">
                                            <!--begin::Svg Icon | path: icons/duotune/abstract/abs042.svg-->
                                            <span class="svg-icon svg-icon-1 svg-icon-lg-2hx">
                                                <img src="https://img.icons8.com/external-kiranshastry-solid-kiranshastry/30/FFFFFF/external-bank-finance-kiranshastry-solid-kiranshastry.png"/>
                                            </span>
                                            <!--end::Svg Icon-->
                                        </a>

                                        <a href="{!! url('admin/users-management') !!}" class="btn btn-icon btn-outline btn-nav h-50px w-50px h-lg-70px w-lg-70px ms-2" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="bottom" data-bs-original-title="Merchant User">
                                            <!--begin::Svg Icon | path: icons/duotune/technology/teh008.svg-->
                                            <span class="svg-icon svg-icon-1 svg-icon-lg-2hx">
                                                <img src="https://img.icons8.com/external-flatart-icons-outline-flatarticons/30/FFFFFF/external-users-cv-resume-flatart-icons-outline-flatarticons.png"/>
                                            </span>
                                            <!--end::Svg Icon-->
                                        </a>
                                        <a href="#" class="btn btn-icon btn-outline btn-nav h-50px w-50px h-lg-70px w-lg-70px ms-2" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="bottom" data-bs-original-title="Applications">
                                            <!--begin::Svg Icon | path: icons/duotune/medicine/med005.svg-->
                                            <span class="svg-icon svg-icon-1 svg-icon-lg-2hx">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                    <path opacity="0.3" d="M20 15H4C2.9 15 2 14.1 2 13V7C2 6.4 2.4 6 3 6H21C21.6 6 22 6.4 22 7V13C22 14.1 21.1 15 20 15ZM13 12H11C10.5 12 10 12.4 10 13V16C10 16.5 10.4 17 11 17H13C13.6 17 14 16.6 14 16V13C14 12.4 13.6 12 13 12Z" fill="black"></path>
                                                    <path d="M14 6V5H10V6H8V5C8 3.9 8.9 3 10 3H14C15.1 3 16 3.9 16 5V6H14ZM20 15H14V16C14 16.6 13.5 17 13 17H11C10.5 17 10 16.6 10 16V15H4C3.6 15 3.3 14.9 3 14.7V18C3 19.1 3.9 20 5 20H19C20.1 20 21 19.1 21 18V14.7C20.7 14.9 20.4 15 20 15Z" fill="black"></path>
                                                </svg>
                                            </span>
                                            <!--end::Svg Icon-->
                                        </a>
                                        <a href="#" class="btn btn-icon btn-outline btn-nav h-50px w-50px h-lg-70px w-lg-70px ms-2" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="bottom" data-bs-original-title="Transactions">
                                            <!--begin::Svg Icon | path: icons/duotune/medicine/med005.svg-->
                                            <span class="svg-icon svg-icon-1 svg-icon-lg-2hx">
                                                <img src="https://img.icons8.com/material-outlined/35/FFFFFF/card-in-use.png"/>
                                            </span>
                                            <!--end::Svg Icon-->
                                        </a>
                                        <a href="#" class="btn btn-icon btn-outline btn-nav h-50px w-50px h-lg-70px w-lg-70px ms-2" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="bottom" data-bs-original-title="Transaction Summary Report">
                                            <!--begin::Svg Icon | path: icons/duotune/medicine/med005.svg-->
                                            <span class="svg-icon svg-icon-1 svg-icon-lg-2hx">
                                                <img src="https://img.icons8.com/ios/35/FFFFFF/calculator--v1.png"/>
                                            </span>
                                            <!--end::Svg Icon-->
                                        </a>
                                    </div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Row-->
                            @yield('content')
                        </div>
                        <!--end::Post-->
                    </div>
                    <!--end::Container-->
                    <!--begin::Footer-->
                    @include('layouts.admin.footer')
                    <!--end::Footer-->
                </div>
                <!--end::Wrapper-->
            </div>
            <!--end::Page-->
        </div>
        <!--end::Root-->

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

        <script type="text/javascript">
            var DATE = "{{ date('d-m-Y') }}";
            var current_page_url = "<?php echo URL::current(); ?>";
            var current_page_fullurl = "<?php echo URL::full(); ?>";
            var CSRF_TOKEN= "{{ csrf_token() }}";
        </script>

        <script>
            window.hostname = '{{env("LARAVEL_ECHO_HOST")}}';
            window.laravel_echo_port = '{{env("LARAVEL_ECHO_PORT")}}';
            window.user_id={{auth()->guard('admin')->user()->id}};
            window.user_type='admin';
        </script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.2.0/socket.io.js"></script>
        
        @include('layouts.admin.alert')
        @include('layouts.admin.deleteModal')

        @yield('customScript')
    </body>
    <!--end::Body-->
</html>