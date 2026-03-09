@extends('layouts.admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>Manage Scratch Cards</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li class="active"> Manage Scratch Cards</li>
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
                    <!--<span class="hints">Search by Card Number</span>-->
                    <span class="hint"><label>Search by Card Number</label>{{Form::text('keyword', null, ['class'=>'form-control', 'placeholder'=>'Search by Card Number', 'autocomplete' => 'off'])}}</span>
                    
                    <span class="hint">
                        <label>Search by purchased type</label>
                        <?php $typeArr = array('0'=>'Non Purchased','1'=>'Purchased');?>                        
                        {{Form::select('purchase_status', $typeArr,null, ['class' => 'form-control','placeholder' => 'All'])}}
                    </span>
                    <span class="hint">  
                        <label>Search by last updated by</label>
                        {{Form::select('last_updated_by', $adminList,null, ['class' => 'form-control','placeholder' => 'All'])}}
                    </span>
                    
                    
                    <span class="hint">
                        <label>Search by expiry date</label>
                        {{Form::text('to1', null, ['id'=>'toDate1','class'=>'form-control', 'placeholder'=>'Search by expiry date', 'autocomplete' => 'off'])}}
                    </span>
<br>
                    <span class="hint">
                        <label>Search by created date</label>
                        {{Form::text('to', null, ['id'=>'toDate','class'=>'form-control', 'placeholder'=>'Search by created date', 'autocomplete' => 'off'])}}
                    </span>
                    <div class="admin_asearch row-button">
                        <div class="ad_s ajshort">{{Form::button('Submit', ['class' => 'btn btn-info admin_ajax_search'])}}</div>
                        <div class="ad_cancel"><a href="{{URL::to('admin/scratchcards')}}" class="btn btn-default canlcel_le">Clear Search</a></div>
                    </div>
                </div>
                {{ Form::close()}}
                <div class="add_new_record"><a href="{{URL::to('admin/scratchcards/add')}}" class="btn btn-default"><i class="fa fa-plus"></i> Add Scratch Card</a></div>
            </div>            
            <div class="m_content" id="listID">
                @include('elements.admin.scratchcards.index')
            </div>
        </div>
    </section>
     
    
</div>
@endsection