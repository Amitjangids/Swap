@extends('layouts.admin')
@section('content')
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

<script type="text/javascript">
    $(document).ready(function () {
        $.validator.addMethod("alphanumeric", function (value, element) {
            return this.optional(element) || /^\S+$/i.test(value);
        }, "Space not allowed for username.");
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

<div class="content-wrapper">
    <section class="content-header">
        <h1>Add Sub Admin</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/subadmins')}}"><i class="fa fa-user-secret"></i> <span>Manage Sub Admins</span></a></li>
            <li class="active"> Add Sub Admin</li>
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
                        <label class="col-sm-2 control-label">Department <span class="require">*</span></label>
                        <div class="col-sm-10">
                         {{Form::select('role_id', $roleList,null, ['class' => 'form-control required'])}}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Username <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('username', null, ['class'=>'form-control required alphanumeric', 'placeholder'=>'Username', 'autocomplete' => 'off'])}}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Email Address <span class="require"></span></label>
                        <div class="col-sm-10">
                            {{Form::text('email', null, ['class'=>'form-control email', 'placeholder'=>'Email Address', 'autocomplete' => 'off'])}}
                        </div>
                    </div>  

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Password <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::password('password', ['class'=>'form-control required passworreq', 'placeholder' => 'Password', 'minlength' => 8, 'id'=>'password'])}}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Confirm Password <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::password('confirm_password', ['class'=>'form-control required', 'placeholder' => 'Confirm Password', 'equalTo' => '#password'])}}
                        </div>
                    </div>

                    <!-- <div class="form-group">
                        <label class="col-sm-2 control-label">Select Roles <span class="require">*</span></label>
                        <div class="col-sm-10  plennndd role_rr">

                            <div id="size-filter" style="float:left;">
                                <div class="sizes-drop" style="display: none1;" id="sizes-dropdown">
                                    <div class="crooss crsshide"><span onclick="$('#sizes-dropdown').toggle();">X</span></div>
                                    <div class="sizemorescroll">
                                        <div class="cloth_size"> 
                                            <?php global $roles;
                                            foreach($roles as $roleKey => $roleVal){ ?>
                                                <div class="des_box_cont test-size mmbtm">
                                                <div class="main_role"><input class='' type="checkbox" id="StrategyAsset{{$roleKey}}" value="{{$roleKey}}" name="role_ids[]" onclick="checkSubRole({{$roleKey}})"><label>{{$roleVal}}</label></div>
                                                
                                            </div>
                                            <?php }
                                            ?>
                                           <div class="des_box_cont test-size mmbtm">
                                                <div class="main_role"><input type="checkbox" id="StrategyAsset1" value="1" name="role_ids[]" onclick="checkSubRole(1)"><label>Configuration</label></div>
                                                <div class="sub_role">
                                                    <div class="sub_role_c"><input class="sbrole1" type="checkbox" id="sub1_2" value="2" name="data[Admin][sub_role_ids][1][]" onclick="checkSubRoleSub(1, 2)"><label>Edit</label></div>
                                                    <div class="sub_role_c"><input class="sbrole1" type="checkbox" id="sub1_3" value="3" name="data[Admin][sub_role_ids][1][]" onclick="checkSubRoleSub(1, 3)"><label>Delete</label></div>
                                                    <div class="sub_role_c"><input class="sbrole1" type="checkbox" id="sub1_4" value="4" name="data[Admin][sub_role_ids][1][]" onclick="checkSubRoleSub(1, 4)"><label>View Only</label></div>
                                                </div>
                                            </div>
                                            
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </div> -->

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

//        var phone_number = window.intlTelInput(document.querySelector("#phone"), {
//            separateDialCode: true,
//             preferredCountries:false,
//  onlyCountries: ['iq'],
//            hiddenInput: "phone",
//            utilsScript: "<?php echo HTTP_PATH; ?>/public/assets/js/utils.js"
//        });
//
//
//        $("#adminForm").validate(function () {
//            var full_number = phone_number.getNumber(intlTelInputUtils.numberFormat.E164);
//            $("input[name='phone'").val(full_number);
//            alert(full_number)
//
//        });
    </script>
    @endsection




