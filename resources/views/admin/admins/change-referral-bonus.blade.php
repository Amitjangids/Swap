@extends('layouts.admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>Change Referral Bonus</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="javascript:void(0);"><i class="fa fa-cogs"></i> Configuration</a></li>
            <li class="active">Change Referral Bonus</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
            {{ Form::open(array('method' => 'post', 'id' => 'adminForm', 'class' => 'form form-signin')) }}
            <div class="form-horizontal">
                <div class="box-body"> 
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Sender Referral Bonus<span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::number('referralBonusSender', $adminInfo->referralBonusSender, ['class'=>'form-control required', 'placeholder'=>'Sender Referral Bonus', 'autocomplete' => 'off','min'=>1])}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Receiver Referral Bonus <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::number('referralBonusReceiver', $adminInfo->referralBonusReceiver, ['class'=>'form-control required', 'placeholder'=>'Receiver Referral Bonus', 'autocomplete' => 'off','min'=>1])}}
                        </div>
                    </div>
                    <div class="box-footer">
                            <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                            {{Form::submit('Submit', ['class' => 'btn btn-info'])}}
                            <a href="{{URL::to('admin/admins/dashboard')}}" class="btn btn-default canlcel_le">Cancel</a>
                        </div>
                </div>
            </div>
            {{ Form::close()}}
        </div>
    </section>
</div>
@endsection