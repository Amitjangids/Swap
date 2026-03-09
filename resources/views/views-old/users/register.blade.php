@extends('layouts.login')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
        $("#registerform").validate({ ignore: ":hidden" });
        
        <?php if(count($errors) > 0){ ?>
            $('#head_h').html('Registration');
            $('.form-register').hide();
            <?php if(old('user_type') == 'Radio and Tv Station'){ ?>
                $('#register_tv').show();
                $('#user_type').val('Radio and Tv Station');
//                $('#com_name').show();
//                $('#com_email').show();
            <?php } elseif(old('user_type') == 'Advertising Agency'){ ?>
                $('#register_as').show();
                $('#user_type').val('Advertising Agency');
            <?php } else{ ?>
                $('#register_ad').show();
                $('#user_type').val('Advertiser');
//                $('#label_email').html('Email')
//                $('#email_main').show();
//                $('#com_name').hide();
//                $('#com_email').hide();
            <?php } ?>
        <?php } ?>
        
        $("#step1").click(function () { 
            var user_type = $('#user_type').val();
            $('#head_h').html('Registration');
                $('.form-register').hide();
            if(user_type == 'Radio and Tv Station'){                
                $('#register_tv').show();
//                $('#label_email').html('Company Email')
//                $('#email_main').hide();
//                $('#com_name').show();
//                $('#com_email').show();
            } else if(user_type == 'Advertising Agency'){ 
                $('#register_as').show();
            } else{
                $('#register_ad').show();
//                $('#label_email').html('Email')
//                $('#email_main').show();
//                $('#com_name').hide();
//                $('#com_email').hide();
            }
        });
    });
    
    function showForm(id,value){
        $('#user_type').val(value);
        $('.choose-register-optn').removeClass('active');
        $('#'+id).addClass('active');
    }
    
    
    
        
</script>

<div class="form-main">
    <div class="container">
        <div class="row">
            <div class="col-sm-6 m-auto">
                <div class="form-heading">
                    <h2 id="head_h">Register As</h2>
                    <div class="ee er_msg">@include('elements.errorSuccessMessage')</div>
                </div>
                {{ Form::open(array('method' => 'post', 'id' => 'registerform', 'class' => 'form form-signin')) }}  
                <div class="form-register" id="register_main">
                    <h5>Choose One Registration Option</h5>
                    <div class="form-group">
                        <div class="choose-register-optn active" id="tab-adr" onclick="showForm('tab-adr','Advertiser')">
                            Advertiser
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="choose-register-optn" id="tab-adg" onclick="showForm('tab-adg','Advertising Agency')">
                            Advertising Agency
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="choose-register-optn" id="tab-tv" onclick="showForm('tab-tv','Radio and Tv Station')">
                            Radio and Tv Station
                        </div>
                    </div>
                    <button type="button" class="submit-btn sbtn1" id="step1">Continue</button>
                </div>

                <div class="form-register" id="register_ad" style="display: none;">
                    <div class="form-group">
                        <label>Name</label>
                        {{Form::text('advertiser_name', null, ['class'=>'form-control required alphanumeric', 'placeholder'=>'Name', 'autocomplete'=>'OFF'])}}
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        {{Form::text('advertiser_email', Cookie::get('user_email_address'), ['class'=>'form-control required email', 'placeholder'=>'Email Address', 'autocomplete'=>'OFF'])}}
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        {{Form::password('advertiser_password', ['class'=>'form-control required', 'placeholder' => 'Enter Password', 'minlength' => 8, 'id'=>'password'])}}
                    </div>
                    <p class="text1">By clicking on the registration button. you are aggreging terms and conditions of the platform</p>
                    
                    {{Form::submit('Continue', ['class' => 'submit-btn'])}}
                </div>
                
                <div class="form-register" id="register_as" style="display: none;">
                    <div class="form-group">
                        <label>Name</label>
                        {{Form::text('agency_name', null, ['class'=>'form-control required alphanumeric', 'placeholder'=>'Name', 'autocomplete'=>'OFF'])}}
                    </div>
                    <div class="form-group">
                        <label>Company Name</label>
                        {{Form::text('agency_company_name', null, ['class'=>'form-control required alphanumeric', 'placeholder'=>'Company Name', 'autocomplete'=>'OFF'])}}
                    </div>
                    <div class="form-group">
                        <label>Company Email</label>
                        {{Form::text('agency_email', Cookie::get('user_email_address'), ['class'=>'form-control required email', 'placeholder'=>'Email Address', 'autocomplete'=>'OFF'])}}
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        {{Form::password('agency_password', ['class'=>'form-control required', 'placeholder' => 'Enter Password', 'minlength' => 8, 'id'=>'password'])}}
                    </div>
                    <p class="text1">By clicking on the registration button. you are aggreging terms and conditions of the platform</p>
                    
                    {{Form::submit('Continue', ['class' => 'submit-btn'])}}
                </div>

                <div class="form-register" id="register_tv" style="display: none;">
<!--                    <div class="form-group">
                        <label>Name</label>
                        {{Form::text('station_name', null, ['class'=>'form-control required alphanumeric', 'placeholder'=>'Name', 'autocomplete'=>'OFF'])}}
                    </div>-->

                    <div class="form-group" id="com_name">
                        <label>Business Name</label>
                        {{Form::text('station_business_name', null, ['class'=>'form-control required alphanumeric', 'placeholder'=>'Business Name', 'autocomplete'=>'OFF'])}}
                    </div>                    
                    
                    <div class="form-group" id="com_email">
                        <label>Business Email</label>
                        {{Form::text('station_email', Cookie::get('user_email_address'), ['id'=>'business_email','class'=>'form-control required email', 'placeholder'=>'Business Email', 'autocomplete'=>'OFF'])}}
                    </div>
                    <div class="form-group" id="com_email">
                        <label>Business phone</label>
                        {{Form::text('station_phone', null, ['id'=>'station_phone','class'=>'form-control required digits', 'placeholder'=>'Business Phone', 'autocomplete'=>'OFF', 'minlength' => 8, 'maxlength' => 16])}}
                    </div>
                    <div class="form-group">
                        <label>Country</label>
                        {{Form::select('country', $countrList,null, ['class' => 'form-control required','placeholder' => 'Select Country'])}}
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        {{Form::password('station_password', ['class'=>'form-control required', 'placeholder' => 'Enter Password', 'minlength' => 8, 'id'=>'password'])}}
                    </div>
                    <p class="text1">By clicking on the registration button. you are aggreging terms and conditions of the platform</p>
                    
                    {{Form::submit('Continue', ['class' => 'submit-btn'])}}
                </div>
                
                <input type="hidden" name="user_type" id="user_type" value="Advertiser">
                {{ Form::close()}}

                <div class="form-bottom-text">
                    <p>Do you have already an account ? <a href="{{ URL::to( 'login')}}"> Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection