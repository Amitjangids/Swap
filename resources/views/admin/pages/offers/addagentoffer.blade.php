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
        <h1>Add Agent Offer For Agent ({{$userInfo->name}})</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/agents')}}"><i class="fa fa-user-secret"></i> <span>Manage Agents</span></a></li>
            <li><a href="{{URL::to('admin/offers/agentoffers/'.$slug)}}"><i class="fa fa-gift"></i> <span>Manage Agent Offers</span></a></li>
            <li class="active"> Add Agent Offer</li>
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
                    <?php global $offerCards;?>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Card Type <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::select('type', $offerCards,null, ['class' => 'form-control required','placeholder' => 'Select Card Type'])}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Offer Value (%) <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('offer', null, ['class'=>'form-control required dollarsscents', 'placeholder'=>'Offer Value (%)' ,'autocomplete' => 'off','min'=>0,'max'=>100])}}
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