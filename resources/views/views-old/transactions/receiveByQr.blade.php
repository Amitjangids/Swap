@extends('layouts.inner')
@section('content')
<div class="page-heading">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>
                    {{__('message.Receive Money')}}
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
                    <div class="form-cards col-sm-6" id="show_qr">
                        <h4 style="text-align: center;" id="user_name">{{__('message.Scan QR code to pay')}}</h4>
                        <div class="qr-inner" style="padding-left: 70px;">
                            {{HTML::image('public/'.$userInfo->qr_code, SITE_TITLE,['id'=>'qrcode'])}}
                        </div>
                        <h6>{{__('message.Please Ask the sender to scan the  QR Code')}}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection