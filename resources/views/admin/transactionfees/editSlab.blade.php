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
        
        $("#adminForm").validate();
    });
 </script>
 
<div class="content-wrapper">
    <section class="content-header">
        <h1>Edit Slab Amount</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/users')}}"><i class="fa fa-users"></i> <span>Manage Edit Slab Amount</span></a></li>
            <li class="active"> Edit Slab Amount</li>
        </ol>
    </section>
    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <!-- <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div> -->
            {{ Form::open(array('method' => 'post', 'id' => 'adminForm', 'enctype' => "multipart/form-data")) }}
            <div class="form-horizontal">
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Min Amount <span class="require">*</span></label>
                        <div class="col-sm-2">
                            {{Form::number('min',$allrecords->min_amount, ['class'=>'form-control required ', 'placeholder'=>'Min Amount', 'autocomplete' => 'off','min'=>'1'])}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Max Amount <span class="require">*</span></label>
                        <div class="col-sm-2">
                            {{Form::number('max',$allrecords->max_amount, ['class'=>'form-control required', 'placeholder'=>'Max Amount', 'autocomplete' => 'off','min'=>'1'])}}
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