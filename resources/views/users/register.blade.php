@extends('layouts.login')
@section('content')
<section class="same-section login-page">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6">
                <div class="login-content-wrapper">
                    <div class="login-content-parent">
                        <a href="{{HTTP_PATH}}">
                            <img src="{{PUBLIC_PATH}}/assets/front/images/logo.svg" alt="image">
                        </a>
                        <h2>{{__('message.Register')}}<br> {{__('message.your account')}}</h2>
                        <p>"{{__('message.Our end-to-end payment solution is designed to deliver the best payment experience to your customers, suppliers, and employees, thereby enhancing your performance and revenue.')}}" </p>
                    </div>
                    {{ Form::open(array('method' => 'post', 'id' => 'loginform', 'class' => 'form form-signin')) }}

                    <div class="login-from-parent">
                        <label>{{__('message.Phone Number')}}:</label>
                        <div class="login-contact">
                            <div class="country-box">
                                <img src="{{PUBLIC_PATH}}/assets/front/images/country-flag.png" alt="image">
                                <span>+241</span>   
                            </div>
                            <div class="input-box-parent">
                                <input class="required" type="text" name="phoneNumber" placeholder="{{__('message.Enter mobile number')}}">
                                <input type="hidden" name="refCode" value="{{$ref}}">

                            </div>
                        </div>
                        <div class="login-btn">
                            <button type="submit" class="btn btn-primaryx">{{__('message.Submit')}}</button>
                        </div>
                         <div class="register-page-parent">
                            <p>{{__('message.Already have an account?')}} <a href="{{HTTP_PATH}}">{{__('message.LOGIN HERE')}}</a></p>
                        </div>
                    </div>
                    {{ Form::close()}}
                </div>
            </div>
            <div class="col-lg-6">
                <div class="login-image">
                    <img src="{{PUBLIC_PATH}}/assets/front/images/login-image.png" alt="image">
                </div>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
    $(document).ready(function () {
        $("#loginform").validate({
            rules: {
                "phoneNumber": {
                    required: true,
                    digits: true
                }
            },
            messages: {
                "phoneNumber": {
                    required: "{{__('message.Enter mobile number')}}",
                    digits: "{{__('message.Please enter only digits')}}"
                }
            },
            submitHandler: function (form) {
                form.submit();
            }
        });
    });
</script>
@endsection