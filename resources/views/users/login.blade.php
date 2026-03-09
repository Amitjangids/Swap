@extends('layouts.login')
@section('content')

<style type="text/css"> 

.toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
}

.toggle-password i {
    font-size: 18px;
} 

</style>
<?php 
    $lang = Session::get('locale') ?? 'en';  // Get the selected language from the session or default to 'en'
    $textDirection = ($lang == 'fr') ? 'text-right' : 'text-left'; // Set direction class based on the language
?>

<section class="same-section login-page">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6">
                <div class="login-content-wrapper">
                    <div class="login-content-parent">
                        <a href="{{HTTP_PATH}}">
                            <img src="{{PUBLIC_PATH}}/assets/front/images/logo.svg" alt="image">
                        </a>
                        <h2>{{__('message.Login to')}}<br>{{__('message.your account')}}</h2>
                        <p>"{{__('message.Our end-to-end payment solution is designed to deliver the best payment experience to your customers, suppliers, and employees, thereby enhancing your performance and revenue.')}}" </p>
                    </div>
                    {{ Form::open(array('method' => 'post', 'id' => 'loginform', 'class' => 'form form-signin','url' => 'login')) }}
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
                        <div class="form-group m-0">
                            <label>{{__('message.Password')}}:</label>
                            <div class="login-contact">
                                <div class="input-box-parent">
                                    <input type="password" name="password" autocomplete="off" id="password">
                                    <button type="button" id="togglePassword" class="toggle-password">
                                        <i class="fa fa-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="forgot-password-btn">
                                <a href="{{HTTP_PATH}}/forgot-password">{{__('message.Forgot Password?')}}</a>
                            </div>
                        </div>
                        <div class="login-btn">
                            <button type="submit" class="btn btn-primaryx">{{__('message.Login')}}</button>
                        </div>
                        <div class="register-page-parent">
                            <p>{{__('message.Don t have an account?')}}<a href="{{HTTP_PATH}}/register">{{__('message.REGISTER HERE')}}</a></p>
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
            "email": "required",
            "password": "required",
        },
        messages: {
            "email": "{{__('message.Enter email address')}}",
            "password": "{{__('message.Enter password')}}",
        },
        submitHandler: function (form) {
            form.submit();
        }
       });
    });
</script>


<script type="text/javascript">
    const passwordInput = document.getElementById('password'); 
    const togglePassword = document.getElementById('togglePassword');  

// Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="fa fa-eye-slash"></i>' : '<i class="fa fa-eye"></i>';
    }); 

</script>

@endsection