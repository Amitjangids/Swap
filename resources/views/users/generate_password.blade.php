@extends('layouts.login')
@section('content')
<header>
       <div class="container">
        <div class="row">
           <div class="col logo"><a href="index.html"><img src="{{PUBLIC_PATH}}/assets/front/images/logo.svg" alt="image"></a></div>
        </div>
       </div>
</header>

<section class="banner-section password-section">
       <div class="container">
           <div class="user-password-wrapper">
               <h2>{{__('message.Create Password')}}</h2>
               {{ Form::open(array('method' => 'post', 'id' => 'generatePasswordForm', 'class' => 'form form-signin')) }}
               <div class="col-lg-6">
                   <div class="login-from-parent">
                        <div class="input-box-parent from-group">
                            <label>{{__('message.Password')}}:</label>
                            <input type="password" class="form-control required" id="password" name="password" placeholder="{{__('message.Enter Password')}}" autocomplete="off">
                        </div>

                        <div class="input-box-parent from-group">
                            <label>{{__('message.Confirm Password')}}:</label>
                            <input type="password" class="form-control required" name="confirm_password" placeholder="{{__('message.Enter Confirm Password')}}" autocomplete="off">
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

   <script type="text/javascript">
    $(document).ready(function () {
    $("#generatePasswordForm").validate({
        rules: {
            "password": "required",
            "confirm_password":{
                required: true,
                equalTo: "#password"
            }
        },
        messages: {
            "password": "{{__('message.Enter a password')}}",
            "confirm_password": {
                required: "{{__('message.Enter a confirm password')}}",
                equalTo: "{{__('message.Password and confirm password should be same')}}"
        },
    },
    });
    });
</script>

@endsection