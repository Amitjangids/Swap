@extends('layouts.admin')
@section('content')


<style>
  .form-group {
    position: relative;
  }

  .toggle-password {
    position: absolute;
    top: 30%;
    right: 20px;
    transform: translateY(-50%);
    cursor: pointer;
	font-size: 18px;
  }


  .form-group {
    position: relative;
  }

  .eyes {
    position: absolute;
    top: 50%;
    right: 10px;
    transform: translateY(-50%);
    cursor: pointer;
	font-size: 18px;
  }
</style>
<script type="text/javascript">
    $(document).ready(function () {
        $.validator.addMethod("passworreq", function (input) {
            var reg = /[0-9]/; //at least one number
            var reg2 = /[a-z]/; //at least one small character
            var reg3 = /[A-Z]/; //at least one capital character
            //var reg4 = /[\W_]/; //at least one special character
            return reg.test(input) && reg2.test(input) && reg3.test(input);
        }, "Password must be at least 8 characters long, contains an upper case letter, a lower case letter, a number and a symbol.");
        $("#adminForm").validate();
    });
</script>
<div class="content-wrapper">
    <section class="content-header">
        <h1>Change Password</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="javascript:void(0);"><i class="fa fa-cogs"></i> Configuration</a></li>
            <li class="active">Change Password</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
            {{ Form::open(array('method' => 'post', 'id' => 'adminForm', 'class' => 'form form-signin')) }}
            <div class="form-horizontal">
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Current Password <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::password('old_password', ['class'=>'form-control required', 'placeholder'=>'Current Password','id'=>'old_password'])}}
                            <span toggle="#old_password" class="fa fa-fw field-icon toggle-password fa-eye"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">New Password <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::password('new_password', ['class'=>'form-control required passworreq', 'placeholder'=>'New Password', 'id'=>'newpassword', 'minlength' => 8])}}
                            <span toggle="#newpassword" class="fa fa-fw field-icon toggle-password fa-eye"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Confirm Password <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::password('confirm_password', ['class'=>'form-control required', 'placeholder'=>'Confirm Password', 'equalTo' => '#newpassword', 'id'=>'confirm_password'])}}
                            <span toggle="#confirm_password" class="fa fa-fw field-icon toggle-password fa-eye"></span>
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

<script type="text/javascript">

	$(".toggle-password").click(function() {



	$(this).toggleClass("fa-eye fa-eye-slash");

	var input = $($(this).attr("toggle"));

	if (input.attr("type") == "password") {

	input.attr("type", "text");

	} else {

	input.attr("type", "password");

	}

	});

</script>
@endsection