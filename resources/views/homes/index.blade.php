@extends('layouts.login')
@section('content')
<div class="choose-login">
        <div class="container">
            <div class="row justify-content-center">
                <h3 class="col-sm-12">Choose Login</h3>
                <div class="col-sm-4">
                    <div class="c-log-box grad-one">
                        <a href="{{ URL::to( 'individualLogin')}}">
                            Individual
                        </a>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="c-log-box grad-one">
                        <a href="{{ URL::to( 'merchantLogin')}}">
                            Merchant
                        </a>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="c-log-box grad-one">
                        <a href="{{ URL::to( 'agentLogin')}}">
                            Agent
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>



@endsection