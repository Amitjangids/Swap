@extends('layouts.inner')
@section('content')
{{ HTML::script('public/js/facebox.js')}}
{{ HTML::style('public/css/facebox.css')}}
<script type="text/javascript">
    $(document).ready(function ($) {
        $('.close_image').hide();
        $('a[rel*=facebox]').facebox({
            closeImage: '{!! HTTP_PATH !!}/public/img/close.png'
        });
    });
</script>
<div class="container">
    <div class="row">
        <div class="col-sm-5 m-auto">
            <div class="profile-detail">
                <div class="dp-box">
                    @if(isset($recordInfo->profile_image))
                    {{HTML::image(PROFILE_SMALL_DISPLAY_PATH.$recordInfo->profile_image, SITE_TITLE, ['id'=> ''])}}
                    @else
                    {{HTML::image('public/img/front/no_user.png', SITE_TITLE, ['id'=> ''])}}
                    @endif
                </div>
                <h4>{{$recordInfo->name}}</h4>
                <h5>{{$recordInfo->phone}}</h5>
                <span>
                    @if($recordInfo->is_kyc_done == 1)
                    {{HTML::image('public/img/front/check.svg', SITE_TITLE)}} {{__('message.Verified')}}
                    @elseif($recordInfo->is_kyc_done == 2)
                    {{HTML::image('public/img/front/reject.svg', SITE_TITLE)}} {{__('message.Not verified')}}
                    <a href="{{ URL::to( 'kyc-verification')}}" style="color: #d6ae4f;text-decoration: underline;">{{__('message.Verify again?')}}</a>
                    @elseif($recordInfo->is_kyc_done == 3)
                    {{HTML::image('public/img/front/reject.svg', SITE_TITLE)}} {{__('message.Not verified')}}
                    <a href="{{ URL::to( 'kyc-verification')}}" style="color: #d6ae4f;text-decoration: underline;">{{__('message.Verify again?')}}</a>
                    @else                    
                    {{HTML::image('public/img/front/pending.svg', SITE_TITLE)}} {{__('message.Pending')}}
                    @endif
                </span>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="ee er_msg">@include('elements.errorSuccessMessage')</div>
        <div class="col-sm-6">
            <a href="{{ URL::to( 'edit-profile')}}" class="setting-link">
                {{HTML::image('public/img/front/edit-profile.png', SITE_TITLE)}}
                {{__('message.Edit Profile')}}
            </a>
        </div>
        <div class="col-sm-6">
            <a href="{{ URL::to( 'change-password')}}" class="setting-link">
                {{HTML::image('public/img/front/change-password.png', SITE_TITLE)}}
                {{__('message.Change Password')}}
            </a>
        </div>
        <div class="col-sm-6">
            <a href="{{ URL::to( 'transaction-history')}}" class="setting-link">
                {{HTML::image('public/img/front/history.svg', SITE_TITLE)}}
                {{__('message.Transaction History')}}
            </a>
        </div>
        <div class="col-sm-6">
            <a href="{{ URL::to( 'feedback')}}" class="setting-link">
                {{HTML::image('public/img/front/feedback.svg', SITE_TITLE)}}
                {{__('message.Feedback')}}
            </a>
        </div>
        <div class="col-sm-6">
            <a href="{{ URL::to( 'about-us')}}" class="setting-link">
                {{HTML::image('public/img/front/about-us.svg', SITE_TITLE)}}
                {{__('message.About Us')}}
            </a>
        </div>
        <div class="col-sm-6">
            <a href="{{ URL::to( 'notifications')}}" class="setting-link">
                {{HTML::image('public/img/front/notification.svg', SITE_TITLE)}}
                {{__('message.Notification')}}
            </a>
        </div>
        @if($recordInfo->user_type == 'Merchant')
        <div class="col-sm-6">
            <a href="{{ URL::to( 'merchantSetting')}}" class="setting-link">
                {{HTML::image('public/img/front/settings.png', SITE_TITLE)}}
                {{__('message.Applicable Transaction Fee Charges')}}
            </a>
        </div>
        <div class="col-sm-6">
        </div>
        @endif
        <div class="col-sm-6 m-auto">
            <a href="{{ URL::to( 'logout')}}"  onclick="return confirm('<?php echo __("message.Are you sure you want to logout?")?>')" class="btn-grad grad-two logot_btn">{{__('message.Log Out')}}</a>
        </div>
    </div>
</div>
<?php 

        ?>

@endsection