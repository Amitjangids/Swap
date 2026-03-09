@extends('layouts.admin')
@section('content')
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<style>
    div#ui-datepicker-div {
    background: #fff;
    padding: 20px 10px;
    width: 230px;
    overflow: hidden;
    box-shadow: 0 0 10px rgb(0 0 0 / 20%);
    border-radius: 5px;
}
div#ui-datepicker-div table.ui-datepicker-calendar {
    width: 100%;
}
    </style>
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
        
    //     $("#city").change(function () {
    //         var cityid = $("#city").val();
    //         $("#area").load('<?php echo HTTP_PATH . '/admin/users/getarealist/' ?>' + cityid);
    //     });
    // });
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
        <h1>Edit User</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/users')}}"><i class="fa fa-user"></i> <span>Manage Users</span></a></li>
            <li class="active"> Edit User</li>
        </ol>
    </section>
    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <?php //print_r($areaList);?>
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
            {{Form::model($recordInfo, ['method' => 'post', 'id' => 'adminForm', 'enctype' => "multipart/form-data"]) }}            
            <div class="form-horizontal">
                <div class="box-body">
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Full Name <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('name', null, ['class'=>'form-control required', 'placeholder'=>'Full Name', 'autocomplete' => 'off', 'maxlength' => 15])}}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Email Address <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('email', null, ['class'=>'form-control required email', 'placeholder'=>'Email Address', 'autocomplete' => 'off'])}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Date Of Birth <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('dob', null, ['class'=>'form-control required ', 'placeholder'=>'Date Of Birth', 'autocomplete' => 'off','id'=>'dob','readonly'])}}
                        </div>
                    </div>  

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Select ID type:<span class="require">*</span></label>
                        <div class="col-sm-10">
                            <select name="national_identity_type" class="form-control required" autocomplete="off">
                            <?php global $id_type;?>
                             @foreach ($id_type as $key=>$option)
                                    <option value="{{$key }}"@if ($key == $recordInfo->national_identity_type) selected @endif>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                   
                   
                  <div class="form-group">
                        <label class="col-sm-2 control-label">National Identity Number <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('national_identity_number', null, ['class'=>'form-control required', 'placeholder'=>'National Identity Number', 'autocomplete' => 'off',  'minlength' => 10, 'maxlength' => 12])}}
                        </div>
                    </div> 
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">ID expiry date <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('id_expiry_date', null, ['class'=>'form-control required', 'placeholder'=>'ID expiry date', 'autocomplete' => 'off','id'=>'expirydate','readonly'])}}
                        </div>
                    </div> 
                    

                    
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Profile Image <span class="require"></span></label>
                        <div class="col-sm-10">
                            {{Form::file('profile_image', ['class'=>'form-control', 'accept'=>IMAGE_EXT,'onchange'=>'titleImage(this)','id'=>'profileimage'])}}
                            <span class="help-text" id="Profile"> Supported File Types: jpg, jpeg, png (Max. {{ MAX_IMAGE_UPLOAD_SIZE_DISPLAY }}).</span>
                            @if($recordInfo->profile_image != '')
                            <div class="showeditimage">{{HTML::image(PROFILE_FULL_DISPLAY_PATH.$recordInfo->profile_image, SITE_TITLE,['style'=>"max-width: 200px"])}}</div>
                            <div class="help-text"><a href="{{ URL::to('admin/users/deleteimage/'.$recordInfo->slug)}}" title="Delete Image" class="canlcel_le"  onclick="return confirm('Are you sure you want to delete?')">Delete Image</a></div>
                            @endif
                        </div>
                    </div>  
                    
                  <div class="form-group">
                        <label class="col-sm-2 control-label">Upload picture of your national identity <span class="require"></span></label>
                        <div class="col-sm-10">
                            {{Form::file('identity_front_image', ['class'=>'form-control', 'accept'=>IMAGE_EXT,'onchange'=>'frontImage(this)','id'=>'frontimage'])}}
                            <span class="help-text" id="frontimg"> Supported File Types: jpg, jpeg, png (Max. {{ MAX_IMAGE_UPLOAD_SIZE_DISPLAY }}).</span>
                            @if($recordInfo->identity_front_image != '')
                            <div class="showeditimage">{{HTML::image(IDENTITY_FULL_DISPLAY_PATH.$recordInfo->identity_front_image, SITE_TITLE,['style'=>"max-width: 200px"])}}</div>
                            <div class="help-text"><a href="{{ URL::to('admin/users/deleteidentity/'.$recordInfo->slug)}}" title="Delete Image" class="canlcel_le"  onclick="return confirm('Are you sure you want to delete?')">Delete Image</a></div>
                            @endif
                        </div>
                    </div>  


                    <div class="form-group">
                        <label class="col-sm-2 control-label">Upload back picture of your national identity <span class="require"></span></label>
                        <div class="col-sm-10">
                            {{Form::file('identity_back_image', ['class'=>'form-control', 'accept'=>IMAGE_EXT,'onchange'=>'backImage(this)','id'=>'backimage'])}}
                            <span class="help-text" id="backimg"> Supported File Types: jpg, jpeg, png (Max. {{ MAX_IMAGE_UPLOAD_SIZE_DISPLAY }}).</span>
                            @if($recordInfo->identity_back_image != '')
                            <div class="showeditimage">{{HTML::image(IDENTITY_FULL_DISPLAY_PATH.$recordInfo->identity_back_image, SITE_TITLE,['style'=>"max-width: 200px"])}}</div>
                            <div class="help-text"><a href="{{ URL::to('admin/users/deleteidentity1/'.$recordInfo->slug)}}" title="Delete Image" class="canlcel_le"  onclick="return confirm('Are you sure you want to delete?')">Delete Image</a></div>
                            @endif
                        </div>
                    </div>  
                    

                    <div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        {{Form::submit('Submit', ['class' => 'btn btn-info'])}}
                        <a href="{{ URL::to( 'admin/users')}}" title="Cancel" class="btn btn-default canlcel_le">Cancel</a>
                    </div>
                </div>
            </div>
            {{ Form::close()}}
        </div>
    </section>

<script>
        function titleImage(input) 
        {
            $('#Profile').html('');	
            var file_name=input.files[0].name;
            var file_size=input.files[0].size; 
            var file_type=input.files[0].type;
            if (file_type != 'image/png' && file_type != 'image/jpeg' && file_type != 'jpeg' && file_type != 'png') 
            {   
                $('#Profile').html('Please upload a valid image!');
                $('#profileimage').val('');
                return false;
            } 
        }

        function frontImage(input) 
        {
            $('#frontimg').html('');	
            var file_name=input.files[0].name;
            var file_size=input.files[0].size; 
            var file_type=input.files[0].type;
            if (file_type != 'image/png' && file_type != 'image/jpeg' && file_type != 'jpeg' && file_type != 'png') 
            {   
                $('#frontimg').html('Please upload a valid image!');
                $('#frontimage').val('');
                return false;
            } 
        }

        function backImage(input) 
        {
            $('#backimg').html('');	
            var file_name=input.files[0].name;
            var file_size=input.files[0].size; 
            var file_type=input.files[0].type;
            if (file_type != 'image/png' && file_type != 'image/jpeg' && file_type != 'jpeg' && file_type != 'png') 
            {   
                $('#backimg').html('Please upload a valid image!');
                $('#backimage').val('');
                return false;
            } 
        }


        $(function () {
        $("#expirydate").datepicker({
        dateFormat: 'yy-mm-dd',
        minDate: 'today',
        changeMonth: true,
        changeYear: true,
        // yearRange: "+20"
        });
        });
</script>
    @endsection
