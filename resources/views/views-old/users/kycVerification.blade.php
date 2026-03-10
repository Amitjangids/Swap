@extends('layouts.inner')
@section('content')
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $("#userForm").validate();
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
                    {{__('message.Verification')}}
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
                        <div class="form-group  card-form-group">
                            <label>{{__('message.National ID')}}</label>
                            <div class="fg-id">
                                <?php $national_identity_number = $recordInfo->national_identity_number?Crypt::decryptString($recordInfo->national_identity_number):'';?>
                                {{Form::text('national_identity_number', $national_identity_number, ['class'=>'form-control required', 'placeholder'=>__('message.Enter National ID Number'), 'autocomplete'=>'OFF'])}}
                                {{HTML::image('public/img/front/id.svg', SITE_TITLE)}}
                            </div>
                        </div>
                        @if($recordInfo->user_type == 'Merchant')
                            <div class="form-group card-form-group">
                                <label>{{__('message.Business Registration Number')}}</label>
                                <?php $registration_number = $recordInfo->registration_number?Crypt::decryptString($recordInfo->registration_number):'';?>
                                {{Form::text('registration_number', $registration_number, ['class'=>'form-control required', 'placeholder'=>__('message.Business Registration Number'), 'autocomplete'=>'OFF'])}}
                            </div>
                        @endif
                        
                        <div class="form-group">
                            <label>{{__('message.Upload picture of your national identity')}}</label>
                            <div class="filform-group">
                                {{HTML::image('public/img/front/pro-icon.png', SITE_TITLE)}}
                                <div class="file-uploader ">
                                    <label for="file-input1" class="grad-one">
                                        {{__('message.Browse')}} <i class="fa fa-folder"></i></label>
                                    {{Form::file('identity_image', ['id'=>'file-input1','class'=>'required', 'accept'=>IMAGE_EXT])}}
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn-grad grad-two btn-one">{{__('message.Submit')}}</button>
                        {{ Form::close()}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection