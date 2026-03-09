@extends('layouts.admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>Special Offers List For Agent ({{$userInfo->name}})</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/agents')}}"><i class="fa fa-user-secret"></i> <span>Agents</span></a></li>
            <li class="active"> Special Offers List</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-info">
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
            <div class="admin_search">
{{ Form::open(array('method' => 'post', 'id' => 'adminSearch')) }}
                <div class="form-group align_box dtpickr_inputs">
                   
                </div>
                {{ Form::close()}}
                <div class="add_new_record"><a href="{{URL::to('admin/offers/addagentoffer/'.$userInfo->slug)}}" class="btn btn-default"><i class="fa fa-plus"></i> Add Agent Offer</a></div>
            </div>        
            <div class="m_content" id="listID">
                @include('elements.admin.offers.agentoffers')
            </div>
        </div>
    </section>
</div>
@endsection