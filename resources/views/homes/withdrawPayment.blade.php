@extends('layouts.login')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
        $("#loginform").validate();
        $(".enterkey").keyup(function (e) {
            if (e.which == 13) {
                postform();
            }
        });
        $("#user_password").keyup(function (e) {
            if (e.which == 13) {
                postform();
            }
        });
    });

    function showPass() {
        var x = document.getElementById("password");
        if (x.type === "password") {
            x.type = "text";
            $('#showEye').html('<img src="<?php echo HTTP_PATH; ?>/public/img/front/eye.svg" alt="Dafri Bank">');
        } else {
            x.type = "password";
            $('#showEye').html('<img src="<?php echo HTTP_PATH; ?>/public/img/front/eye.svg" alt="Dafri Bank">');
        }
    }
</script>

<div class="page-heading">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>
                    {{__('message.Withdrawal Request')}}
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
                        
                        <?php if ($amount > 0) { ?>
        <?php if ($order_id != 'N/A') { ?>
        
        <?php if ($user_id != 'N/A') { ?>
                        {{ Form::open(array('method' => 'post', 'id' => 'loginform', 'class' => '')) }} 
                        <div class="form-group card-form-group">
                            <label>{{__('message.Merchant Name')}}</label>
                            <input type="text" id="recipName" value="{{strtoupper($merchant_name)}}" disabled="">
                        </div>
                        <div class="form-group card-form-group">
                            <label>{{__('message.Amount')}}</label>
                            <input type="text" value="{{$user->currency.' '.$amount}}" id="recipAccNum" placeholder="" disabled="">
                        </div>
                        <div class="form-group card-form-group">
                            <label>{{__('message.Username')}}</label>
                            <input type="text" id="recipEmail" value="{{strtoupper($user_name)}}" placeholder="" disabled="">
                        </div>
                        
                        @if(!empty($user))
                    <button class="btn-grad grad-two btn-one" type="submit" name="submit" value="submit">
                        {{__('message.Submit')}}
                    </button>
                    @endif
                        {{ Form::close()}}
                        
                         <?php } else { ?>
                <div class="no_record">
                    Merchant ID / User ID not valid.
                </div>
            <?php } ?>
        <?php } else { ?> 
            <div class="no_record">
                    Order ID not valid.
                </div>
        <?php } ?>
            
        <?php } else { ?>
            <div class="no_record">
                Amount not valid.
            </div>
        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<style type="text/css">
    .widt-req {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        font-family: 'Sora', sans-serif;
    }
    .widt-req form {
        display: flex;
        flex-direction: column;
        width: 30%;
        background: #fff;
        padding: 60px;
        box-shadow: 0 0 16px rgb(0 0 0 / 10%);
        border-radius: 25px;
    }
    .widt-req h4{font-size: 28px;}
    label{font-size: 16px; font-weight: 600; margin-bottom: 10px;}
    input{font-size: 16px; border: 1px solid #000;height: 40px;padding: 5px 10px; border-radius: 10px; margin-bottom: 15px; }
    textarea{font-size: 16px; border: 1px solid #000;height: 80px;padding: 5px 10px; border-radius: 10px; margin-bottom: 15px; }
    .submit{display: inline-block; background: #000; width: auto; color: #fff; width: 150px; margin: 0 auto; cursor: pointer;}
    .modal-content.transfer-pop.w-cionfirm {
        display: flex;
        flex-direction: column;
        width: 30%;
        background: #fff;
        padding: 60px;
        box-shadow: 0 0 16px rgb(0 0 0 / 10%);
        border-radius: 25px;
    }
    .modal-content.transfer-pop.w-cionfirm form {
        width: 100%;
        padding: 0;
        box-shadow: none;
    }
    .modal-content.transfer-pop.w-cionfirm form img{width: 60px;}
    .transfer-fund-pop.confirm-form {
        display: flex;
        flex-direction: column;
    }
    .transfer-fund-pop.confirm-form .ft-img {
        text-align: center;
    }
    .transfer-fund-pop.confirm-form .form-control-new {
        display: flex;
        flex-direction: column;
    }
    .transfer-fund-pop.confirm-form .form-control-new input{background: #000; color: #fff;}
    .submit-btn{display: inline-block; background: #000; width: auto; color: #fff; width: 150px; margin: 0 auto; cursor: pointer;font-size: 16px;
                border: 1px solid #000;
                height: 40px;
                padding: 5px 10px;
                border-radius: 10px;}
    .modal-footer.pop-ok.text-center {
        text-align: center;
    }

</style>
@endsection