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
        {{ HTML::style('public/assets/css/front/main.css')}}
        {{ HTML::style('public/assets/css/front/responsive.css')}}
        
        <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
       
        {{ HTML::script('public/assets/js/front/jquery.min.js')}}
        {{ HTML::script('public/assets/js/front/bootstrap.min.js')}}
        {{ HTML::script('public/assets/js/front/custom.js')}}
        {{ HTML::script('public/assets/js/front/jquery.validate.min.js')}}    
    </head>
    <body>
        @include('elements.header_two')
        @yield('content') 
        
         <div id="mySuccessModal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="pin-box">
                        {{HTML::image('public/img/front/success_image.png', SITE_TITLE)}}
                        <br>
                        <br>
                        <br>
                        <h4 id="succ_message">

                        </h4>
                        <br>
                        <br>
                    </div>

                    <div class="d-flex btns-pop">
                        <button type="button" class="close mod-cancel" data-dismiss="modal">{{__('message.Cancel')}}</button>
                        <button type="button" class="btn-grad grad-two btn-one btnConff" onclick="submitForm()">{{__('message.Confirm')}}</button>

                    </div>
                </div>
            </div>
            <!--  <div class="modal-content">
                
             </div> -->
        </div>

        <script>
            function submitForm() {
                $('.btnConff').prop('disabled', true);
                $('#check_status').val('1');
//                $('#userform').attr('id', 'submitform');
                $('#mySuccessModal').modal('hide');
                $("#userform").submit();
            }
            function completeOk() {
                $('#check_status').val('0');
                $('.modal').modal('hide');
                window.location.href = "<?php echo HTTP_PATH; ?>/login";
            }
            function completeOk1() {
                $('#check_status').val('0');
                $('.modal').modal('hide');
            }

        </script>

        <div id="errorModal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <!-- Modal content-->

                <div class="modal-content">
                    <div class="pin-box">
                        <div id="img_dv">{{HTML::image('public/img/front/failed.svg', SITE_TITLE)}}</div>
                        <h3 id="error_message">{{__('message.Success!')}}</h3>

                    </div>
                    <div class="d-flex">
                        <button type="button" class="btn-grad grad-two btn-one" onclick="completeOk1()">{{__('message.OK')}}</button>
                    </div>
                </div>
            </div>
            <!--  <div class="modal-content">
                
             </div> -->
        </div>
        <div id="successModal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <!-- Modal content-->

                <div class="modal-content">
                    <div class="pin-box">
                        <div id="img_dv">{{HTML::image('public/img/front/success.png', SITE_TITLE)}}</div>
                        <h3 id="success_message">{{__('message.Success!')}}</h3>

                    </div>
                    <div class="d-flex">
                        <button type="button" class="btn-grad grad-two btn-one" onclick="completeOk()">{{__('message.Done')}}</button>
                    </div>
                </div>
            </div>
            <!--  <div class="modal-content">
                
             </div> -->
        </div>
    </body>
</html>