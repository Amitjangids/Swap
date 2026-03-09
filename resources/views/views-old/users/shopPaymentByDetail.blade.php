@extends('layouts.inner')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
        // $("#userform").validate();
        jQuery.validator.addMethod("dollarsscents", function(value, element) {
        return this.optional(element) || /^\d{0,10}(\.\d{0,2})?$/i.test(value);
    }, "{{__('message.You can enter amount upto two decimal points.')}}");

        $("#userform").validate({
            submitHandler: function (form) {
                var form = $('#submitform');
                var check_status = $('#check_status').val();
                if (check_status == 1) {
                    $.ajax({
                        url: "{!! HTTP_PATH !!}/send-money",
                        type: "POST",
                        data: $('#userform').serialize(),
                        cache: false,
                        success: function (result) {
                            var json = $.parseJSON(result);
                            if (json.result == 1) {
                                var err_html = json.message;
                                $('#success_message').html(err_html);
                                $('#successModal').modal('show');
                            } else{
                                $('#error_message').html(json.message);
                                $('#errorModal').modal('show');
                            }
                        }
                    });
                } else {
                    $.ajax({
                        url: "{!! HTTP_PATH !!}/checkTransactionFee",
                        type: "POST",
                        data: $('#userform').serialize(),
                        cache: false,
                        success: function (result) {
                            var json = $.parseJSON(result);
                            if (json.result == 1) {
                                var err_html = json.message;
                                $('#succ_message').html(err_html);
                                $('#trans_fee').val(json.transaction_fee);
                                $('#mySuccessModal').modal('show');
                            } else{
                                var err_html = json.message;
                                $('#error_message').html(err_html);
                                $('#errorModal').modal('show');
                            }
                        }
                    });
                }
            }
        });
    });
</script>
<div class="page-heading">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>
                    {{__('message.Shop Payment')}}
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
                    <div class="form-cards col-sm-6">
                        <div class="ee er_msg">@include('elements.errorSuccessMessage')</div>
                        {{ Form::open(array('method' => 'post', 'id' => 'userform', 'class' => ' border-form')) }} 
                        <div class="form-group card-form-group">
                            <label>{{__('message.Recipient Mobile Number')}}</label>
                            {{Form::text('phone', $merchantValue, ['class'=>'required digits', 'placeholder'=>__('message.Enter recipient mobile number'), 'autocomplete'=>'OFF','minlenght'=>8])}}
                        </div>
                        <div class="form-group card-form-group">
                            <label>{{__('message.Enter Amount')}}</label>
                            <div class="input-relative">
                                {{Form::text('amount', '', ['class'=>'required dollarsscents', 'placeholder'=>__('message.Enter Amount'), 'autocomplete'=>'OFF','min'=>1])}}
                                <span>IQD</span>
                            </div>
                        </div>
                        <input type="hidden" value="0" name="check_status" id="check_status">
                        <input type="hidden" value="0" name="trans_fee" id="trans_fee">
                        <button type="submit" class="btn-grad grad-two btn-one">{{__('message.Pay')}}</button>
                        {{ Form::close()}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection