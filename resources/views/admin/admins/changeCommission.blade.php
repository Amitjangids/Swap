@extends('layouts.admin')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
        $("#adminForm").validate();
    });
</script>
<div class="content-wrapper">
    <section class="content-header">
        <h1>Change Commission</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="javascript:void(0);"><i class="fa fa-cogs"></i> Configuration</a></li>
            <li class="active">Change Commission</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
            {{Form::model($adminInfo, ['method' => 'post', 'id' => 'adminForm', 'class' => 'form form-signin']) }}
            <div class="form-horizontal">
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Commission (%) <span class="require"></span></label>
                        <div class="col-sm-10">
                            {{Form::text('commission', null, ['min'=>'0','max'=>'100','class'=>'form-control', 'placeholder'=>'Commission', 'autocomplete' => 'off'])}}
                        </div>
                    </div>

                    <div class="box-footer">
                            <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                            {{Form::submit('Submit', ['class' => 'btn btn-info'])}}
                            <a href="{{URL::to('admin/admins/dashboard')}}" class="btn btn-default canlcel_le">Cancel</a>
                        </div>
                </div>
            </div>
            {{ Form::close()}}
        </div>
    </section>
</div>
@endsection