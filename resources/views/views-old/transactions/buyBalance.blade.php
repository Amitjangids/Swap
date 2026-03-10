@extends('layouts.inner')
@section('content')
<div class="page-heading">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>
                    {{__('message.Buy Balance')}}
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
                    <div class="col-sm-4">
                        <div class="option-thumb">
                            <a href="{{ URL::to( 'buy-pending-requests')}}">
                                {{HTML::image('public/img/front/recipient-detail.svg', SITE_TITLE)}}
                                <span>{{__('message.Pending Requests')}}</span>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="option-thumb">
                            <a href="{{ URL::to( 'deposit-cash-card')}}">
                                {{HTML::image('public/img/front/cash-card.svg', SITE_TITLE)}}
                                <span>{{__('message.Cash Card')}}</span>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="option-thumb">
                            <a href="{{ URL::to( 'buy-balance-qr')}}">
                                {{HTML::image('public/img/front/buy-balance-qr.svg', SITE_TITLE)}}
                                <span>{{__('message.My QR Code')}}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection