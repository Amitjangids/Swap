@extends('layouts.admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>Manage Used Recharge Cards List</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li class="active"> Manage Used Recharge Cards List</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-info">
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
            <div class="admin_search">
                {{ Form::open(array('method' => 'post', 'id' => 'adminSearch')) }}
                <div class="form-group align_box dtpickr_inputs">
                    <!--<span class="hints">Search by Card Number</span>-->
                    <span class="hint"><label>Search by keyword</label>{{Form::text('keyword', null, ['class'=>'form-control', 'placeholder'=>'Search by keyword', 'autocomplete' => 'off'])}}</span>
                    <span class="hint">
                        <label>Search by card type</label>
                        <?php global $cardType;?>                        
                        {{Form::select('card_type', $cardType,null, ['class' => 'form-control','placeholder' => 'All'])}}
                    </span>
                    <span class="hint">
                        <label>Search by date</label>
                        {{Form::text('to', null, ['id'=>'toDate','class'=>'form-control', 'placeholder'=>'Search by date', 'autocomplete' => 'off'])}}
                    </span>
                    <div class="admin_asearch row-button">
                        <div class="ad_s ajshort">{{Form::button('Submit', ['class' => 'btn btn-info admin_ajax_search'])}}</div>
                        <div class="ad_cancel"><a href="{{URL::to('admin/cards/usedcard')}}" class="btn btn-default canlcel_le">Clear Search</a></div>
                    </div>
                </div>
                {{ Form::close()}}
                <!--<div class="add_new_record"><a href="{{URL::to('admin/scratchcards/add')}}" class="btn btn-default"><i class="fa fa-plus"></i> Add Scratch Card</a></div>-->
            </div>            
            <div class="m_content" id="listID">
                @include('elements.admin.cards.usedcard')
            </div>
        </div>
    </section>
    

</div>
@endsection