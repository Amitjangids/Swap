@extends('layouts.admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>Manage Transactions History For {{$userInfo->user_type}}<small>({{$userInfo->name}})</small></h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li class="active"> Manage Transactions History</li>
        </ol>
    </section>
<style>
        .row-button {
    top: 103px !important;
}
    </style>
    <section class="content">
        <div class="box box-info">
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
            <div class="admin_search">
                {{ Form::open(array('method' => 'post', 'id' => 'adminSearch')) }}
                <div class="form-group align_box dtpickr_inputs">
                    <!--<span class="hints" style="font-weight: 600;">Search</span>-->
                    <span class="hint"><label>Search by sender name</label>{{Form::text('sender', null, ['class'=>'form-control', 'placeholder'=>'Search by sender name', 'autocomplete' => 'off'])}}</span>
                    <span class="hint"><label>Search by sender phone</label>{{Form::text('sender_phone', null, ['class'=>'form-control', 'placeholder'=>'Search by sender phone', 'autocomplete' => 'off'])}}</span>
                    <span class="hint"><label>Search by receiver name</label>{{Form::text('receiver', null, ['class'=>'form-control', 'placeholder'=>'Search by receiver name', 'autocomplete' => 'off'])}}</span>
                    <span class="hint"><label>Search by receiver phone</label>{{Form::text('receiver_phone', null, ['class'=>'form-control', 'placeholder'=>'Search by receiver phone', 'autocomplete' => 'off'])}}</span>
                   <?php /* <span class="hint">
                        <?php $typeList = array('Debit' => 'Debit', 'Credit' => 'Credit', 'Request' => 'Request'); ?>                        
                        {{Form::select('type', $typeList,null, ['class' => 'form-control','placeholder' => 'Select transaction type'])}}
                    </span> */?>

                    <span class="hint">
                        <label>Select transaction for</label>
                        <?php global $paymentFor; ?>                        
                        {{Form::select('for', $paymentFor,null, ['class' => 'form-control','placeholder' => 'Select transaction for'])}}
                    </span>

<!--<span class="hint">{{Form::text('for', null, ['class'=>'form-control', 'placeholder'=>'Search by transaction for', 'autocomplete' => 'off'])}}</span>-->
                    <span class="hint">
                        <label>Search by transaction ID</label>
                        {{Form::text('refrence', null, ['class'=>'form-control', 'placeholder'=>'Search by transaction ID', 'autocomplete' => 'off'])}}</span>
                    <span class="hint">
                        <label>Search by date</label>
                        {{Form::text('to', null, ['id'=>'toDate','class'=>'form-control', 'placeholder'=>'Search by date', 'autocomplete' => 'off'])}}
                    </span>
                    <span class="hint"><label>Search by Account ID</label>{{Form::text('accountId', null, ['class'=>'form-control', 'placeholder'=>'Search by account id', 'autocomplete' => 'off'])}}</span>
                    <div class="admin_asearch row-button">
                        <div class="ad_s ajshort">{{Form::button('Submit', ['class' => 'btn btn-info admin_ajax_search'])}}</div>
                        <div class="ad_cancel"><a href="{{URL::to('admin/transactions/transactionHistory/'.$userInfo->slug)}}" class="btn btn-default canlcel_le">Clear Search</a></div>
                    </div>
                </div>
                {{ Form::close()}}
                <!--<div class="add_new_record"><a href="{{URL::to('admin/transactions/add')}}" class="btn btn-default"><i class="fa fa-plus"></i> Add User</a></div>-->
            </div>            
            <div class="m_content" id="listID">
                @include('elements.admin.transactions.transactionHistory')
            </div>
        </div>
    </section>
    
</div>
@endsection