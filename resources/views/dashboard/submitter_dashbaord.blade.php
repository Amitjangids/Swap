@extends('layouts.home')
@section('content')
<section class="banner-section">

<?php if (session()->has('success_message')) { ?>
        <div class="alert alert-success" role="alert">
            {{Session::get('success_message')}}
        </div>
        <?php Session::forget('success_message'); } ?>

        <?php if (session()->has('error_message')) { ?>
            <div class="alert alert-danger" role="alert">
            {{Session::get('error_message')}}
            </div>
        <?php Session::forget('error_message');   } ?>

        <div class="container">
                <!-- <img src="{{PUBLIC_PATH}}/assets/front/images/banner-bg-img.png" alt="image"> -->
                <div class="banner-content-parent-wrapper">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="banner-content banner-content-parent">
                                <h2>{{__('message.Account Balance')}}</h2>
                                <div class="wallet-parent">
                                    <!-- <img src="{{PUBLIC_PATH}}/assets/front/images/dollar-icon.svg" alt="image"> -->
                                    {{ CURR }}{{ number_format(intval(str_replace(',', '', $wallet_balance)), 0, '.', ',') }}

                                </div>
                                <div class="refresh-btn">
                                    <a href="{{HTTP_PATH}}/submitter-dashboard">{{__('message.Refresh')}} <img src="{{PUBLIC_PATH}}/assets/front/images/refresh-icon.png" alt="image"></a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="banner-content banner-content-parent">
                                <h2>{{__('message.Hold Balance')}}</h2>
                                <div class="wallet-parent">
                                    <!-- <img src="{{PUBLIC_PATH}}/assets/front/images/dollar-icon.svg" alt="image"> -->
                                    <!-- {{ CURR }}&nbsp;{{ intval(str_replace(',', '  ',$holdAmount)) }} -->
                                    {{ CURR }}&nbsp;{{ number_format(intval(str_replace(',', '', $holdAmount)), 0, '.', ',') }}
                                </div>
                                <div class="refresh-btn">
                                    <a href="{{HTTP_PATH}}/submitter-dashboard">{{__('message.Refresh')}} <img src="{{PUBLIC_PATH}}/assets/front/images/refresh-icon.png" alt="image"></a>
                                </div>
                            </div>
                        </div>
               <!-- <div class="col-lg-6">
                   <div class="banner-right-img">
                       <img src="{{PUBLIC_PATH}}/assets/front/images/banner-image.svg" alt="image">
                   </div>
               </div> -->
           </div>
       </div>
   </div>
</section>

<section class="tiles-section-wrapper">
   <div class="container">
       <div class="row">
           <div class="col-lg-4">
               <a href="{{HTTP_PATH}}/operations-month">
                   <div class="small-box bg-green">
                    <div class="inner">
                        <h3>{{$opt_this_month}}%</h3>
                        <p>{{__('message.Number of Operations of the Month')}}</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
           <a href="{{HTTP_PATH}}/number_success">
               <div class="small-box bg-green">
                <div class="inner">
                    <h3>{{$successfull_transactions}}</h3>
                    <p>{{__('message.Number of Success')}}</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-4">
       <a href="{{HTTP_PATH}}/failure-transaction">
           <div class="small-box bg-green">
            <div class="inner">
                <h3>{{$failure_transactions}}</h3>
                <p>{{__('message.Number of Failure Transaction')}}</p>
            </div>
        </div>
    </a>
</div>
<!-- <div class="col-lg-4">
       <a href="{{HTTP_PATH}}/customer-deposits">
           <div class="small-box bg-green">
            <div class="inner">
                <h3>{{$total_deposit}}</h3>
                <p>Movement of customer deposits</p>
            </div>
        </div>
    </a>
</div> -->
<div class="col-lg-4">
       <a href="{{HTTP_PATH}}/transition-history">
           <div class="small-box bg-green">
            <div class="inner">
                <h3>{{$total_fees}}</h3>
                <p>{{__('message.Total Fees')}}</p>
            </div>
        </div>
    </a>
</div>
</div>
</div>
</section>
@endsection