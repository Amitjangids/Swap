@extends('layouts.login')
@section('content')
<div class="choose-login">
        <div class="container">
            <div class="row justify-content-center">
                <h3 class="col-sm-12">{{__('message.Choose Account')}}</h3>
                <div class="col-sm-4">
                    <div class="c-log-box grad-one">
                        <a href="{{ URL::to( 'individual-mobile-registration')}}">
                            {{__('message.Individual')}}
                        </a>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="c-log-box grad-one">
                        <a href="{{ URL::to( 'merchant-mobile-registration')}}">
                            {{__('message.Merchant')}}
                        </a>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="c-log-box grad-one">
                        <a href="{{ URL::to( 'agent-mobile-registration')}}">
                            {{__('message.Agent')}}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>



@endsection