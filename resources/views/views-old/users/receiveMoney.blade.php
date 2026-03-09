@extends('layouts.inner')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
//        $("#userform").validate();

        $("#userform").validate({
            submitHandler: function (form) {
                $.ajax({
                    type: 'POST',
                    url: "<?php echo HTTP_PATH; ?>/receive-money",
                    data: $('#userform').serialize(),
                    cache: false,
                    beforeSend: function () {
                        $('#loaderID').show();
                    },
                    success: function (data) {
                        var obj = jQuery.parseJSON(data);
                        is_error = obj.status;
                        err_html = obj.reason;

                        if (is_error == 'Error') {
                            jQuery('.er_msg').append('<div class="alert alert-block alert-danger"><button data-dismiss="alert" class="close close-sm" type="button"><i class="fa fa-times"></i></button>' + err_html + '</div>');
                            setTimeout("hideerrorsucc()", 4000);
                        } else {
                            jQuery('.er_msg').append('<div class="alert alert-success"><button data-dismiss="alert" class="close close-sm" type="button"><i class="fa fa-times"></i></button>' + err_html + '</div>');
                            setTimeout("hideerrorsucc()", 4000);

                            $('#user_name').html(obj.userName);
                            $('#qrcode').attr("src",obj.qr_code);
                            $("#show_form").hide();
                            $("#show_qr").show();
                        }
                    }
                });
            }
        });
        jQuery.validator.addMethod("dollarsscents", function(value, element) {
        return this.optional(element) || /^\d{0,10}(\.\d{0,2})?$/i.test(value);
    }, "{{__('message.You can enter amount upto two decimal points.')}}");
        
    });
</script>
<div class="page-heading">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>
                    {{__('message.Receive Money')}}
                </h2>
            </div>
        </div>
    </div>
</div>
<div class="container">
    <div class="row">
        <div class="col-sm-10 m-auto">
            <div class="main-option-thumb-box">
                <div class="row justify-content-center">
                    <div class="form-cards col-sm-6" id="show_form">
                        <div class="ee er_msg">@include('elements.errorSuccessMessage')</div>
                        {{ Form::open(array('method' => 'post', 'id' => 'userform', 'class' => ' border-form')) }} 
                        <div class="form-group card-form-group">
                            <label>{{__('message.Enter My Number')}}</label>
                            {{Form::text('phone', $userInfo->phone, ['class'=>'required digits', 'placeholder'=>__('message.Enter My Number'), 'autocomplete'=>'OFF','minlenght'=>8])}}
                        </div>
                        <div class="form-group card-form-group">
                            <label>{{__('message.Enter Amount')}}</label>
                            <div class="input-relative">
                                {{Form::text('amount', '', ['class'=>'required dollarsscents', 'placeholder'=>__('message.Enter Amount'), 'autocomplete'=>'OFF','min'=>1])}}
                                <span>IQD</span>
                            </div>
                        </div>
                        <button type="submit" class="btn-grad grad-two btn-one">{{__('message.Generate QR Code')}}</button>
                        {{ Form::close()}}
                    </div>
                    <div class="form-cards col-sm-6" id="show_qr" style="display: none;">
                        <h4 style="text-align: center;" id="user_name">{{__('message.Scan QR code to pay')}}</h4>
                        <div class="qr-inner" style="text-align: center;">
                            {{HTML::image('', SITE_TITLE,['id'=>'qrcode'])}}
                        </div>
                        <h6>{{__('message.Please Ask the sender to scan the  QR Code')}}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection