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