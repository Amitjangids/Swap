@extends('layouts.inner')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
        $("#userform").validate();
    });
</script>
<div class="page-heading">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>
                    {{__('message.Cash Card')}}
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
                            <label>{{__('message.Enter Card Number')}}</label>
                            {{Form::text('card_number', '', ['class'=>'required', 'placeholder'=>__('message.Enter Card Number'), 'autocomplete'=>'OFF'])}}
                        </div>
                        <button type="submit" class="btn-grad grad-two btn-one">{{__('message.Deposit')}}</button>
                        {{ Form::close()}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection