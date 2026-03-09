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
        <h1>Edit Transaction Fees</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/transactionfees')}}"><i class="fa fa-exchange"></i> <span>Manage Transaction Fees</span></a></li>
            <li class="active"> Edit Transaction Fees</li>
        </ol>
    </section>
    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
            {{Form::model($recordInfo, ['method' => 'post', 'id' => 'adminForm', 'enctype' => "multipart/form-data"]) }}            
            <div class="form-horizontal">
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Transaction Type <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{$recordInfo->transaction_type}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">User Transaction Fee (%) <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('user_charge', null, ['class'=>'form-control required dollarsscents', 'placeholder'=>'User Transaction Fee' ,'autocomplete' => 'off','min'=>0,'max'=>99])}}
                        </div>
                    </div>       
                    <?php 
                    global $agentTransFeeType;
                    if(in_array($recordInfo->transaction_type, $agentTransFeeType)){ ?>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Agent Transaction Fee (%) <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('agent_charge', null, ['class'=>'form-control required dollarsscents', 'placeholder'=>'Agent Transaction Fee' ,'autocomplete' => 'off','min'=>0,'max'=>99])}}
                        </div>
                    </div>    
                    <?php } ?>
                    
                    <?php 
                    global $merchantTransFeeType;
                    if(in_array($recordInfo->transaction_type, $merchantTransFeeType)){ ?>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Merchant Transaction Fee (%) <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('merchant_charge', null, ['class'=>'form-control required dollarsscents', 'placeholder'=>'Merchant Transaction Fee' ,'autocomplete' => 'off','min'=>0,'max'=>99])}}
                        </div>
                    </div>    
                    <?php } ?>
                    
                    <div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        {{Form::submit('Submit', ['class' => 'btn btn-info'])}}
                        <a href="{{ URL::to( 'admin/transactionfees')}}" title="Cancel" class="btn btn-default canlcel_le">Cancel</a>
                    </div>
                </div>
            </div>
            {{ Form::close()}}
        </div>
    </section>
@endsection