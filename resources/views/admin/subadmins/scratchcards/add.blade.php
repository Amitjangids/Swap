@extends('layouts.admin')
@section('content')
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

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
$.validator.addMethod("dollarsscents", function(value, element) {
        return this.optional(element) || /^\d{0,10}(\.\d{0,2})?$/i.test(value);
    }, "You can enter amount upto 10 digits with two decimal points.");
        $("#adminForm").validate();
    });
</script>
<script>
    $(function () {
        $("#expiry_date").datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 'today',
            changeMonth: true,
            changeYear: true,
//            yearRange: "+30"
        });
    });
</script>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Add Scratch Card</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/scratchcards')}}"><i class="fa fa-ticket"></i> <span>Manage Scratch Cards</span></a></li>
            <li class="active"> Add Scratch Card</li>
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

<?php /*                    <div class="form-group">
                        <label class="col-sm-2 control-label">Card Number <span class="require">*</span></label>
                        <div class="col-sm-10 control_span">
                            {{$uniqueCardNumber}}
                        </div>
                    </div> */?>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Card Value <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('real_value', null, ['class'=>'form-control required dollarsscents', 'placeholder'=>'Card Value', 'autocomplete' => 'off','min'=>1])}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Card Cost <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('card_value', null, ['class'=>'form-control required dollarsscents', 'placeholder'=>'Card Cost', 'autocomplete' => 'off','min'=>1])}}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Number Of Cards <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('number_of_cards', null, ['class'=>'form-control required digits', 'placeholder'=>'Number Of Cards', 'autocomplete' => 'off','min'=>1,'maxlength'=>4])}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Expiry Date <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('expiry_date', null, ['id'=>'expiry_date','class'=>'form-control required', 'placeholder'=>'Expiry Date', 'readonly'])}}
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




