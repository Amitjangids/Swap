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

/* #Profile  {
    color: red;
} */
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
            maxDate: '-18Y',
            changeMonth: true,
            changeYear: true,
            yearRange: "-70:+0"
        });
    });
</script>

{{ HTML::style('public/assets/css/intlTelInput.css?ver=1.3')}}

<div class="content-wrapper">
    <section class="content-header">
        <h1>Add User</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/users')}}"><i class="fa fa-user"></i> <span>Manage Users</span></a></li>
            <li class="active"> Add User</li>
        </ol>
    </section>
    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
            {{ Form::open(array('method' => 'post', 'id' => 'adminForm', 'enctype' => "multipart/form-data")) }}
            <div class="form-horizontal">
                <div class="box-body">

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Full Name <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('name', null, ['class'=>'form-control required', 'placeholder'=>'Full Name', 'autocomplete' => 'off', 'maxlength' => 15,'oninput' => 'validateName(this)'])}}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Email Address <span class="require"></span></label>
                        <div class="col-sm-10">
                            {{Form::text('email', null, ['class'=>'form-control  email', 'placeholder'=>'Email Address', 'autocomplete' => 'off'])}}

                            <p> This field will be optional </p>
                        </div>
                        
                    </div>  

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Phone Number <span class="require">*</span></label>
                        <div class="col-sm-10">
                           
                            {{ Form::text('phone', null, ['id' => 'phone',
                            'class' => 'form-control required digits',
                            'placeholder' => 'Phone Number',
                            'autocomplete' => 'off',
                            'minlength' => '9',
                            'maxlength' => '15' // Maximum length of the phone number
                        ]) }}

                        </div>
                    </div>  
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Date Of Birth <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('dob', null, ['class'=>'form-control required', 'placeholder'=>'Date Of Birth', 'autocomplete' => 'off','id'=>'dob','readonly'])}}
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
    
    {{ HTML::script('public/assets/js/intlTelInput.js')}}

    <script>
    function validateName(input) {
        // Define the allowed pattern (alphanumeric characters and spaces)
        var pattern = /^[a-zA-Z\s]*$/;
        
        // Test the input value against the pattern
        if (!pattern.test(input.value)) {
            // If invalid, remove the last entered character
            input.value = input.value.replace(/[^a-zA-Z\s]/g, '');
        }
    }
</script>
    <script>

// function ProfileImage(input) 
// {
// 	$('#Profile').html('');	
// 	var file_name=input.files[0].name;
// 	var file_size=input.files[0].size; 
// 	var file_type=input.files[0].type;
// 	if (file_type != 'image/png' && file_type != 'image/jpeg' && file_type != 'jpeg' && file_type != 'png') 
// 	{   
// 		$('#Profile').html('Please upload a valid image!');
// 		$('#profileimage').val('');
// 		return false;
// 	} 
// }

// function frontImage(input) 
// {
// 	$('#frontimg').html('');	
// 	var file_name=input.files[0].name;
// 	var file_size=input.files[0].size; 
// 	var file_type=input.files[0].type;
// 	if (file_type != 'image/png' && file_type != 'image/jpeg' && file_type != 'jpeg' && file_type != 'png') 
// 	{   
// 		$('#frontimg').html('Please upload a valid image!');
// 		$('#frontimage').val('');
// 		return false;
// 	} 
// }

// function backImage(input) 
// {
// 	$('#backimg').html('');	
// 	var file_name=input.files[0].name;
// 	var file_size=input.files[0].size; 
// 	var file_type=input.files[0].type;
// 	if (file_type != 'image/png' && file_type != 'image/jpeg' && file_type != 'jpeg' && file_type != 'png') 
// 	{   
// 		$('#backimg').html('Please upload a valid image!');
// 		$('#backimage').val('');
// 		return false;
// 	} 
// }

//        var phone_number = window.intlTelInput(document.querySelector("#phone"), {
//  separateDialCode: true,
//  preferredCountries:false,
//  onlyCountries: ['iq'],
//  hiddenInput: "phone",
//  utilsScript: "<?php echo HTTP_PATH; ?>/public/assets/js/utils.js"
//});
//
//
//$("#adminForm").validate(function () {
//            var full_number = phone_number.getNumber(intlTelInputUtils.numberFormat.E164);
//            $("input[name='phone'").val(full_number);
////            alert(full_number)
//
//        });
    </script>


    <!-- <script>
    $(function () {
        $("#expirydate").datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 'today',
            changeMonth: true,
            changeYear: true,
            // yearRange: "+20"
        });
    });
</script> -->
    @endsection