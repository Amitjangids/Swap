@extends('layouts.admin')
@section('content')
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

<script type="text/javascript">
    $(document).ready(function () {
        $.validator.addMethod("alphanumeric", function (value, element) {
            return this.optional(element) || /^[\w.]+$/i.test(value);
        }, "Only letters, numbers and underscore allowed.");
        $.validator.addMethod("passworreq", function (input) {
            var reg = /[0-9]/; //at least one number
            var reg2 = /[a-z]/; //at least one small character
            var reg3 = /[A-Z]/; //at least one capital character
            //var reg4 = /[\W_]/; //at least one special character
            return reg.test(input) && reg2.test(input) && reg3.test(input);
        }, "Password must be a combination of Numbers, Uppercase & Lowercase Letters.");

        $("#adminForm").validate();
    });
</script>
<style type="text/css">
    .add_new_record1 {
    float: right;
    padding-right: 20px;
    z-index: 9999;
    position: relative;
}
</style>
<div class="content-wrapper">
    <section class="content-header">
        <h1>Import Card Details For ({{$recordInfo->company_name}})</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/cards')}}"><i class="fa fa-credit-card"></i> <span>Manage Cards</span></a></li>
            <li><a href="{{URL::to('admin/cards/carddetail/'.$cslug)}}"><i class="fa fa-credit-card"></i> <span>Manage Card Details</span></a></li>
            <li class="active"> Import Card Details</li>
        </ol>

    </section>

    <section class="content">

        <div class="box box-info">
            <div class="add_new_record1"><a href="{{URL::to('public/assets/importSample.xls')}}" class="btn btn-default"><i class="fa fa-plus"></i> Download Sample File</a></div>
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>

            {{ Form::open(array('method' => 'post', 'id' => 'adminForm', 'enctype' => "multipart/form-data")) }}
            <div class="form-horizontal">
                <div class="box-body">

   
                    <div class="form-group">
                        <label class="col-sm-2 control-label">XLS File <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::file('csv_file', ['class'=>'form-control required', 'accept'=>'csv'])}}
                            <span class="help-text"> Supported File Types: xls (Max. {{ MAX_IMAGE_UPLOAD_SIZE_DISPLAY }}).</span>
                        </div>
                    </div>

                    <div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        {{Form::submit('Submit', ['class' => 'btn btn-info'])}}
                        {{Form::reset('Reset', ['class' => 'btn btn-default canlcel_le'])}}

                
                
                    </div>
                </div>
            </div>
            {{ Form::close()}}
        </div>


    </section>

</div>
    
    @endsection




