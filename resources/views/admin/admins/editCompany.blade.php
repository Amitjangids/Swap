@extends('layouts.admin')
@section('content')

<script type="text/javascript">
    $(document).ready(function () {
        $("#adminForm").validate({
            rules: {
                company_name: {
                    required: true
                },
                company_code: {
                    required: true
                },
                username: {
                    required: true
                },
                phone: {
                    required: true,
                    digits: true // Assuming US phone number format, you may need to adjust for African countries
                },
                password: {
                    minlength: 8
                },
                confirm_password: {
                    equalTo: "#password"
                }
            },
            messages: {
                company_name: {
                    required: "Please enter company name"
                },
                company_code: {
                    required: "Please enter company code"
                },
                username: {
                    required: "Please enter username"
                },
                phone: {
                    required: "Please enter phone number",
                    digits: "Phone number should be in digits"
                },
                password: {
                    minlength: "Password must be at least 8 characters long"
                },
                confirm_password: {
                    equalTo: "Passwords do not match"
                }
            }
        });
    });
</script>


<div class="content-wrapper">
    <section class="content-header">
        <h1>Edit Company</h1>  
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/admins/roles')}}"><i class="fa fa-user"></i> <span>Manage Department</span></a></li>
            <li class="active"> Edit Company</li>
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
                        <label class="col-sm-2 control-label">Company Name <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('company_name', null, ['class'=>'form-control required', 'placeholder'=>'Company Name', 'autocomplete' => 'off'])}}
                        </div>
                    </div> 

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Company Code <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('company_code', null, ['class'=>'form-control required', 'placeholder'=>'Company Code', 'autocomplete' => 'off','readonly'])}}
                        </div>
                    </div> 

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Username <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('username', null, ['class'=>'form-control required alphanumeric', 'placeholder'=>'Username', 'autocomplete' => 'off'])}}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Phone Number <span class="require">*</span></label>
                        <div class="col-sm-10">
                        {{Form::text('phone', null, ['class'=>'form-control required', 'placeholder'=>'Phone Number', 'autocomplete' => 'off'])}}
                        </div>
                    </div>
<!-- 
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Wallet Balance <span class="require">*</span></label>
                        <div class="col-sm-10">
                        {{Form::text('wallet_balance', null, ['class'=>'form-control required', 'placeholder'=>'Wallet Balance', 'autocomplete' => 'off','onkeypress'=>"return validateFloatKeyPress(this,event);"])}}
                        </div>
                    </div> -->

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Email Address <span class="require"></span></label>
                        <div class="col-sm-10">
                            {{Form::text('email', null, ['class'=>'form-control email', 'placeholder'=>'Email Address', 'autocomplete' => 'off'])}}
                        </div>
                    </div>  

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Password</label>
                        <div class="col-sm-10">
                            {{Form::password('password', ['class'=>'form-control', 'placeholder' => 'Password', 'minlength' => 8, 'id'=>'password'])}}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Confirm Password</label>
                        <div class="col-sm-10">
                            {{Form::password('confirm_password', ['class'=>'form-control', 'placeholder' => 'Confirm Password', 'equalTo' => '#password'])}}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Company Address</label>
                        <div class="col-sm-10">
                            {{Form::text('company_address', null, ['class'=>'form-control', 'placeholder' => 'Company Address'])}}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Web Site</label>
                        <div class="col-sm-10">
                            {{Form::url('website', null, ['class'=>'form-control', 'placeholder' => 'Web Site'])}}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Company Logo</label>
                        <div class="col-sm-10">
                        <input id="single_img" type="file" name="profile" onchange="readURL(this)" class="form-control">
                        <p><span class="label-star error" id="image_pera" style="color: red !important";></span></p>
                        @if($recordInfo->profile!="")
                        <img src="{{HTTP_PATH}}//public/assets/company_logo/{{$recordInfo->profile}}" height="100px" width="100px">
                        @endif
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

<script>
function readURL(input) {
if (input.files && input.files[0]) {
    $('#image_pera').html('');
    var file_name=input.files[0].name;
    var file_size=input.files[0].size;
    var file_width=input.files[0];  
    if ( /\.(jpe?g|png|svg)$/i.test(file_name) === false ) 
    {   
        $('#image_pera').html('Please upload jpeg or png image !');
        $('#single_img').val('');
        return false;
    } 
    if(Math.round(file_size/(1024*1024)) > 1){ 
        $('#image_pera').html('Maximum file upload size is less then 1 MB !');
        $('#single_img').val('');
        return false;   
      }
    }
}

function validateFloatKeyPress(el, evt) {
    var charCode = (evt.which) ? evt.which : event.keyCode;
    var number = el.value.split('.');
    if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57)) {
         return false;
     }
     //just one dot
     if (number.length > 1 && charCode == 46) {
         return false;
     }
     //get the carat position
     var caratPos = getSelectionStart(el);
     var dotPos = el.value.indexOf(".");
     if (caratPos > dotPos && dotPos > -1 && (number[1].length > 1)) {
         return false;
     }
     return true;
    }

</script>

@endsection












    