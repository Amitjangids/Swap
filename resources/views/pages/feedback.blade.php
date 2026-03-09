@extends('layouts.inner')
@section('content')
<?php $lang = 'ku';
if (Session::get('locale')) {
            $lang = Session::get('locale');
        }?>
<script src='https://www.google.com/recaptcha/api.js?hl=<?php echo $lang;?>'></script>
<script type="text/javascript">
    function checkForm(){
        $('#captcha_msg').html("").removeClass('gcerror');
        if ($("#contactform").valid()) {
            var captchaTick = grecaptcha.getResponse(); 
            if (captchaTick == "" || captchaTick == undefined || captchaTick.length == 0) {
                $('#captcha_msg').html("{{__('message.Please confirm captcha to proceed')}}").addClass('gcerror');
                $('#captcha_msg').addClass('gcerror');
                return false;
            }
        }else{
            var captchaTick = grecaptcha.getResponse(); 
            if (captchaTick == "" || captchaTick == undefined || captchaTick.length == 0) {
                $('#captcha_msg').html("{{__('message.Please confirm captcha to proceed')}}").addClass('gcerror');
                return false;
            }
        }        
    };
</script>

<div class="page-heading">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>
                    {{__('message.Send Your Feedback/Enquiry')}}
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
                        {{ Form::open(array('method' => 'post', 'id' => 'contactform', 'class' => 'form form-signin')) }}  

                        <div class="form-group card-form-group">
                            <label>{{__('message.Email Address')}}</label>

                            {{Form::text('email', Session::get('user_email'), ['class'=>'form-control required email', 'placeholder'=>__('message.Email Address'), 'autocomplete'=>'OFF'])}}
                        </div>
                        <div class="form-group card-form-group">
                            <label>{{__('message.Subject')}}</label>

                            {{Form::text('subject', null, ['class'=>'form-control required', 'placeholder'=>__('message.Subject'), 'autocomplete'=>'OFF'])}}
                        </div>
                        <div class="form-group card-form-group">
                            <label>{{__('message.Message')}}</label>

                            {{Form::textarea('message', null, ['class'=>'form-control required', 'placeholder'=>__('message.Please enter your message!'), 'autocomplete' => 'off', 'rows'=>4])}}
                        </div>
                        <div class="form-group">
                            <div class="inputt gcpaatcha">
                                <div id="recaptchaQ" class="g-recaptcha" data-sitekey="{{ CAPTCHA_KEY }}" style="transform:scale(0.2);-webkit-transform:scale(1);transform-origin:0 0;-webkit-transform-origin:0 0;" ></div>
                                <div class="gcpc" id="captcha_msg"></div>
                            </div>
                        </div>
                        {{Form::submit(__('message.Send'), ['class' => 'btn-grad grad-two btn-one', 'onclick'=>'return checkForm()'])}}
                        {{ Form::close()}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection