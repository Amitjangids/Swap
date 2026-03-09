@extends('layouts.inner')
@section('content')
<div class="page-heading">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>
                    {{__('message.Refund Payment')}}
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
                            <a href="{{ URL::to( 'send-refund')}}">
                                {{HTML::image('public/img/front/recipient-detail.svg', SITE_TITLE)}}
                                <span>{{__('message.Recipient Detail')}}</span>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="option-thumb">
                            <a href="{{ URL::to( 'merchant-transaction')}}">
                                {{HTML::image('public/img/front/iqd.svg', SITE_TITLE)}}
                                <span>{{__('message.Payments')}}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection