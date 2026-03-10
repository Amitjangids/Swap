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
        <h1>Manage Amount Slab</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li class="active"> Manage Amount Slab</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-info">
            <!-- <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div> -->
            <div class="admin_search">
                {{ Form::open(array('method' => 'post', 'id' => 'adminSearch')) }}
                <!-- <div class="form-group align_box dtpickr_inputs">
                    <span class="hints">Search by Username, Phone Number, Email Address or Date</span>
                    <span class="hint">{{Form::text('keyword', null, ['class'=>'form-control', 'placeholder'=>'Search by keyword', 'autocomplete' => 'off'])}}</span>
                    <span class="hint">
                        {{Form::text('to', null, ['id'=>'toDate','class'=>'form-control', 'placeholder'=>'Search by date', 'autocomplete' => 'off'])}}
                    </span>
                    <div class="admin_asearch">
                        <div class="ad_s ajshort">{{Form::button('Submit', ['class' => 'btn btn-info admin_ajax_search'])}}</div>
                        <div class="ad_cancel"><a href="{{URL::to('admin/users')}}" class="btn btn-default canlcel_le">Clear Search</a></div>
                    </div>
                </div> -->
                @php
                    $roles = AdminsController::getRoles(Session::get('adminid'));   
                @endphp
            
        
                <?php $permissions = DB::table('permissions')->where('role_id',$roles)->pluck('permission_name')->toArray();?>
                @if(in_array('addSlab',$permissions))
                <div class="add"><a href="{{URL::to('admin/transactionfees/addSlab')}}" class="btn btn-default"><i class="fa fa-plus"></i> Add Slab</a></div>
                @endif

           
            </div>          
            <div class="m_content" id="listID">
                @include('elements.admin.transactionfees.slablist')
            </div>
        </div>
    </section>
    
    
</div>

@endsection