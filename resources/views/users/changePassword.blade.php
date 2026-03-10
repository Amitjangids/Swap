@extends('layouts.inner')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
        $("#userform").validate();
        $.validator.addMethod("passworreq", function (input) {
            var reg = /[0-9]/; //at least one number
            var reg2 = /[a-z]/; //at least one small character
            var reg3 = /[A-Z]/; //at least one capital character
            //var reg4 = /[\W_]/; //at least one special character
            return reg.test(input) && reg2.test(input) && reg3.test(input);
        }, "<?php echo __('message.Password must be at least 8 characters long, contains an upper case letter, a lower case letter, a number and a symbol.');?>");
    });
</script>
<div class="page-heading">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>
                    {{__('message.Change Password')}}
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
                            <label>{{__('message.Enter Current Password')}}</label>
                            {{Form::password('old_password', ['class'=>'form-control required', 'placeholder'=>__('message.Enter Current Password'), 'id'=>'old_password'])}}
                        </div>
                        <div class="form-group card-form-group">
                            <label>{{__('message.Enter New Password')}}</label>
                            {{Form::password('new_password', ['class'=>'form-control required passworreq', 'placeholder'=>__('message.Enter New Password'), 'id'=>'newpassword', 'minlength'=>8])}}
                        </div>
                        <div class="form-group card-form-group">
                            <label>{{__('message.Confirm New Password')}}</label>
                            {{Form::password('confirm_password', ['class'=>'form-control required', 'placeholder'=>__('message.Confirm New Password'), 'equalTo' => '#newpassword', 'id'=>'confirm_password'])}}
                        </div>
                        <button type="submit" class="btn-grad grad-two btn-one">{{__('message.Confirm')}}</button>
                        {{ Form::close()}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection