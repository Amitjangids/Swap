<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <?php
        $cookie_name = "XSRF-TOKEN";
        $cookie_value = csrf_token();
        setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "Secure"); // 86400 = 1 day
        setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "HttpOnly"); // 86400 = 1 day
        ?>
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>{{$title.TITLE_FOR_LAYOUT}}</title>
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <link rel="shortcut icon" href="{!! FAVICON_PATH !!}" type="image/x-icon"/>
        <link rel="icon" href="{!! FAVICON_PATH !!}" type="image/x-icon"/>
        <meta http-equiv="X-Frame-Options" content="SAMEORIGIN">
        <meta name="robots" content="noindex, nofollow">

        {{ HTML::style('public/assets/css/bootstrap.min.css')}}
        {{ HTML::style('public/assets/css/AdminLTE.min.css?ver=1.4')}}
        {{ HTML::style('public/assets/css/all-skins.min.css?ver=1.4')}}
        {{ HTML::style('public/assets/css/admin.css?ver=1.1')}}
        {{ HTML::style('public/assets/css/font-awesome.min.css')}}

        {{ HTML::script('public/assets/js/jquery-2.1.0.min.js')}}
        {{ HTML::script('public/assets/js/jquery.validate.js')}}
        {{ HTML::script('public/assets/js/app.min.js')}}
        {{ HTML::script('public/assets/js/ajaxsoringpagging.js')}}
        {{ HTML::script('public/assets/js/listing.js')}}
        {{ HTML::script('public/assets/js/bootstrap.min.js')}}
    </head>
    <body class="hold-transition skin-blue sidebar-mini">
        <div class="wrapper">
            @include('elements.admin.header')
            @include('elements.admin.left_menu')
            @yield('content')
        </div>
        <script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
        <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
        <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
        <script>
$.noConflict();
jQuery(function ($) {

    
    $('#toDate').daterangepicker({
        autoUpdateInput: false,
        locale: {
            format: 'YYYY-MM-DD',
            maxDate: 'mm-dd-yyyy',
        },
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });

    $('#toDate').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + '/' + picker.endDate.format('YYYY-MM-DD'));
    });

    $('#toDate1').daterangepicker({
        autoUpdateInput: false,
        locale: {
            format: 'YYYY-MM-DD',
            maxDate: 'mm-dd-yyyy',
        },
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });

    $('#toDate1').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + '/' + picker.endDate.format('YYYY-MM-DD'));
    });

    //        $('#toDate').data('daterangepicker').setStartDate();
    //        $('#toDate').data('daterangepicker').setEndDate();
});
        </script>
    </body>
</html>