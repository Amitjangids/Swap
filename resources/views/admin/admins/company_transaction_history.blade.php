@extends('layouts.admin')
@section('content')
@php
use App\Http\Controllers\Admin\AdminsController;
@endphp
@php
use App\Permission;
@endphp

<div class="content-wrapper">
    <section class="content-header">
        <h1>Manage Company Transactions History Of {{$username}}</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li class="active"> Manage Company Transactions History  Of {{$username}}</li>
        </ol>
    </section>

    <section class="content">
	<div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
        <div class="box box-info">
           <div class="admin_search">
            {{ Form::open(array('method' => 'post', 'id' => 'adminSearch')) }}
                <div class="form-group align_box dtpickr_inputs">
                <span class="hint">
                        <?php $arr = array("Deposit"=>"Deposit","Withdraw"=>"Withdraw"); ?>                        
                        {{Form::select('for', $arr,null, ['class' => 'form-control','placeholder' => 'Select transaction for'])}}  
                    </span>
                    <span class="hint">
                        {{Form::text('to', null, ['id'=>'toDate','class'=>'form-control', 'placeholder'=>'Search by date', 'autocomplete' => 'off'])}}
                    </span>
                    <div class="admin_asearch">
                        <div class="ad_s ajshort">{{Form::button('Submit', ['class' => 'btn btn-info admin_ajax_search'])}}</div>
                        <div class="ad_cancel"><a href="{{URL::to('admin/admins/company-transaction-history/'.$slug)}}" class="btn btn-default canlcel_le">Clear Search</a></div>
                    </div>
                </div>
                {{ Form::close()}}
                @php
                    $roles1 = AdminsController::getRoles(Session::get('adminid'));   
                @endphp
                <?php $permissions = DB::table('permissions')->where('role_id',$roles1)->pluck('permission_name')->toArray();?>
                @if(in_array('pay-company',$permissions))
                <div class="add_new_record" style="10px;"><a href="{{URL::to('admin/admins/pay-company/'.$slug)}}" class="btn btn-default"><i class="fa fa-plus"></i> Pay Company</a>
                </div>
                @endif
             </div>            
            <div class="m_content" id="listID">
           @include('elements.admin.admins.company_transaction_history')
            </div>
        </div>
    </section>
</div>
@endsection