@extends('layouts.inner')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
        $("#userform").validate();
        $.validator.addMethod("passworreq", function (input) {
            var reg = /[0-9]/; //at least one number
            var reg2 = /[a-z]/; //at least one small character
            var reg3 = /[A-Z]/; //at least one capital character
            //var reg4 = /[\W_]/; //at least one special character
            return reg.test(input) && reg2.test(input) && reg3.test(input);
        }, "<?php echo __('message.Password must be at least 8 characters long, contains an upper case letter, a lower case letter, a number and a symbol.');?>");
    });
</script>
<style type="text/css">

    .switch {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 34px;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #6E6E6E;
  transition: .4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 26px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  transition: .4s;
}

input:checked + .slider {
  background-color: green;
}

input:focus + .slider {
  box-shadow: 0 0 0 4px rgba(21, 156, 228, 0.7);
  outline: none;
}

input:checked + .slider:before {
  transform: translateX(26px);
}

.slider.round {
  border-radius: 34px;
}

.slider.round:before {
  border-radius: 50%;
}

</style>
<div class="page-heading">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>
                    
                </h2>
            </div>
        </div>
    </div>
</div>
<div class="container">
    <div class="row">
        <div class="col-sm-10 m-auto setting_sec">
            <div class="main-option-thumb-box">
                <div class="row justify-content-center">
                  <div class="head_msg">{{__('message.Do you want to apply the transaction Fee charges to user?')}}</div>
                    <div class="form-cards col-sm-6">
                        <div class="ee er_msg">@include('elements.errorSuccessMessage')</div>
                        {{ Form::open(array('method' => 'post', 'id' => 'userform', 'class' => ' border-form')) }} 
                            <div class="content">
  <label class="switch">
    <?php $checked = '';
    if($recordInfo->trans_pay_by == 'User'){
        $checked = 'checked=checked';
    }?>
    <input type="checkbox" <?php echo $checked;?> name="trans_pay_by" value="0">
    <span class="slider round"></span>
  </label>
</div>
                        
                        <button type="submit" class="btn-grad grad-two btn-one">{{__('message.Confirm')}}</button>
                        {{ Form::close()}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection