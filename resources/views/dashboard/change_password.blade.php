@extends('layouts.home')
@section('content')
<style>
.swap_fields{display:none}
.iti--allow-dropdown .iti__flag-container, .iti--separate-dial-code .iti__flag-container {height: 60px;}
section.banner-section.password-section.newpassword-section {height: 100vh !important;}
.password-new-fields{position: relative;}
.password-new-fields span.fa {position: absolute;top: 50%;right: 15px;transform: translateY(-50%);color: #4b2e6f; cursor: pointer;}
.password-new-fields input + label.error + span { top: 36%; }
</style>    

    <section class="banner-section password-section newpassword-section">
       <div class="container">
            <div class="heading-parent same-heading-wrapper">
               <h2>{{__('message.Change Password')}}</h2>    
           </div>   
            <div class="user-password-wrapper">
            {{ Form::open(array('method' => 'post', 'id' => 'createUserForm', 'class' => 'form form-signin')) }}
               <div class="col-lg-12">
                   <div class="login-from-parent">
                        <div class="input-box-parent from-group">
                            <label>{{__('message.Current Password')}}:</label>
                            <input id="current_password" type="text" class="form-control required" name="current_password" placeholder="{{__('message.Current Password')}}" autocomplete="off">
                        </div>
                        <div class="input-box-parent from-group">
                            <label>{{__('message.New Password')}}:</label>
                            <div class="password-new-fields">
                                {{Form::password('password', ['class'=>'form-control required password_toggle', 'placeholder' =>__('message.New Password'), 'minlength' => 6, 'id'=>'password'])}}
                                <span toggle="#password" class="fa fa-fw field-icon toggle-password toggle-pass fa-eye-slash"></span>
                            </div>
                        </div>
                        <div class="input-box-parent from-group">
                            <label>{{__('message.Confirm New Password')}}:</label>
                             <div class="password-new-fields">
                                {{Form::password('confirm_password', ['class'=>'form-control required password_toggle', 'placeholder' =>__('message.Confirm Password'),'id'=>'confirm_password', 'equalTo' => '#password'])}}
                                <span toggle="#confirm_password" class="fa fa-fw field-icon toggle-password toggle-pass fa-eye-slash"></span>
                            </div>
                        </div>    
                        <div class="login-btn">
                            <button type="button" id="clearButton" class="btn btn-secondary">{{__('message.Cancel')}}</button>
                            <button type="submit" class="btn btn-primaryx">{{__('message.Submit')}}</button>
                        </div>
                    </div>
               </div>
            {{ Form::close()}}
           </div>
       </div>
   </section>  

   <script type="text/javascript">
        $(".toggle-password").click(function() {
            $(this).toggleClass("fa-eye fa-eye-slash");
            input = $(this).parent().find("input");
            if (input.attr("type") == "password") {
                input.attr("type", "text");
            } else {
                input.attr("type", "password");
            }
        });
    </script>

   <script type="text/javascript">
    $(document).ready(function () { 

        /*$.validator.addMethod("passwordCheck", function(value, element) {
        return this.optional(element) || /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/.test(value);
       }, "Password must contain at least one uppercase letter, one lowercase letter, one digit, and one special character");*/

        $.validator.addMethod("passworreq", function (input) {
        var reg = /[0-9]/; // At least one number
        var reg2 = /[a-z]/; // At least one lowercase letter
        var reg3 = /[A-Z]/; // At least one uppercase letter
        return reg.test(input) && reg2.test(input) && reg3.test(input);
    }, "{{__('message.Password must be a combination of Numbers, Uppercase & Lowercase Letters.')}}");


        $("#createUserForm").validate({
        rules: {
        current_password: "required",
        password: {
            required: true,
            minlength: 8,
            passworreq: true
           // pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/
        },
        confirm_password: {
            required: true,
            equalTo: "#password"
        }
    },
    messages: {
    current_password: "{{ __('message.Enter current password') }}",
    password: {
        required: "{{ __('message.Enter new password') }}",
        minlength: "{{ __('message.Password must be at least 8 characters long') }}",
        // pattern: "{{ __('message.Password must contain at least one uppercase letter, one lowercase letter, one digit, and one special character') }}"
    },
    confirm_password: {
        required: "{{ __('message.Enter confirm password') }}",
        equalTo: "{{ __('message.Passwords do not match') }}"
    }
},

    errorPlacement: function (error, element) {
        error.insertAfter(element);
    },
    success: function(label) {
        label.remove();
    },
    submitHandler: function(form) {
        if ($("#password").val() != $("#confirm_password").val()) {
            alert("Passwords do not match");
            return false; // Prevent form submission
        } else {
            form.submit(); // Submit the form
        }
    }
    });
        $("#clearButton").click(function () {
        // Option 1: Refresh the page
        location.reload();

        // Option 2: Reset the form without reloading the page
        // $("#createUserForm")[0].reset();
        // $("#createUserForm").validate().resetForm(); // Reset validation messages
    });
    });
</script>
@endsection

