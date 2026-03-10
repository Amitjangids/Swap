@extends('layouts.admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>Enable/Disable Features For {{$userInfo->user_type}}<small>({{$userInfo->name}})</small></h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li class="active"> Enable/Disable Features </li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-info">
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
            <div class="m_content" id="listID">
                @include('elements.admin.users.homeFeatures')    

            </div>
            </div>
    </section>
</div>
@endsection