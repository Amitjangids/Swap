@extends('layouts.admin')
@section('content')

<script type="text/javascript">
    $(document).ready(function () {
        $.validator.addMethod("alphanumeric", function (value, element) {
            return this.optional(element) || /^[\w.]+$/i.test(value);
        }, "Only letters, numbers and underscore allowed.");
        $.validator.addMethod("passworreq", function (input) {
            var reg = /[0-9]/; //at least one number
            var reg2 = /[a-z]/; //at least one small character
            var reg3 = /[A-Z]/; //at least one capital character
            //var reg4 = /[\W_]/; //at least one special character
            return reg.test(input) && reg2.test(input) && reg3.test(input);
        }, "Password must be a combination of Numbers, Uppercase & Lowercase Letters.");

        $("#adminForm").validate();
        
        jQuery.validator.addMethod("dollarsscents", function(value, element) {
        return this.optional(element) || /^\d{0,10}(\.\d{0,2})?$/i.test(value);
    }, "You can enter amount upto 10 digits with two decimal points.");
    });
</script>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Add Card Detail</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/cards')}}"><i class="fa fa-credit-card"></i> <span>Manage Cards</span></a></li>
            <li><a href="{{URL::to('admin/cards/carddetail/'.$cslug)}}"><i class="fa fa-credit-card"></i> <span>Manage Card Details</span></a></li>
            <li class="active"> Add Card Detail</li>
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
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Serial Code <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('serial_number', null, ['class'=>'form-control required', 'placeholder'=>'Serial Code', 'autocomplete' => 'off'])}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Pin Number <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('pin_number', null, ['class'=>'form-control required', 'placeholder'=>'Pin Number', 'autocomplete' => 'off'])}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Currency For Value <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::select('currency', $currencyList,null, ['class' => 'form-control required','placeholder' => 'Select Currency For Value','id'=>'currency'])}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Value <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('real_value', null, ['class'=>'form-control required', 'placeholder'=>'Value', 'autocomplete' => 'off',])}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Card Cost (IQD) <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('card_value', null, ['class'=>'form-control required dollarsscents', 'placeholder'=>'Card Cost', 'autocomplete' => 'off','min' => 1])}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Card Cost For Agent (IQD) <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('agent_card_value', null, ['class'=>'form-control required dollarsscents', 'placeholder'=>'Value For Agent', 'autocomplete' => 'off','min' => 1])}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Instruction <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('instruction', null, ['class'=>'form-control required', 'placeholder'=>'Instruction', 'autocomplete' => 'off','maxlength'=>'20'])}}
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

</div>
    
    @endsection




