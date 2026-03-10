@extends('layouts.inner')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
        $("#userform").validate();
        jQuery.validator.addMethod("dollarsscents", function(value, element) {
        return this.optional(element) || /^\d{0,10}(\.\d{0,2})?$/i.test(value);
    }, "{{__('message.You can enter amount upto two decimal points.')}}");
    });
</script>
<div class="page-heading">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>
                    {{__('message.Deposit Agent')}}
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
                        {{ Form::open(array('method' => 'post', 'id' => 'userform', 'class' => ' border-form')) }} 
                        <div class="form-group card-form-group">
                            <label>{{__('message.Agent Mobile Number')}}</label>
                            {{Form::text('phone', '', ['class'=>'required digits', 'placeholder'=>__('message.Enter Mobile Number'), 'autocomplete'=>'OFF','minlenght'=>8])}}
                        </div>
                        <div class="form-group card-form-group">
                            <label>{{__('message.Enter Amount')}}</label>
                            <div class="input-relative">
                                {{Form::text('amount', '', ['class'=>'required dollarsscents', 'placeholder'=>__('message.Enter Amount'), 'autocomplete'=>'OFF','min'=>1])}}
                                <span>IQD</span>
                            </div>
                        </div>
                        <button type="submit" class="btn-grad grad-two btn-one">{{__('message.Send Deposit Request')}}</button>
                        {{ Form::close()}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection