@extends('layouts.admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>KYC Details</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/agents')}}"><i class="fa fa-user-secret"></i> <span>Agents</span></a></li>
            <li class="active"> KYC Details</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-info">
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
                       
            <div class="m_content" id="listID">
                @include('elements.admin.agents.kycdetail')
            </div>
            
        </div>
    </section>
</div>
@endsection