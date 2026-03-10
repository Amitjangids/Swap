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
                        <h2>{{__('message.Forgot')}}<br> {{__('message.Password')}}</h2>
                        <p>"{{__('message.Our end-to-end payment solution is designed to deliver the best payment experience to your customers, suppliers, and employees, thereby enhancing your performance and revenue.')}}" </p>
                    </div>
                    {{ Form::open(array('method' => 'post', 'id' => 'loginform', 'class' => 'form form-signin')) }}
                    <div class="login-from-parent">
                        <div class="form-group">
                            <label>{{__('message.Email Address')}}:</label>
                            <div class="login-contact">
                                <div class="input-box-parent">
                                    <input class="required" type="email" name="email" autocomplete="off" value="{{ old('email') }}">
                                    <!-- <input class="required" type="text" name="phoneNumber"> -->
                                </div>
                            </div>
                        </div>

                        <div class="login-btn">
                            <button type="submit" class="btn btn-primaryx">{{__('message.Verify')}}</button>
                        </div>

                        <div class="register-page-parent">
                            <a href="{{HTTP_PATH}}/login">{{__('message.Login')}}</a>
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
                "email": {
                    required: true,
                    email: true 
                }, 
            },
            messages: {
                "email": {
                    required: "{{__('message.Enter email address')}}",
                    email: "{{__('message.Enter a valid email address')}}"
                }, 
            },

            submitHandler: function (form) {
                form.submit();
            }
        });
    });
</script>

@endsection