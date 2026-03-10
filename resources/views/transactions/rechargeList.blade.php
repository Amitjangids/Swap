@extends('layouts.inner')
@section('content')
<div class="page-heading">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>
                    {{$headTitle}}
                </h2>
            </div>
        </div>
    </div>
</div>
{{ Form::open(array('method' => 'post', 'id' => 'searchform')) }}
<div class="container">
    <div class="row">
        <div class="col-sm-10 m-auto">
            <div class="main-option-thumb-box">
                <div class="row justify-content-center ">
                    @if($allrecords)
                    @foreach($allrecords as $allrecord)
                    <div class="recharge-comp-card">
                        <a href="{{ URL::to( 'card-recharge/'.$allrecord['slug'])}}">
                            {{HTML::image($allrecord['card_image'], SITE_TITLE, ['width'=> '179px'])}}
                        </a>
                    </div>
                    @endforeach
                    @else
                    <div class="container mb-40"><div class="col-sm-12"><div class="no_record">{{__('message.No records found.')}}</div></div></div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

@endsection