

@extends('layouts.admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>Pay Merchant</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/users')}}"><i class="fa fa-cogs"></i> <span>Merchant Management</span></a></li>
            <li class="active"> Pay Merchant</li>  
        </ol>
    </section>
    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
            {{ Form::model($recordInfo,array('method' => 'post', 'id' => 'adminForm', 'enctype' => "multipart/form-data")) }}
            <div class="form-horizontal">
                <div class="box-body">
                    <div class="form-group">
         
                        <label class="col-sm-2 control-label">Username</label>
                        <div class="col-sm-10" style="padding-top: 7px;margin-bottom: 0;">
                        @if($recordInfo->user_type == 'Merchant')
                            @php $name  = strtoupper($recordInfo->name)@endphp
                            @elseif($recordInfo->user_type == 'Business')
                            @php $name  = strtoupper($recordInfo->director_name)@endphp
                            @elseif($recordInfo->user_type == 'Agent' && $recordInfo->first_name != "")
                            @php $name  = strtoupper($recordInfo->first_name.' '.$recordInfo->last_name)@endphp
                            @elseif($recordInfo->user_type == 'Agent' && $recordInfo->director_name != "")
                            @php $name  = strtoupper($recordInfo->director_name)@endphp
                            @endif
                            {{$name}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Account Number</label>
                        <div class="col-sm-10" style="padding-top: 7px;margin-bottom: 0;">
                        {{$recordInfo->phone}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Account Balance <span class="require">*</span>  </label>
                        <div class="col-sm-10">
                        {{number_format((($recordInfo->wallet_balance - floor($recordInfo->wallet_balance)) > 0.5 ? ceil($recordInfo->wallet_balance) : floor($recordInfo->wallet_balance)), 0, '', ' ') ?? 0}}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Account Action <span class="require">*</span></label>
                        <div class="col-sm-10">
                            <?php $serviceType = array('Withdraw' => 'Withdraw', 'Deposit' => 'Deposit'); ?>                        
                            {{Form::select('wallet_action', $serviceType,null, ['class' => 'form-control','id' => 'wallet_action','placeholder' => 'Select Action'])}}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Amount <span class="require">*</span>  </label>
                        <div class="col-sm-10">
                        {{ Form::text('amount', null, ['class'=>'form-control required', 'id'=>'amount', 'placeholder'=>'Amount', 'autocomplete' => 'off', 'onkeypress'=>"return validateFloatKeyPress(this,event);"/* , 'min' => '1' */]) }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Reason <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('reason', null, ['class'=>'form-control required','id'=>'reason', 'placeholder'=>'Reason', 'autocomplete' => 'off'])}}
                        </div>
                    </div>

                    <div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        <!-- {{Form::submit('Submit', ['class' => 'btn btn-info'])}} -->
                        {{Form::submit('Submit', ['class' => 'btn btn-info','id'=>'check_form'])}}
                        {{Form::reset('Reset', ['class' => 'btn btn-default canlcel_le'])}}
                    </div>
                </div>
            </div>



            {{ Form::close()}}
        </div>
    </section>


    <script type="text/javascript">
    $(document).ready(function () {
        $("#adminForm").validate({
            rules: {
                wallet_action: {
                    required: true
                },
                amount: {
                    required: true,
                    min: 0.1
                },
                username: {
                    required: true
                },
                reason: {
                    required: true,
                },
            },
            messages: {

                wallet_action: {
                    required: "Please Select Action"
                },
                amount: {
                    required: "Please enter amount",
                    min: "Amount should be greater than 0",
                },
                reason: {
                    required: "Please enter reason"
                }
            }
        });
    });
</script>


    <script type="text/javascript">
    function validateFloatKeyPress(el, evt) {
    var charCode = (evt.which) ? evt.which : event.keyCode;
    var number = el.value.split('.');
    if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57)) {
         return false;
     }
     //just one dot
     if (number.length > 1 && charCode == 46) {
         return false;
     }
     //get the carat position
     var caratPos = getSelectionStart(el);
     var dotPos = el.value.indexOf(".");
     if (caratPos > dotPos && dotPos > -1 && (number[1].length > 1)) {
         return false;
     }
     return true;
    }
    </script>
    @endsection