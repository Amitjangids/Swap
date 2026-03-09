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


        {{ HTML::style('public/assets/css/front/bootstrap.min.css')}}       


        @if(Session::get('locale') == 'ar')
        {{ HTML::style('public/assets/css/front/main_ar.css')}}
        {{ HTML::style('public/assets/css/front/responsive_ar.css')}}

        @elseif(Session::get('locale') == 'en')
        {{ HTML::style('public/assets/css/front/main.css')}}
        {{ HTML::style('public/assets/css/front/responsive.css')}}        

        @else
        {{ HTML::style('public/assets/css/front/main_ku.css')}}
        {{ HTML::style('public/assets/css/front/responsive_ku.css')}}
        @endif 

        <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        {{ HTML::script('public/assets/js/front/jquery.min.js')}}
        {{ HTML::script('public/assets/js/front/bootstrap.min.js')}}
        {{ HTML::script('public/assets/js/front/custom.js')}}

        @if(Session::get('locale') == 'ar')
        {{ HTML::script('public/assets/js/front/jquery.validate_ar.min.js')}}    

        @elseif(Session::get('locale') == 'en')
        {{ HTML::script('public/assets/js/front/jquery.validate_en.min.js')}}         

        @else
        {{ HTML::script('public/assets/js/front/jquery.validate_ku.min.js')}}    
        @endif 


    </head>
    <style>
        .bdclass{
            text-align: right !important;
        }
    </style>
    <?php $bdClas = '';
    if(Session::get('locale') != 'en'){
        $bdClas = 'bdclass';
    }
    
    ?>
    <body class="<?php echo $bdClas;?>">
        @include('elements.header_login')
        @yield('content') 


    </body>
</html>