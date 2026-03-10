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
    });
</script>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Edit Card</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/cards')}}"><i class="fa fa-credit-card"></i> <span>Manage Cards</span></a></li>
            <li class="active"> Edit Card</li>
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

                    <?php global $cardType;?>
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Card Type <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::select('card_type', $cardType,null, ['class' => 'form-control required','placeholder' => 'Select Card Type'])}}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Company Name <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('company_name', null, ['class'=>'form-control required', 'placeholder'=>'Company Name', 'autocomplete' => 'off'])}}
                        </div>
                    </div>


                    <div class="form-group">
                        <label class="col-sm-2 control-label">Company Image <span class="require"></span></label>
                        <div class="col-sm-10">
                            {{Form::file('company_image', ['class'=>'form-control ', 'accept'=>IMAGE_EXT,'onchange'=>'companyImage(this)','id'=>'image'])}}
                            <span class="help-text" id="company"> Supported File Types: jpg, jpeg, png (Max. {{ MAX_IMAGE_UPLOAD_SIZE_DISPLAY }}).</span>
                            @if($recordInfo->company_image != '')
                            <div class="showeditimage">{{HTML::image(COMPANY_FULL_DISPLAY_PATH.$recordInfo->company_image, SITE_TITLE,['style'=>"max-width: 200px"])}}</div>
                            @endif
                        </div>
                    </div>

                    

                    <div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        {{Form::submit('Submit', ['class' => 'btn btn-info'])}}
                        <a href="{{ URL::to( 'admin/cards')}}" title="Cancel" class="btn btn-default canlcel_le">Cancel</a>
                    </div>
                </div>
            </div>
            {{ Form::close()}}
        </div>
    </section>
</div>

<script>

function companyImage(input) 
{
	$('#company').html('');	
	var file_name=input.files[0].name;
	var file_size=input.files[0].size; 
	var file_type=input.files[0].type;
	if (file_type != 'image/png' && file_type != 'image/jpeg' && file_type != 'jpeg' && file_type != 'png') 
	{   
		$('#company').html('Please upload a valid image!');
		$('#image').val('');
		return false;
	} 
}

</script>
@endsection