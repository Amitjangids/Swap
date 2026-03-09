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

    .strength-message {
        margin-top: 15px;
        font-size: 14px;
        color: #555;
    }

    .strength-weak {
        color: red;
    }

    .strength-medium {
        color: orange;
    }

    .strength-strong {
        color: green;
    }

</style>
<section class="same-section login-page">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6">
                <div class="login-content-wrapper">
                    <div class="login-content-parent">
                        <a href="{{HTTP_PATH}}">
                            <img src="{{PUBLIC_PATH}}/assets/front/images/logo.svg" alt="image">
                        </a>
                        <h2>{{__('message.Register to')}}<br> {{__('message.your account')}}</h2>
                        <p>"{{__('message.Our end-to-end payment solution is designed to deliver the best payment experience to your customers, suppliers, and employees, thereby enhancing your performance and revenue.')}}" </p>
                    </div>

                    {{ Form::open(array('method' => 'post', 'id' => 'loginform', 'class' => 'form form-signin')) }}
                    <div class="login-from-parent create-account-custom-error">
                        <div class="form-group">
                            <label>{{__('message.Email Address')}}:</label>
                            <div class="login-contact">
                                <div class="input-box-parent">
                                    <input class="required" type="text" name="email" value="{{ old('email') }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>{{__('Referral Code')}} (Optional): </label>
                            <div class="login-contact">
                                <div class="input-box-parent">
                                    <input type="text" name="referralCode" value="{{ Session::get('referralCode') }}" placeholder="Enter referral code if you have one">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>{{__('message.Password')}}:</label>
                            <div class="login-contact">
                                <div class="input-box-parent">
                                    <input class="required" type="password" name="password" id="password">
                                    <button type="button" id="togglePassword" class="toggle-password">
                                        <i class="fa fa-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                            <!-- <div id="strengthMessage" class="strength-message"></div> -->
                        </div>
                        <div class="form-group m-0">
                            <label>{{__('message.Confirm Password')}}:</label>
                            <div class="login-contact">
                                <div class="input-box-parent">
                                    <input class="required" type="password" name="confirm_password" id="confirm_password">
                                    <button type="button" id="togglePasswordC" class="toggle-password">
                                        <i class="fa fa-eye-slash"></i>
                                    </button>
                                </div>
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

        $.validator.addMethod("passworreq", function (input) {
        var reg = /[0-9]/; // At least one number
        var reg2 = /[a-z]/; // At least one lowercase letter
        var reg3 = /[A-Z]/; // At least one uppercase letter
        return reg.test(input) && reg2.test(input) && reg3.test(input);
    }, "{{ __('message.Password must be a combination of Numbers, Uppercase & Lowercase Letters.') }}");

        $("#loginform").validate({
            rules: {
                "email": {
                    required: true,
                    email: true 
                },
                "password": {
                    required: true,
                    minlength: 8, 
                    passworreq: true 
                },
                "confirm_password": {
                    required: true,
                    equalTo: "#password" 
                }
            },
            messages: {
                "email": {
                    required: "{{__('message.Enter email address')}}",
                    email: "{{__('message.Enter a valid email address')}}"
                },
                "password": {
                    required: "{{__('message.Enter a password')}}",
                    minlength: "{{__('message.Password must be at least 6 characters long')}}"
                },
                "confirm_password": {
                    required: "{{__('message.Confirm your password')}}",
                    equalTo: "{{__('message.Passwords do not match')}}"
                }
            },

            submitHandler: function (form) {
                form.submit();
            }
        });
    });
    /*$(document).ready(function () {
        $("#loginform").validate({
            rules: {
                "email": {
                    required: true,
                    email: true 
                },
                "password": {
                    required: true,
                    minlength: 8
                },
                "confirm_password": {
                    required: true,
                    equalTo: "#password" // Ensure it matches the password field
                }
            },
            messages: {
                "email": {
                    required: "Enter email address",
                    email: "Enter a valid email address"
                },
                "password": {
                    required: "Enter a password",
                    minlength: "Password must be at least 8 characters long"
                },
                "confirm_password": {
                    required: "Confirm your password",
                    equalTo: "Passwords do not match"
                }
            },
            submitHandler: function (form) {
                form.submit();
            }
        });
    });*/
</script>
<script type="text/javascript">
    const passwordInput = document.getElementById('password');
    const passwordCInput = document.getElementById('confirm_password');
    const togglePassword = document.getElementById('togglePassword');
    const togglePasswordC = document.getElementById('togglePasswordC');
    const strengthMessage = document.getElementById('strengthMessage');

// Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="fa fa-eye-slash"></i>' : '<i class="fa fa-eye"></i>';
    });
    togglePasswordC.addEventListener('click', function() {
        const type = passwordCInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordCInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="fa fa-eye-slash"></i>' : '<i class="fa fa-eye"></i>';
    });

// Password strength checker
/*passwordInput.addEventListener('input', function() {
    const value = passwordInput.value;
    const strength = getPasswordStrength(value);

    if (strength === 'Weak') {
        strengthMessage.textContent = 'Weak password';
        strengthMessage.className = 'strength-message strength-weak';
    } else if (strength === 'Medium') {
        strengthMessage.textContent = 'Medium strength password';
        strengthMessage.className = 'strength-message strength-medium';
    } else if (strength === 'Strong') {
        strengthMessage.textContent = 'Strong password';
        strengthMessage.className = 'strength-message strength-strong';
    } else {
        strengthMessage.textContent = '';
    }
});*/

// Function to check password strength
/*function getPasswordStrength(password) {
    let strength = 'Weak';

    if (password.length > 8 && /[A-Z]/.test(password) && /[a-z]/.test(password) && /\d/.test(password) && /\W/.test(password)) {
        strength = 'Strong';
    } else if (password.length > 6 && (/[A-Z]/.test(password) || /\d/.test(password) || /\W/.test(password))) {
        strength = 'Medium';
    }

    return strength;
}*/ 
</script>

@endsection