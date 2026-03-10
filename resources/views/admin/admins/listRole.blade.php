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
        <h1>Manage Department</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li class="active"> Manage Department</li>
        </ol>
    </section>

    <section class="content">
	<div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
        <div class="box box-info">
           <div class="admin_search">
                @php
                    $roles1 = AdminsController::getRoles(Session::get('adminid'));   
                @endphp
                <?php $permissions = DB::table('permissions')->where('role_id',$roles1)->pluck('permission_name')->toArray();?>
                @if(in_array('add-department',$permissions))
                <div class="add_new_record" style="10px;"><a href="{{URL::to('admin/admins/add-department')}}" class="btn btn-default"><i class="fa fa-plus"></i> Add Department</a></div>
                @endif
    </div>            
            <div class="m_content" id="listID">
                @include('elements.admin.admins.roleList')
            </div>
        </div>
    </section>
</div>
@endsection