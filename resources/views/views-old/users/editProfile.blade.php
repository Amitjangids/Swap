@extends('layouts.inner')
@section('content')
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $("#userform").validate();
    });
</script>
<script>
    $(function () {
        $("#dob").datepicker({
            dateFormat: 'yy-mm-dd',
            maxDate: 'today',
            changeMonth: true,
            changeYear: true,
            yearRange: "-30:+0"
        });
    });
</script>
<div class="page-heading">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>
                    {{__('message.Edit Profile')}}
                </h2>
            </div>
        </div>
    </div>
</div>
<div class="container">
    <div class="row">
        <div class="col-sm-10 m-auto">
            <div class="main-option-thumb-box">
                <div class="row justify-content-center">
                    <div class="form-cards col-sm-6">
                        <div class="ee er_msg">@include('elements.errorSuccessMessage')</div>
                        {{Form::model($recordInfo, ['method' => 'post', 'id' => 'userForm', 'enctype' => "multipart/form-data"]) }}
                        @if($recordInfo->user_type == 'Merchant')
                        <div class="form-group card-form-group">
                            <label>{{__('message.Business Name')}}</label>                          

                            {{Form::text('business_name', null, ['class'=>'form-control required', 'placeholder'=>'Business Name', 'autocomplete'=>'OFF'])}}
                        </div>
                        @endif

                        <div class="form-group card-form-group">
                            @if($recordInfo->user_type == 'Merchant')
                            <label>{{__('message.Business Owner Name')}}</label>
                            @else
                            <label>{{__('message.Full Name')}}</label>
                            @endif

                            {{Form::text('name', null, ['class'=>'form-control required', 'placeholder'=>__('message.Full Name'), 'autocomplete'=>'OFF'])}}
                        </div>
                        <div class="form-group card-form-group">
                            @if($recordInfo->user_type == 'Merchant')
                            <label>{{__('message.Business Email Address')}}</label>
                            @else
                            <label>{{__('message.Email Address')}}</label>
                            @endif

                            {{Form::text('email', null, ['class'=>'form-control email', 'placeholder'=>__('message.Email Address'), 'autocomplete'=>'OFF'])}}
                        </div>
                        <div class="form-group card-form-group">
                            <label>{{__('message.Date Of Birth')}}</label>
                            {{Form::text('dob', null, ['class'=>'form-control required', 'placeholder'=>'Date Of Birth', 'autocomplete' => 'off','id'=>'dob1'])}}
                        </div>
                        <div class="form-group card-form-group">
                            <label>{{__('message.Select City')}}</label>
                            {{Form::select('city', $cityList,$recordInfo->city, ['id'=>'city','class' => 'form-control','placeholder' => __('message.Select City')])}}
                        </div>
                        <div class="form-group card-form-group">
                            <label>{{__('message.Select Area')}}</label>
                            
                            {{Form::select('area', $areaList,$recordInfo->area, ['class' => 'form-control required','placeholder' => __('message.Select Area')])}}
                        </div>
                        <div class="form-group card-form-group">
                            <label>{{__('message.National ID')}}</label>
                            <div class="fg-id">
                             <?php $national_identity_number = $recordInfo->national_identity_number?($recordInfo->national_identity_number):'';?>
                            {{Form::text('national_identity_number', $national_identity_number, ['class'=>'form-control required', 'placeholder'=>'Enter National ID Number', 'autocomplete'=>'OFF'])}}
                        </div>
                        </div>
                        <div class="form-group card-form-group">
                            <label>{{__('message.User Image Upload (Optional)')}}</label>
                            <div class="filform-group">
                                @if($recordInfo->profile_image != '')
                                {{HTML::image(PROFILE_FULL_DISPLAY_PATH.$recordInfo->profile_image, SITE_TITLE,['style'=>"max-width: 100px"])}}
                                @else
                                {{HTML::image('public/img/front/pro-icon.png', SITE_TITLE,['style'=>"max-width: 100px"])}}
                                @endif
                                <div class="file-uploader ">
                                    <label for="file-input" class="grad-one">
                                        {{__('message.Upload')}} <i class="fa fa-folder"></i></label>
                                    {{Form::file('profile_image', ['id'=>'file-input','class'=>'', 'accept'=>IMAGE_EXT])}}
                                </div>
                            </div>

                        </div>
                        <button type="submit" class="btn-grad grad-two btn-one">{{__('message.Update')}}</button>
                        {{ Form::close()}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection