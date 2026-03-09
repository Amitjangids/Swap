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
        }, "Password must be at least 8 characters long, contains an upper case letter, a lower case letter, a number and a symbol.");
        $("#adminForm").validate();

        $("#radio").click(function () {
            $(".main_section").hide();
            $("#station_sec").show();
        });
        $("#advertising").click(function () {
            $(".main_section").hide();
            $("#agency_sec").show();
        });
        $("#advertiser").click(function () {
            $(".main_section").hide();
            $("#advertiser_sec").show();
        });
        
        $("#city").change(function () {
            var cityid = $("#city").val();
            $("#area").load('<?php echo HTTP_PATH . '/admin/users/getarealist/' ?>' + cityid);
        });
    });
</script>
<script>
    $(function () {
        $("#dob").datepicker({
            dateFormat: 'yy-mm-dd',
            maxDate: 'today',
            changeMonth: true,
            changeYear: true,
            yearRange: "-70:+0"
        });
    });
</script>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Edit Agent User</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/agents')}}"><i class="fa fa-user-secret"></i> <span>Manage Agent Users</span></a></li>
            <li class="active"> Edit Agent User</li>
        </ol>
    </section>
    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
            {{Form::model($recordInfo, ['method' => 'post', 'id' => 'adminForm', 'enctype' => "multipart/form-data"]) }}            
            <div class="form-horizontal">
                <div class="box-body">
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Full Name <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('name', null, ['class'=>'form-control required', 'placeholder'=>'Full Name', 'autocomplete' => 'off', 'maxlength' => 25])}}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Email Address <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('email', null, ['class'=>'form-control  required email', 'placeholder'=>'Email Address', 'autocomplete' => 'off'])}}
                        </div>
                    </div>  
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Date Of Birth <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('dob', null, ['class'=>'form-control required ', 'placeholder'=>'Date Of Birth', 'autocomplete' => 'off','id'=>'dob','readonly'])}}
                        </div>
                    </div>  
                    <div class="form-group">
                        <label class="col-sm-2 control-label">City <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::select('city', $cityList,null, ['class' => 'form-control required','placeholder' => 'Select City','id' => 'city'])}}
                            <!--{{Form::text('city', null, ['class'=>'form-control required', 'placeholder'=>'City', 'autocomplete' => 'off'])}}-->
                        </div>
                    </div>  
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Area <span class="require">*</span></label>
                        <div class="col-sm-10" id="area">
                            {{Form::select('area', $areaList,null, ['class' => 'form-control required','placeholder' => 'Select Area'])}}
                        </div>
                    </div>  
<!--                    <div class="form-group">
                        <label class="col-sm-2 control-label">National Identity Number <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('national_identity_number', null, ['class'=>'form-control required', 'placeholder'=>'National Identity Number', 'autocomplete' => 'off', 'minlength' => 8, 'maxlength' => 16])}}
                        </div>
                    </div>                                   -->

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Password <span class="require"></span></label>
                        <div class="col-sm-10">
                            {{Form::password('password', ['class'=>'form-control', 'placeholder' => 'Password', 'minlength' => 8, 'id'=>'password'])}}
                            <!--<span class="help-text"> * Note: If You want to change User's password, only then fill password below otherwise leave it blank. </span>-->
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Confirm Password <span class="require"></span></label>
                        <div class="col-sm-10">
                            {{Form::password('confirm_password', ['class'=>'form-control', 'placeholder' => 'Confirm Password', 'equalTo' => '#password'])}}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Profile Image <span class="require"></span></label>
                        <div class="col-sm-10">
                            {{Form::file('profile_image', ['class'=>'form-control', 'accept'=>IMAGE_EXT])}}
                            <span class="help-text"> Supported File Types: jpg, jpeg, png (Max. {{ MAX_IMAGE_UPLOAD_SIZE_DISPLAY }}).</span>
                            @if($recordInfo->profile_image != '')
                            <div class="showeditimage">{{HTML::image(PROFILE_FULL_DISPLAY_PATH.$recordInfo->profile_image, SITE_TITLE,['style'=>"max-width: 200px"])}}</div>
                            <div class="help-text"><a href="{{ URL::to('admin/agents/deleteimage/'.$recordInfo->slug)}}" title="Delete Image" class="canlcel_le"  onclick="return confirm('Are you sure you want to delete?')">Delete Image</a></div>
                            @endif
                        </div>
                    </div>  
                    
<!--                    <div class="form-group">
                        <label class="col-sm-2 control-label">Upload picture of your national identity <span class="require"></span></label>
                        <div class="col-sm-10">
                            {{Form::file('identity_image', ['class'=>'form-control', 'accept'=>IMAGE_EXT])}}
                            <span class="help-text"> Supported File Types: jpg, jpeg, png (Max. {{ MAX_IMAGE_UPLOAD_SIZE_DISPLAY }}).</span>
                            @if($recordInfo->identity_image != '')
                            <div class="showeditimage">{{HTML::image(IDENTITY_FULL_DISPLAY_PATH.$recordInfo->identity_image, SITE_TITLE,['style'=>"max-width: 200px"])}}</div>
                            <div class="help-text"><a href="{{ URL::to('admin/agents/deleteidentity/'.$recordInfo->slug)}}" title="Delete Image" class="canlcel_le"  onclick="return confirm('Are you sure you want to delete?')">Delete Image</a></div>
                            @endif
                        </div>
                    </div>  -->

                    <div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        {{Form::submit('Submit', ['class' => 'btn btn-info'])}}
                        <a href="{{ URL::to( 'admin/agents')}}" title="Cancel" class="btn btn-default canlcel_le">Cancel</a>
                    </div>
                </div>
            </div>
            {{ Form::close()}}
        </div>
    </section>
    @endsection