@extends('layouts.inner')
@section('content')
<div class="page-heading">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>
                    {{__('message.Shop Payment')}}
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
                            <a href="{{ URL::to( 'shop-payment-by-detail')}}">
                                {{HTML::image('public/img/front/cash-card.svg', SITE_TITLE)}}
                                <span>{{__('message.Enter Details')}}</span>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="option-thumb">
                            <a href="{{ URL::to( 'merchant-list')}}">
                                {{HTML::image('public/img/front/agents.svg', SITE_TITLE)}}
                                <span>{{__('message.Merchants')}}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection