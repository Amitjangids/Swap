{{ HTML::style('public/assets/front/css/bootstrap.min.css')}}
{{ HTML::style('public/assets/front/css/custom.css')}}
{{ HTML::style('public/assets/front/css/media.css')}}
{{ HTML::style('public/assets/front/css/owl.carousel.min.css')}}
<style type="text/css">
    section.banner-section.password-section {
        height: 100%;
        background: transparent;
        padding: 50px 0;
        overflow-y: auto;
    }

    body {
        margin: 0;
        padding: 0;
        background: #eee;
    }

    section.same-section.login-page .row {
        justify-content: center;
    }

    .login-content-wrapper {
        padding: 30px 30px;
        border-radius: 22px;
    }

    .login-content-parent h2 {
        font-size: 30px;
        margin: 0 0 0;
    }

    .login-content-parent {
        margin: 0 0 20px;
    }

    .login-content-parent a {
        max-width: 130px;
        margin: 0 0 30px;
    }

    .login-from-parent label {
        font-size: 18px;
    }

    .login-from-parent .login-contact .input-box-parent input {
        padding: 12px;
        font-size: 16px;
        border-radius: 7px;
    }

    .login-btn {
        margin: 30px 0 0;
    }

    .login-btn .btn-primaryx {
        border-radius: 8px;
        padding: 12px 12px;
        font-size: 16px;
    }
</style>
<section class="same-section login-page">


    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6">
                <div class="login-content-wrapper">
                    <div class="login-content-parent">
                        <a href="{{HTTP_PATH}}">
                            <img src="{{PUBLIC_PATH}}/assets/front/images/logo.svg" alt="image">
                        </a>
                        <h2>{{__('Login to')}} {{__('your account')}}</h2>
                    </div>
                    <?php if (session()->has('success_message')) { ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <strong>Success!</strong> {{Session::get('success_message')}}
                    </div>
                    <?php    
                            Session::forget('success_message');
} ?>
                    <?php if (session()->has('error_message')) { ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <strong>Error!</strong> {{Session::get('error_message')}}
                    </div>
                    <?php    
                            Session::forget('error_message');
} ?>

                    {{ Form::open([
    'method' => 'post',
    'id' => 'loginform',
    'class' => 'form form-signin',
    'url' => url('payment-login?merchantId=' . request()->get('merchantId').'&orderId='.request()->get('orderId'))
]) }}


                    <div class="login-from-parent">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <div class="login-contact">
                                <div class="input-box-parent">
                                    <input class="required " type="phoneNumber" name="phoneNumber" autocomplete="off"
                                        value="{{ old('phoneNumber') }}">
                                    <input type="hidden" value="{{request()->get('merchantId')}}" name="merchantId">
                                    <input type="hidden" value="{{request()->get('orderId')}}" name="orderId">
                                </div>
                            </div>
                        </div>
                        <div class="login-btn">
                            <button type="submit" class="btn btn-primaryx">{{__('Login')}}</button>
                        </div>
                    </div>
                    {{ Form::close()}}
                </div>
            </div>
        </div>
    </div>
</section>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
{{ HTML::script('public/assets/js/front/jquery.validate_en.min.js')}}
<script>
    $(document).ready(function () {
        $.validator.addMethod("regex", function (value, element, regexpr) {
            return regexpr.test(value);
        });
        $("#loginform").validate({
            rules: {
                phoneNumber: {
                    required: true,
                    regex: /^\+?[0-9]*$/,
                },
            },
            messages: {
                phoneNumber: {
                    required: "Phone number is required",
                    regex: "Please enter only numeric digits.",
                }
            },
            submitHandler: function (form) {
                form.submit();
            }
        });
    });
</script>