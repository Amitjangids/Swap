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
<header class="merchantby">
    <div class="container">
        <div class="row">
            <div class="col logo">
                <a href="index.html">
                    <img src="{{ PUBLIC_PATH }}/assets/front/images/logo.svg" alt="Logo">
                </a>
            </div>
            <div class="SelectBox ml-auto">
            <select class="" onchange="changeLanguage(this.value)" name="language" >
            <option value="fr" {{ (Session::get('locale') == 'fr' ) ? 'selected' : '' }}>French</option>
            <option value="en" {{ (Session::get('locale') == 'en' ) ? 'selected' : '' }}>English</option>

            </select>
            <div class="chevron">
                <img src="{{ PUBLIC_PATH }}/img/front/drop-arrow.svg" alt="Language Selector">
            </div>
        </div>
        </div>
    </div>
</header>
@if(Session::get('locale') == 'fr'||Session::get('locale') == 'en')
<section class="banner-section password-section merchantby genpassword">
 <div class="container">
     <div class="user-password-wrapper">
         <h2>{{__('message.Create Password')}}</h2>
         {{ Form::open(array('method' => 'post', 'id' => 'generatePasswordForm', 'class' => 'form form-signin')) }}
         <div class="col-lg-6">
             <div class="login-from-parent">
                <div class="input-box-parent from-group">
                    <label>{{__('message.Password')}}:</label>
                    <div class="toggleeye-parent">
                        <input type="password" class="form-control required" id="password" name="password" placeholder="{{__('message.Enter Password')}}" autocomplete="off">
                        <button type="button" id="togglePassword" class="toggle-password">
                            <i class="fa fa-eye-slash"></i>
                        </button>
                    </div>
                </div>

                <div class="input-box-parent from-group">
                    <label>{{__('message.Confirm Password')}}:</label>
                    <div class="toggleeye-parent">
                        <input type="password" class="form-control required" name="confirm_password" id="confirm_password" placeholder="{{__('message.Enter Confirm Password')}}" autocomplete="off">
                        <button type="button" id="togglePasswordC" class="toggle-password">
                            <i class="fa fa-eye-slash"></i>
                        </button>
                    </div>
                </div>

                <div class="login-btn">
                  <button type="submit" class="btn btn-primaryx">{{__('message.Submit')}}</button>
              </div>

              <?php if (session()->has('error_message')) { ?>
                <div class="alert alert-danger" role="alert">
                    {{Session::get('error_message')}}
                </div>
                <?php Session::forget('error_message'); } ?>

            </div>
        </div>
        {{ Form::close()}}
    </div>
</div>
</section>  
@else
<section class="banner-section password-section merchantby genpassword">
 <div class="container">
     <div class="user-password-wrapper">
         <h2>Créer un mot de passe</h2>
         {{ Form::open(array('method' => 'post', 'id' => 'generatePasswordForm', 'class' => 'form form-signin')) }}
         <div class="col-lg-6">
             <div class="login-from-parent">
                <div class="input-box-parent from-group">
                    <label>Mot de passe:</label>
                    <div class="toggleeye-parent">
                        <input type="password" class="form-control required" id="password" name="password" placeholder="Entrez le mot de passe" autocomplete="off">
                        <button type="button" id="togglePassword" class="toggle-password">
                            <i class="fa fa-eye-slash"></i>
                        </button>
                    </div>
                </div>

                <div class="input-box-parent from-group">
                    <label>Confirmez le mot de passe:</label>
                    <div class="toggleeye-parent">
                        <input type="password" class="form-control required" name="confirm_password" id="confirm_password" placeholder="Entrez Confirmer le mot de passe" autocomplete="off">
                        <button type="button" id="togglePasswordC" class="toggle-password">
                            <i class="fa fa-eye-slash"></i>
                        </button>
                    </div>
                </div>

                <div class="login-btn">
                  <button type="submit" class="btn btn-primaryx">
                  Soumettre</button>
              </div>

              <?php if (session()->has('error_message')) { ?>
                <div class="alert alert-danger" role="alert">
                    {{Session::get('error_message')}}
                </div>
                <?php Session::forget('error_message'); } ?>

            </div>
        </div>
        {{ Form::close()}}
    </div>
</div>
</section>  
@endif
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

</script>

@if(Session::get('locale') == 'fr'||Session::get('locale') == 'en')
<script type="text/javascript">
    /*$(document).ready(function () {
        $("#generatePasswordForm").validate({
            rules: {
                "password": "required",
                "confirm_password":{
                    required: true,
                    equalTo: "#password"
                }
            },
            messages: {
                "password": "Enter a password",
                "confirm_password": {
                    required: "Enter a confirm password",
                    equalTo: "Password and confirm password should be same"
                },
            },
        });
    });*/

    $(document).ready(function () {
    // Add custom validation methods

        $.validator.addMethod("passworreq", function (input) {
        var reg = /[0-9]/; // At least one number
        var reg2 = /[a-z]/; // At least one lowercase letter
        var reg3 = /[A-Z]/; // At least one uppercase letter
        return reg.test(input) && reg2.test(input) && reg3.test(input);
    }, "{{__('message.Password must be a combination of Numbers, Uppercase & Lowercase Letters.')}}");

        $("#generatePasswordForm").validate({
            rules: { 
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
                "password": {
                    required: "{{__('message.Enter a password')}}",
                    minlength: "{{__('message.Password must be at least 8 characters long')}}"
                },
                "confirm_password": {
                    required: "{{__('message.Enter a confirm password')}}",
                    equalTo: "{{__('message.Password and confirm password should be same')}}"
                }
            },
            success: function (label) {
                label.remove();
            },
            submitHandler: function (form) {
                form.submit();
            }
        });
    });
</script>
@else
<script type="text/javascript">
    /*$(document).ready(function () {
        $("#generatePasswordForm").validate({
            rules: {
                "password": "required",
                "confirm_password":{
                    required: true,
                    equalTo: "#password"
                }
            },
            messages: {
                "password": "Enter a password",
                "confirm_password": {
                    required: "Enter a confirm password",
                    equalTo: "Password and confirm password should be same"
                },
            },
        });
    });*/

    $(document).ready(function () {
    // Add custom validation methods

        $.validator.addMethod("passworreq", function (input) {
        var reg = /[0-9]/; // At least one number
        var reg2 = /[a-z]/; // At least one lowercase letter
        var reg3 = /[A-Z]/; // At least one uppercase letter
        return reg.test(input) && reg2.test(input) && reg3.test(input);
    }, "Password doit être une combinaison de chiffres, de lettres majuscules et minuscules.");

        $("#generatePasswordForm").validate({
            rules: { 
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
                "password": {
                    required: "Entrez un mot de passe",
                    minlength: "Password doit contenir au moins 8 caractères"
                },
                "confirm_password": {
                    required: "Entrez un mot de passe de confirmation",
                    equalTo: "Password et confirmer le mot de passe doivent être identiques"
                }
            },
            success: function (label) {
                label.remove();
            },
            submitHandler: function (form) {
                form.submit();
            }
        });
    });
</script>
@endif
@endsection