@extends('layouts.payment')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
//        $("#loginform").validate();

        $("#loginform").validate({
            submitHandler: function (form) {
                var form = $('#loginform');
                $.ajax({
                    url: "{!! HTTP_PATH !!}/onlineShopping",
                    type: "POST",
                    data: $('#loginform').serialize(),
                    cache: false,
                    success: function (result) {
                        var json = $.parseJSON(result);
                        if (json.result == 1) {
//                            var err_html = json.message;
//                            $('#success_message').html(err_html);
//                            $('#successModal').modal('show');
                            
                            window.location.href = json.path;
                        } else {
                            $('#pay_error_message').html(json.message);
                            $('#errorPayModal').modal('show');
                        }
                    }
                });
            }
        });
        
        setInterval(function () {
            chkpayment()
        }, 10000);
    });
    
    function chkpayment() {
        $.ajax({
            url: "<?php echo HTTP_PATH;?>/users/chkpayment",
            beforeSend: function () {
                //$('#loaderID').show();
            },
            success: function (data) { 
                if (data != 0) {
                    document.location.href = data;
                }
            }
        });
    }
</script>
<div id="errorPayModal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <!-- Modal content-->

                <div class="modal-content">
                    <div class="pin-box">
                        <div id="img_dv">{{HTML::image('public/img/front/failed.svg', SITE_TITLE)}}</div>
                        <h3 id="pay_error_message">Success!</h3>

                    </div>
                    <div class="d-flex">
                        <button type="button" class="btn-grad grad-two btn-one" onclick="completeOk1()">OK</button>
                    </div>
                </div>
            </div>
            <!--  <div class="modal-content">
                
             </div> -->
        </div>
<div class="container payment-page">
    <div class="row">
        <div class="col-sm-6 ml-auto mr-auto">
            <div class="order-summery">
                <table>
                    <tr><th colspan="2">Order Summery</th></tr>
                    <tr><td>Merchant Name</td><td>{{$orderInfo->Merchant->business_name}}</td></tr>
                    <tr><td>Order id</td><td>{{$orderInfo->order_id}}</td></tr>
                    <tr><td>Bill Amount</td><td>IQD {{$orderInfo->amount}}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <div class="qr-box-payment">
                <h4>Scan QR code <br>
                    to pay</h4>
                <div class="qr-inner">
                    {{HTML::image('public/'.$orderInfo->qr_code, SITE_TITLE)}}
                </div>
                <ul>
                    <li>1). Open your SatPay App</li>
                    <li>2). Tap on online shopping</li>
                    <li>3). Scan QR code</li>
                </ul>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="log-payment-box">
                <h5>Login to pay</h5>
                <p>Enter your SatPay mobile number and password to make the payment </p>
                <div class="ee er_msg">@include('elements.errorSuccessMessage')</div>
                {{ Form::open(array('method' => 'post', 'id' => 'loginform', 'class' => 'form form-signin')) }}
                <div class="form-group">
                    <label>Enter your mobile number</label>
                    {{Form::text('phone', Cookie::get('user_phone'), ['class'=>'form-control required enterkey', 'placeholder'=>'Enter your mobile number', 'autocomplete'=>'OFF'])}}
                </div>
                <div class="form-group">
                    <label>Enter your password</label>
                    {{Form::input('password', 'password', Cookie::get('user_password'), array('class' => "form-control required", 'placeholder' => 'Enter your password', 'id'=>'password','minlength'=>8))}}
                </div>

                <button type="submit" class="btn-grad grad-two">Pay Now</button>
                {{ Form::close()}}
            </div>
        </div>
    </div>
</div>

@endsection