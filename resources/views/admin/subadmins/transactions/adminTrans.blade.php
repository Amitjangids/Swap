@extends('layouts.admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>Manage Admin Transactions</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li class="active"> Manage Admin Transactions</li>
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
                    <span class="hint"><label>Search by user name</label>{{Form::text('user', null, ['class'=>'form-control', 'placeholder'=>'Search by user name', 'autocomplete' => 'off'])}}</span>
                    <span class="hint"><label>Search by user phone</label>{{Form::text('user_phone', null, ['class'=>'form-control', 'placeholder'=>'Search by user phone', 'autocomplete' => 'off'])}}</span>
                   <!--  <span class="hint"><label>Search by receiver name</label>{{Form::text('receiver', null, ['class'=>'form-control', 'placeholder'=>'Search by receiver name', 'autocomplete' => 'off'])}}</span>
                    <span class="hint"><label>Search by receiver phone</label>{{Form::text('receiver_phone', null, ['class'=>'form-control', 'placeholder'=>'Search by receiver phone', 'autocomplete' => 'off'])}}</span> -->
                   <?php /* <span class="hint">
                        <?php $typeList = array('Debit' => 'Debit', 'Credit' => 'Credit', 'Request' => 'Request'); ?>                        
                        {{Form::select('type', $typeList,null, ['class' => 'form-control','placeholder' => 'Select transaction type'])}}
                    </span> */?>

                    <span class="hint">
                        <label>Select transaction for</label>
                        <?php $adminPaymentFor = array('Credited by admin'=>'Credited by admin','Debited by admin'=>'Debited by admin'); ?>                        
                        {{Form::select('for', $adminPaymentFor,null, ['class' => 'form-control','placeholder' => 'Select transaction for'])}}
                    </span>

<!--<span class="hint">{{Form::text('for', null, ['class'=>'form-control', 'placeholder'=>'Search by transaction for', 'autocomplete' => 'off'])}}</span>-->
                    <span class="hint">
                        <label>Search by transaction ID</label>
                        {{Form::text('refrence', null, ['class'=>'form-control', 'placeholder'=>'Search by transaction ID', 'autocomplete' => 'off'])}}</span>
                    <span class="hint">
                        <label>Search by date</label>
                        {{Form::text('to', null, ['id'=>'toDate','class'=>'form-control', 'placeholder'=>'Search by date', 'autocomplete' => 'off'])}}
                    </span>
                    <div class="admin_asearch  ">
                        <div class="ad_s ajshort">{{Form::button('Submit', ['class' => 'btn btn-info admin_ajax_search'])}}</div>
                        <div class="ad_cancel"><a href="{{URL::to('admin/transactions/adminTrans')}}" class="btn btn-default canlcel_le">Clear Search</a></div>
                    </div>
                </div>
                {{ Form::close()}}
                <!--<div class="add_new_record"><a href="{{URL::to('admin/transactions/add')}}" class="btn btn-default"><i class="fa fa-plus"></i> Add User</a></div>-->
            </div>            
            <div class="m_content" id="listID">
                @include('elements.admin.transactions.adminTrans')
            </div>
        </div>
    </section>

  
</div>
@endsection