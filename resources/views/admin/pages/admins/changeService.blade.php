@extends('layouts.admin')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
        $("#adminForm").validate();
    });
</script>
<div class="content-wrapper">
    <section class="content-header">
        <h1>Manage Service Hours</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li class="active">Manage Service Hours</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <?php global $serviceDays; ?>
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
            {{Form::model($serviceInfo, ['method' => 'post', 'id' => 'adminForm', 'class' => 'form form-signin']) }}
            <div class="form-horizontal">
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Select ACH (ACH) <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::select('ach', $serviceDays,null, ['class' => 'form-control required','placeholder' => 'Select ACH (ACH)'])}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Select C21 (Check21) <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::select('c21', $serviceDays,null, ['class' => 'form-control required','placeholder' => 'Select C21 (Check21)'])}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Select EFT (Canada) <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::select('eft', $serviceDays,null, ['class' => 'form-control required','placeholder' => 'Select EFT (Canada)'])}}
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