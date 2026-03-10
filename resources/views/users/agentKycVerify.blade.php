@extends('layouts.login')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
        $.validator.addMethod("alphanumeric", function (value, element) {
            return this.optional(element) || /^[\w.]+$/i.test(value);
        }, "Only letters, numbers and underscore allowed.");
        $.validator.addMethod("passworreq", function (input) {
            var reg = /[0-9]/; //at least one number
            var reg2 = /[a-z]/; //at least one small character
            var reg3 = /[A-Z]/; //at least one capital character
            //var reg4 = /[\W_]/; //at least one special character
            return reg.test(input) && reg2.test(input) && reg3.test(input);
        }, "Password must be a combination of Numbers, Uppercase & Lowercase Letters.");
//        $("#registerform").validate();

        $(".opt_input").keyup(function () {
            if (this.value.length == this.maxLength) {
                $(this).next('label').remove();
                $(this).next('.opt_input').focus();
            }
        });

        $("#registerform").validate({
            submitHandler: function (form) {
                if ($('#checkterm').is(':checked')) {
//                    if ($('#is_upload').val() == '0') {
//                        alert('<?php echo __('message.Please upload national identity image.'); ?>');
//                    } else {
                        $("#registerform").submit();
//                    }
                } else {
                    alert('<?php echo __('message.Please accept terms and conditions'); ?>');
                }
            }
        });

    });

    function hideerrorsucc() {
        $('.close.close-sm').click();
    }

</script>

<div class="form-box-pre-register">
    <div class="bg-img-phone">{{HTML::image('public/img/front/phone-bg.png', SITE_TITLE)}}</div>
    <div class="container">
        <div class="row">
            <div class="col-sm-6">
                <div class="pre-register-left-head">
                    <h1><span>{{__('message.Your New Banking')}}</span>
                        {{__('message.Experience')}} </h1>
                    <div class="join-app">
                        <p>{{__('message.Join us on mobile app')}}</p>
                        <a href="javascript:void(0);">{{HTML::image('public/img/front/apple-store.svg', SITE_TITLE)}}</a>
                        <a href="javascript:void(0);">{{HTML::image('public/img/front/g-play-store.svg', SITE_TITLE)}}</a>
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="pre-register-form">
                    <h4 class="form-heading">{{__('message.Sign Up')}}</h4>
                    <div class="ee er_msg">@include('elements.errorSuccessMessage')</div>
                    <?php /* {{ Form::model($userInfo, array('method' => 'post', 'id' => 'registerform', 'class' => ' border-form', 'enctype' => "multipart/form-data")) }} */ ?>
                    {{ Form::open(array('method' => 'post', 'id' => 'registerform', 'class' => ' border-form', 'enctype' => "multipart/form-data")) }} 
                    <div class="form-group">
                        <label>{{__('message.User Image Upload (Optional)')}}</label>
                        <div class="filform-group">
                            {{HTML::image('public/img/front/pro-icon.png', SITE_TITLE,['id'=>'profile_image','width'=>'116px'])}}
                            <div class="file-uploader ">
                                <label for="file-input" class="grad-one">
                                    {{__('message.Upload')}} <i class="fa fa-folder"></i></label>
                                {{Form::file('profile_image', ['id'=>'file-input','class'=>'', 'accept'=>IMAGE_EXT,'onChange'=>'uploadProfile(this)'])}}
                            </div>
                        </div>
                    </div>
                    <div class="form-group ">
                        <label>{{__('message.National ID')}}</label>
                        <div class="fg-id">
                            {{Form::text('national_identity_number', '', ['class'=>'form-control', 'placeholder'=>'Enter National ID Number', 'autocomplete'=>'OFF'])}}
                            {{HTML::image('public/img/front/id.svg', SITE_TITLE)}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{__('message.Upload picture of your national identity')}}</label>
                        <div class="filform-group">
                            {{HTML::image('public/img/front/pro-icon.png', SITE_TITLE,['id'=>'identity_image','width'=>'116px'])}}
                            <div class="file-uploader ">
                                <label for="file-input1" class="grad-one">
                                    {{__('message.Upload')}} <i class="fa fa-folder"></i></label>
                                {{Form::file('identity_image', ['id'=>'file-input1','class'=>'required', 'accept'=>IMAGE_EXT,'onChange'=>'uploadIdentity(this)'])}}
                            </div>
                        </div>
                    </div>
                    <div class="check-tearm">
                        {{Form::checkbox('terms', '1', '', array('class' => "required", 'id' =>"checkterm"))}}
                        <label for="checkterm">{{__('message.I agree and accept')}} <a href="{{ URL::to( 'terms-and-condition')}}" target="_blank">{{__('message.terms and conditions')}}</a> {{__('message.&')}} <a href="{{ URL::to( 'privacy-policy')}}" target="_blank">{{__('message.privacy policy')}}</a></label>
                    </div>
                    <input type="hidden" id="is_upload" name="is_upload" value="0">
                    <button type="submit" class="btn-grad grad-two">{{__('message.Proceed')}}</button>
                    {{ Form::close()}}
                    <p class="text-center step-text">{{__('message.Step 3/3')}}</p>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function uploadProfile(input) {
        var url = input.value;
        var ext = url.substring(url.lastIndexOf('.') + 1).toLowerCase();
        if (input.files && input.files[0] && (ext == "png" || ext == "jpeg" || ext == "jpg")) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $('#profile_image').attr('src', e.target.result);
            }

            reader.readAsDataURL(input.files[0]);
        } else {
            alert('<?php echo __('message.Only png,jpeg,jpg files allow for image.'); ?>');
        }
    }

    function uploadIdentity(input) {
        var url = input.value;
        var ext = url.substring(url.lastIndexOf('.') + 1).toLowerCase();
        if (input.files && input.files[0] && (ext == "png" || ext == "jpeg" || ext == "jpg")) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $('#identity_image').attr('src', e.target.result);
                $('#is_upload').val(1);
            }

            reader.readAsDataURL(input.files[0]);
        } else {
            alert('<?php echo __('message.Only png,jpeg,jpg files allow for image.'); ?>');
        }
    }
</script>
@endsection