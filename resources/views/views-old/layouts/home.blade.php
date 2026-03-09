<!DOCTYPE html>
<html>
    <head>
        <?php
$cookie_name = "XSRF-TOKEN";
$cookie_value = csrf_token();
setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "Secure"); // 86400 = 1 day
setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "HttpOnly"); // 86400 = 1 day
?>
<meta name="csrf-token" content="{{ csrf_token() }}">

        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>{{$title.TITLE_FOR_LAYOUT}}</title>
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <link rel="shortcut icon" href="{!! FAVICON_PATH !!}" type="image/x-icon"/>
        <link rel="icon" href="{!! FAVICON_PATH !!}" type="image/x-icon"/>
        <meta http-equiv="X-Frame-Options" content="SAMEORIGIN">
        <meta name="robots" content="noindex, nofollow">

        {{ HTML::style('public/assets/css/front/bootstrap.min.css')}}
        {{ HTML::style('public/assets/css/front/main.css?ver=1.2')}}
        {{ HTML::style('public/assets/css/front/responsive.css')}}

        {{ HTML::script('public/assets/js/front/jquery.min.js')}}
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/owl.carousel.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/owl.theme.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    </head>
    <body>
        @include('elements.header')
        @yield('content') 
        @include('elements.footer')
        
    </body>
</html>