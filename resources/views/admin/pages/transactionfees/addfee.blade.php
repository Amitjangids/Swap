@extends('layouts.admin')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
        $.validator.addMethod("alphanumeric", function(value, element) {
            return this.optional(element) || /^[\w.]+$/i.test(value);
        }, "Only letters, numbers and underscore allowed.");
        $.validator.addMethod("passworreq", function (input) {
            var reg = /[0-9]/; //at least one number
            var reg2 = /[a-z]/; //at least one small character
            var reg3 = /[A-Z]/; //at least one capital character
            //var reg4 = /[\W_]/; //at least one special character
            return reg.test(input) && reg2.test(input) && reg3.test(input);
        }, "Password must be a combination of Numbers, Uppercase & Lowercase Letters.");
        $.validator.addMethod("dollarsscents", function (value, element) {
            return this.optional(element) || /^\d{0,10}(\.\d{0,2})?$/i.test(value);
        }, "You can enter amount upto 10 digits with two decimal points.");
        $("#adminForm").validate();
    });
 </script>
 
<div class="content-wrapper">
    <section class="content-header">
        <h1>Add Transaction Fees For {{$userInfo->user_type}} <small>({{$userInfo->name}})</small></h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/transactionfees/transactionfee/'.$slug)}}"><i class="fa fa-gift"></i> <span>Manage Fees</span></a></li>
            <li class="active"> Add Transaction Fee</li>
        </ol>
    </section>
    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
            {{ Form::open(array('method' => 'post', 'id' => 'adminForm', 'enctype' => "multipart/form-data")) }}
            <div class="form-horizontal">
                <div class="box-body">
                    <?php 
                    if($userInfo->user_type == 'Agent'){
                        global $agentTransFeeType;
                        $transFeeType = $agentTransFeeType;
                    } elseif($userInfo->user_type == 'Merchant'){
                        global $merchantTransFeeType;
                        $transFeeType = $merchantTransFeeType;
                    } else{
                        global $transFeeType;
                        
                    }
                    ?>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Transaction Type <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::select('transaction_type', $transFeeType,null, ['class' => 'form-control required','placeholder' => 'Select Transaction Type'])}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">User Transaction Fee (%) <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('user_charge', null, ['class'=>'form-control required dollarsscents', 'placeholder'=>'User Transaction Fee' ,'autocomplete' => 'off','min'=>0,'max'=>99])}}
                        </div>
                    </div>
                    
                    <div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        {{Form::submit('Submit', ['class' => 'btn btn-info'])}}
                        {{Form::reset('Reset', ['class' => 'btn btn-default canlcel_le'])}}
                    </div>
                </div>
            </div>
            {{ Form::close()}}
        </div>
    </section>
@endsection