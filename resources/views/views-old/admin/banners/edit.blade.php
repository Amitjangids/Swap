@extends('layouts.admin')
@section('content')
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

        $("#user_type").change(function () {
            var catid = $("#user_type").val();
            $("#category").load('<?php echo HTTP_PATH . '/admin/banners/getcategorylist/' ?>' + catid);


        });
    });
</script>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Edit Banner</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/banners')}}"><i class="fa fa-picture-o"></i> <span>Manage Banners</span></a></li>
            <li class="active"> Edit Banner</li>
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

                    <?php global $userType; ?>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">User Type <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::select('user_type', $userType,null, ['class' => 'form-control required','placeholder' => 'Select User Type','id'=>'user_type'])}}
                        </div>
                    </div>
                    
                    <?php //global $categoryType;?>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Category <span class="require">*</span></label>
                        <div class="col-sm-10" id="category">
                            {{Form::select('category', $categoryType,null, ['class' => 'form-control required','placeholder' => 'Select Category Type'])}}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Banner Name <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('banner_name', null, ['class'=>'form-control required', 'placeholder'=>'Banner Name', 'autocomplete' => 'off'])}}
                        </div>
                    </div>


                    <div class="form-group">
                        <label class="col-sm-2 control-label">Banner Image <span class="require"></span></label>
                        <div class="col-sm-10">
                            {{Form::file('banner_image', ['class'=>'form-control ', 'accept'=>IMAGE_EXT,'onchange'=>'bannerImage(this)','id'=>'bannerimage'])}}
                            <span class="help-text" id="banner"> Supported File Types: jpg, jpeg, png (Max. {{ MAX_IMAGE_UPLOAD_SIZE_DISPLAY }}).Upload 1400*600px files for better resolution in app and web both.</span>
                            @if($recordInfo->banner_image != '')
                            <div class="showeditimage">{{HTML::image(BANNER_FULL_DISPLAY_PATH.$recordInfo->banner_image, SITE_TITLE,['style'=>"max-width: 200px"])}}</div>
                            @endif
                        </div>
                    </div>

                    

                    <div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        {{Form::submit('Submit', ['class' => 'btn btn-info'])}}
                        <a href="{{ URL::to( 'admin/banners')}}" title="Cancel" class="btn btn-default canlcel_le">Cancel</a>
                    </div>
                </div>
            </div>
            {{ Form::close()}}
        </div>
    </section>
</div>
<script>
    function bannerImage(input) 
        {
            $('#banner').html('');	
            var file_name=input.files[0].name;
            var file_size=input.files[0].size; 
            var file_type=input.files[0].type;
            if (file_type != 'image/png' && file_type != 'image/jpeg' && file_type != 'jpeg' && file_type != 'png') 
            {   
                $('#banner').html('Please upload a valid image!');
                $('#bannerimage').val('');
                return false;
            } 
        }
</script>

@endsection