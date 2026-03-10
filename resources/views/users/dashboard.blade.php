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
<script type="text/javascript">
    function updateBalance() {

        window.location.href = "<?php echo HTTP_PATH; ?>/users/dashboard";
//         $.ajax({
//             type: 'GET',
//             url: "<?php //echo HTTP_PATH; ?>/updateBalance",
//             cache: false,
//             success: function (data) {
//                 $("#wallet_balance").fadeOut(400, function () {
                            
//                     $(this).html('IQD ' + data).fadeIn(400);
//                 });
//                 $("#refesh_btn").fadeOut(400, function () {
//                     $(this).fadeIn(400);
//                 });

//                 window.location.href = "<?php echo HTTP_PATH; ?>/users/dashboard";
// //                $('#wallet_balance').html('IQD ' + data);
//             }
//         });
    }
</script>
<style>
    sup{
        font-size: 53%;
    }
    .disable {
        background-color: #e1e1e1;
    }
</style>
<div class="home-card-block">
    <div class="container">
        <div class="row">
            <div class="col-sm-4">
                <div class="pro-card grad-three">
                    <div class="refresh" id="refesh_btn" onclick="updateBalance();">
                        {{HTML::image('public/img/front/refresh.svg', SITE_TITLE)}}
                    </div>
                    <div class="pro-pic-box">
                        <div class="pro-card-pic">
                            @if(isset($recordInfo->profile_image) && !empty($recordInfo->profile_image))
                            {{HTML::image(PROFILE_SMALL_DISPLAY_PATH.$recordInfo->profile_image, SITE_TITLE, ['id'=> ''])}}
                            @else
                            {{HTML::image('public/img/front/no_user.png', SITE_TITLE, ['id'=> ''])}}
                            @endif
                        </div>
                        <h4>{{$recordInfo->name}}</h4>
                    </div>
                    <div class="banlance-card">
                        <h6>{{__('message.Current Balance')}}</h6>
                        <h5 id="wallet_balance">IQD 
                            <?php
                            $completeNumber = number_format($recordInfo->wallet_balance, 2);
//                            $newstring = substr($completeNumber, -3);
                            echo substr($completeNumber, 0, -3);
                            echo '<sup>' . substr($completeNumber, -3) . '</sup>';
                            ?>
                        </h5>
                    </div>
                    <div class="add-icon">
                        <?php
                        if (in_array('Deposit', $featureArr)) {
                            $link = 'javascript:void(0);';
                        } else {
                            $link = URL::to('deposit');
                        }
                        ?>
                        <a href="{{ $link}}">{{HTML::image('public/img/front/add.svg', SITE_TITLE)}}</a>
                    </div>
                </div>
            </div>
            <div class="col-sm-4 desktop_set">
                <div class="ticket-top-home ">
                    <a href="{{ URL::to( 'users/nearByMerchant')}}">
                        <div class="namebox-top-ticket">
                            <h6>{{__('message.Nearest Merchants')}}</h6>
                            <div class="arow-rt">
                                {{HTML::image('public/img/front/arrow-rt.svg', SITE_TITLE)}}
                            </div>
                        </div>
                        {{HTML::image('public/img/front/merchent.png', SITE_TITLE)}}
                    </a>
                </div>
            </div>
            <div class="col-sm-4 desktop_set">
                <div class="ticket-top-home">
                    <a href="{{ URL::to( 'users/nearByAgent')}}">
                        <div class="namebox-top-ticket">
                            <h6>{{__('message.Nearest Agents')}}</h6>
                            <div class="arow-rt">
                                {{HTML::image('public/img/front/arrow-rt.svg', SITE_TITLE)}}
                            </div>
                        </div>
                        {{HTML::image('public/img/front/agents.png', SITE_TITLE)}}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="category">
    <div class="container">
        <div class="row">
            <h3 class="col-sm-12 section-head">{{__('message.Categories')}}</h3>
            @if($recordInfo->user_type == 'Agent')
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Buy Balance', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('buy-balance');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/deposit.svg', SITE_TITLE)}}
                    <span>{{__('message.Buy Balance')}}</span>
                </a>
            </div>
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Sell Balance', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('sell-balance');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/withdraw.svg', SITE_TITLE)}}
                    <span>{{__('message.Sell Balance')}}</span>
                </a>
            </div>
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Cash Card', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('cash-card');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/cashcard.svg', SITE_TITLE)}}
                    <span>{{__('message.Cash Card')}}</span>
                </a>
            </div>
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Mobile Recharge', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('mobile-recharge');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/mobile-recharge.svg', SITE_TITLE)}}
                    <span>{{__('message.Mobile Recharge')}}</span>
                </a>
            </div>

            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Internet Recharge', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('internet-recharge');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/internet-recharge.svg', SITE_TITLE)}}
                    <span>{{__('message.Internet Recharge')}}</span>
                </a>
            </div>
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Online Card', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('online-card');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/online-card.svg', SITE_TITLE)}}
                    <span>{{__('message.Online Card')}}</span>
                </a>
            </div>
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Transactions', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('transaction-history');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/transaction.svg', SITE_TITLE)}}
                    <span>{{__('message.Transactions')}}</span>
                </a>
            </div>
            @elseif($recordInfo->user_type == 'Merchant')

            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Receive Money', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('receive-by-qr');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/receive-money.svg', SITE_TITLE)}}
                    <span>{{__('message.Receive Money')}}</span>
                </a>
            </div>
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Refund Payment', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('refund');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/refund-payment.svg', SITE_TITLE)}}
                    <span>{{__('message.Refund Payment')}}</span>
                </a>
            </div>
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Withdraw', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('withdraw');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/withdraw.svg', SITE_TITLE)}}
                    <span>{{__('message.Withdraw')}}</span>
                </a>
            </div>
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Mobile Recharge', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('mobile-recharge');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/mobile-recharge.svg', SITE_TITLE)}}
                    <span>{{__('message.Mobile Recharge')}}</span>
                </a>
            </div>

            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Internet Recharge', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('internet-recharge');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/internet-recharge.svg', SITE_TITLE)}}
                    <span>{{__('message.Internet Recharge')}}</span>
                </a>
            </div>
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Online Card', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('online-card');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/online-card.svg', SITE_TITLE)}}
                    <span>{{__('message.Online Card')}}</span>
                </a>
            </div>
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Transactions', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('transaction-history');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/transaction.svg', SITE_TITLE)}}
                    <span>{{__('message.Transactions')}}</span>
                </a>
            </div>
            @if($recordInfo->api_enable == 'Y')
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Request', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('withdrawal-request');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/request.svg', SITE_TITLE)}}
                    <span>{{__('message.Withdrawal Request')}}</span>
                </a>
            </div>
            @endif
            @else
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Deposit', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('deposit');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/deposit.svg', SITE_TITLE)}}
                    <span>{{__('message.Deposit')}}</span>
                </a>
            </div>
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Withdraw', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('withdraw');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/withdraw.svg', SITE_TITLE)}}
                    <span>{{__('message.Withdraw')}}</span>
                </a>
            </div>
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Send Money', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('send-money');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/send-money.svg', SITE_TITLE)}}
                    <span>{{__('message.Send Money')}}</span>
                </a>
            </div>
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Receive Money', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('receive-money');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/receive-money.svg', SITE_TITLE)}}
                    <span>{{__('message.Receive Money')}}</span>
                </a>
            </div>
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Mobile Recharge', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('mobile-recharge');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/mobile-recharge.svg', SITE_TITLE)}}
                    <span>{{__('message.Mobile Recharge')}}</span>
                </a>
            </div>
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Shop Payment', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('shop-payment');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/online-shopping.svg', SITE_TITLE)}}
                    <span>{{__('message.Shop Payment')}}</span>
                </a>
            </div>

            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Internet Recharge', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('internet-recharge');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/internet-recharge.svg', SITE_TITLE)}}
                    <span>{{__('message.Internet Recharge')}}</span>
                </a>
            </div>
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Online Card', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('online-card');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/online-card.svg', SITE_TITLE)}}
                    <span>{{__('message.Online Card')}}</span>
                </a>
            </div>
            <div class="cat-box col-sm-2">
                <?php
                if (in_array('Transactions', $featureArr)) {
                    $link = 'javascript:void(0);';
                    $class = 'disable';
                } else {
                    $link = URL::to('transaction-history');
                    $class = '';
                }
                ?>
                <a class="{{$class}}" href="{{ $link }}">
                    {{HTML::image('public/img/front/transaction.svg', SITE_TITLE)}}
                    <span>{{__('message.Transactions')}}</span>
                </a>
            </div>

            <div class="cat-box col-sm-2">
                
                <a class="" href="javascript:void(0);">
                    {{HTML::image('public/img/front/travel.svg', SITE_TITLE)}}
                    <span>{{__('message.Travel')}}</span>
                </a>
                <div class="overlay-soon"><span class="comming_soon">{{__('message.Coming soon')}}</span></div>
            </div>

            <div class="cat-box col-sm-2">
                
                <a class="" href="javascript:void(0);">
                    {{HTML::image('public/img/front/buy-online.svg', SITE_TITLE)}}
                    <span>{{__('message.Buy Online')}}</span>
                </a>
                <div class="overlay-soon"><span class="comming_soon">{{__('message.Coming soon')}}</span></div>
            </div>
            <!-- <div class="cat-box col-sm-2 comming">
                {{HTML::image('public/img/front/online-shop.png', SITE_TITLE)}}
            </div>
            <div class="cat-box col-sm-2 comming">
                {{HTML::image('public/img/front/travel.png', SITE_TITLE)}}
            </div> -->
            <!--            <div class="cat-box col-sm-2 comming">
                            {{HTML::image('public/img/front/travel.png', SITE_TITLE)}}
                        </div>-->
            @endif
        </div>
    </div>
</div>
<div class="container mob-view">
    <div class="row">
        <div class="col-sm-6 slide_cls">
            <div class="slider">
                <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
                    <ol class="carousel-indicators">
                        @if(!empty($bannerArr))
                        @php $i=0;@endphp
                        @foreach($bannerArr as $banner)

                        <?php
                        if (in_array($banner->category, $featureArr)) {
                            
                        } else {
                            ?>
                            <li data-target="#carouselExampleIndicators" data-slide-to="{{$i}}" class="<?php echo $i == 0 ? 'active' : ''; ?>"></li>
                        <?php }
                        ?>

                        @php $i++;@endphp
                        @endforeach
                        @endif
                    </ol>
                    <div class="carousel-inner">
                        @if(!empty($bannerArr))
                        @php $i=0;@endphp
                        @foreach($bannerArr as $banner)
                        <?php
                        if (in_array($banner->category, $featureArr)) {
                            
                        } else {
                            ?>
                            <div class="carousel-item <?php echo $i == 0 ? 'active' : ''; ?>">
                                <?php
                                if ($banner->category == 'Internet Recharge') {
                                    $url = '/internet-recharge';
                                } elseif ($banner->category == 'Mobile Recharge') {
                                    $url = '/mobile-recharge';
                                } elseif ($banner->category == 'Online Card') {
                                    $url = '/online-card';
                                } elseif ($banner->category == 'Deposit') {
                                    $url = '/deposit';
                                } elseif ($banner->category == 'Online Shopping') {
                                    $url = '/shop-payment';
                                } elseif ($banner->category == 'Transactions') {
                                    $url = '/transaction-history';
                                } elseif ($banner->category == 'Withdraw') {
                                    $url = '/withdraw';
                                } elseif ($banner->category == 'Send Money') {
                                    $url = '/send-money';
                                } elseif ($banner->category == 'Receive Money') {
                                    $url = '/receive-money';
                                } elseif ($banner->category == 'Shop Payment') {
                                    $url = '/shop-payment';
                                }
                                ?>
                                <a href="<?php echo HTTP_PATH . $url ?>">{{HTML::image(BANNER_FULL_DISPLAY_PATH . $banner->banner_image, $banner->banner_name,['class'=>'d-block w-100'])}}</a>
                            </div>
                        <?php }
                        ?>


                        @php $i++;@endphp
                        @endforeach
                        @endif
                    </div>

                </div>
            </div>
        </div>

        <div class="col-sm-6 p-left tran_clss"> 
            <h3 class="section-head trans-head">{{__('message.Transaction History')}} <a href="{{ URL::to( 'transaction-history')}}">{{__('message.View All')}}</a></h3>
            <?php $userInfo = $recordInfo;?>
            @if(!empty($transactions))
            @foreach($transactions as $allrecord)
            <?php
            global $tranType;
            $spayment_mode = $allrecord->payment_mode;
$transArr = array();
$user_id = Session::get('user_id');
if ($allrecord->receiver_id == 0) {
    $transArr['trans_from'] = $allrecord->payment_mode;
    $transArr['sender'] = $allrecord->User->name;
    $transArr['sender_id'] = $allrecord->user_id;
    $transArr['sender_phone'] = $allrecord->User->phone;
    $transArr['receiver'] = 'Admin';
    $transArr['receiver_id'] = $allrecord->receiver_id;
    $transArr['receiver_phone'] = 0;
    $transArr['trans_type'] = $tranType[$allrecord->trans_type]; //1=Credit;2=Debit;3=topup
} elseif ($allrecord->user_id == $user_id) { //User is sender
    $transArr['trans_from'] = $allrecord->payment_mode;
    $transArr['sender'] = $allrecord->User->name;
    $transArr['sender_id'] = $allrecord->user_id;
    $transArr['sender_phone'] = $allrecord->User->phone;
    $transArr['receiver'] = $allrecord->Receiver->name;
    $transArr['receiver_id'] = $allrecord->receiver_id;
    $transArr['receiver_phone'] = $allrecord->Receiver->phone;
    $transArr['trans_type'] = $tranType[$allrecord->trans_type]; //1=Credit;2=Debit;3=topup

    if ($allrecord->payment_mode == 'Send Money' || $allrecord->payment_mode == 'Shop Payment' || $allrecord->payment_mode == 'Online Shopping' || $allrecord->payment_mode == 'Merchant Withdraw') {
        $allrecord->payment_mode = 'wallet2wallet'; //1=Credit;2=Debit;3=topup
        $transArr['payment_mode'] = $allrecord->payment_mode;
        $transArr['trans_from'] = $allrecord->payment_mode;
    }

    if ($allrecord->payment_mode != 'Cash card') {
        if ($allrecord->trans_type == 2) {
            $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
        } else {
            $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
        }
    }

    if ($allrecord->payment_mode == 'Agent Deposit') {
                                $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                                $transArr['receiver'] = $allrecord->User->name;
                                $transArr['receiver_id'] = $allrecord->user_id;
                                $transArr['receiver_phone'] = $allrecord->User->phone;
                                $transArr['sender'] = $allrecord->Receiver->name;
                                $transArr['sender_id'] = $allrecord->receiver_id;
                                $transArr['sender_phone'] = $allrecord->Receiver->phone;
                            }

    if ($allrecord->payment_mode == 'Refund' && $allrecord->trans_type == 1) {
        $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
        $transArr['receiver'] = $allrecord->User->name;
        $transArr['receiver_id'] = $allrecord->user_id;
        $transArr['receiver_phone'] = $allrecord->User->phone;
        $transArr['sender'] = $allrecord->Receiver->name;
        $transArr['sender_id'] = $allrecord->receiver_id;
        $transArr['sender_phone'] = $allrecord->Receiver->phone;
    }
    if ($allrecord->payment_mode == 'wallet2wallet' && $allrecord->trans_type == 2) {
        $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
    }

    if ($allrecord->payment_mode == 'Withdraw') {
                                $transArr['receiver'] = $allrecord->User->name;
        $transArr['receiver_id'] = $allrecord->user_id;
        $transArr['receiver_phone'] = $allrecord->User->phone;
        $transArr['sender'] = $allrecord->Receiver->name;
        $transArr['sender_id'] = $allrecord->receiver_id;
        $transArr['sender_phone'] = $allrecord->Receiver->phone;
                                $transArr['trans_type'] = $tranType[$allrecord->trans_type]; //1=Credit;2=Debit;3=topup
                            }
} else if ($allrecord->receiver_id == $user_id) { //USer is Receiver
    $transArr['trans_from'] = $allrecord->payment_mode;
    $transArr['sender'] = $allrecord->User->name;
    $transArr['sender_id'] = $allrecord->user_id;
    $transArr['sender_phone'] = $allrecord->User->phone;
    $transArr['receiver'] = $allrecord->Receiver->name;
    $transArr['receiver_id'] = $allrecord->receiver_id;
    $transArr['receiver_phone'] = $allrecord->Receiver->phone;
    $transArr['trans_type'] = $tranType[$allrecord->trans_type]; //1=Credit;2=Debit;3=topup

    if ($allrecord->trans_type == 2) {
                                $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                            }

    if ($allrecord->payment_mode == 'Send Money' || $allrecord->payment_mode == 'Shop Payment' || $allrecord->payment_mode == 'Online Shopping') {
        $allrecord->payment_mode = 'wallet2wallet'; //1=Credit;2=Debit;3=topup
    }

    if ($allrecord->payment_mode == 'Withdraw' && $allrecord->trans_type == 2) {
                                $transArr['receiver'] = $allrecord->User->name;
                                $transArr['receiver_id'] = $allrecord->user_id;
                                $transArr['receiver_phone'] = $allrecord->User->phone;
                                $transArr['sender'] = $allrecord->Receiver->name;
                                $transArr['sender_id'] = $allrecord->receiver_id;
                                $transArr['sender_phone'] = $allrecord->Receiver->phone;
                                $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                            }



                            if ($allrecord->payment_mode == 'Refund' && $allrecord->trans_type == 1) {
                                $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                            }
                            if ($userInfo->user_type != 'Merchant') {
                                if ($allrecord->payment_mode == 'Refund' && $transArr['trans_type'] == 'Debit') {
                                    $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                                }
                            } else {
                                if ($allrecord->payment_mode == 'Refund' && $allrecord->trans_type == 1) {
                                    $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
//                                    $transArr['receiver'] = $this->getUserNameById($val->user_id);
//                                    $transArr['receiver_id'] = $val->user_id;
//                                    $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
//                                    $transArr['sender'] = $this->getUserNameById($val->receiver_id);
//                                    $transArr['sender_id'] = $val->receiver_id;
//                                    $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
                                }
                                if ($allrecord->payment_mode == 'Refund' && $allrecord->trans_type == 1 && $allrecord->refund_status == 0) {
                                    $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                                }
                            }

                            if ($allrecord->payment_mode == 'Agent Deposit') {
                                if($allrecord->trans_type != 4){
                                    $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                                } else{
                                    $transArr['trans_type'] = $tranType[4]; //1=Credit;2=Debit;3=topup
                                }
                                $transArr['receiver'] = $allrecord->User->name;
                                $transArr['receiver_id'] = $allrecord->user_id;
                                $transArr['receiver_phone'] = $allrecord->User->phone;
                                $transArr['sender'] = $allrecord->Receiver->name;
                                $transArr['sender_id'] = $allrecord->receiver_id;
                                $transArr['sender_phone'] = $allrecord->Receiver->phone;
                            }
}


if($spayment_mode=="Merchant Withdraw" || $spayment_mode=="Online Shopping"){


    if ($userInfo->user_type != 'Individual') {
        if ($transArr['trans_type'] == 'Credit') {
            if ($allrecord->payment_mode == "Withdraw") {
                $description = $allrecord->payment_mode;
            } else {
                $description = $userInfo->user_type == 'Merchant' ? $transArr['sender_phone'] : $transArr['receiver_phone'];
            }
    
            if ($userInfo->user_type == 'Merchant') {
                if ($allrecord->payment_mode == 'Refund') {
                   $tranTitle = __('message.Refund from') . " " . $transArr['sender'];
                    $amount = '+ IQD ' . number_format($allrecord->total_amount, 2);
                } else {
                    $tranTitle = __('message.Received from') . " " . $transArr['sender'];
    
    
                    $remain=$allrecord->total_amount-$allrecord->transaction_amount;
                    $plus=$allrecord->total_amount;
        
                    if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Agent') {
                        $amount = '+ IQD ' . number_format($remain, 2);
                    }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Agent') {
                        $amount = '+ IQD ' . number_format($plus, 2);
                       }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Individual') {
                        $amount = '+ IQD ' . number_format($remain, 2);
                    }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Individual') {
                        $amount = '+ IQD ' . number_format($plus, 2);
                       }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Merchant') {
                       
                        if($spayment_mode=="Merchant Withdraw"){
                       
                        if($allrecord->receiver_id==$userInfo->id){
    
                            $amount = '+ IQD ' . number_format($plus, 2);
                        }else{
            
                            $amount = '+ IQD ' . number_format($plus, 2);
                        }
                    }else{
    
                        $amount = '+ IQD ' . number_format($remain, 2);
    
    
                    }
    
                       }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Merchant') {
                        if($spayment_mode=="Merchant Withdraw"){
                        $amount = '+ IQD ' . number_format($remain, 2);
    
                        }else{
    
                            $amount = '+ IQD ' . number_format($plus, 2);
    
                        }
                       }else{
         
                        $amount = '+ IQD ' . number_format($allrecord->total_amount, 2);
                    }
    
    
                   // $amount = '+ IQD ' . number_format($allrecord->amount, 2);
                }
            } else {
                $tranTitle = __('message.Received from') . " " . $transArr['sender'];
                $remain=$allrecord->total_amount-$allrecord->transaction_amount;
                $plus=$allrecord->total_amount;
    
                if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Agent') {
                    $amount = '+ IQD ' . number_format($remain, 2);
                }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Agent') {
                    $amount = '+ IQD ' . number_format($plus, 2);
                   }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Individual') {
                    $amount = '+ IQD ' . number_format($remain, 2);
                }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Individual') {
                    $amount = '+ IQD ' . number_format($plus, 2);
                   }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Merchant') {
                    $amount = '+ IQD ' . number_format($plus, 2);
                   }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Merchant') {
                    if($spayment_mode=="Merchant Withdraw"){
                        $amount = '+ IQD ' . number_format($remain, 2);
    
                        }else{
    
                            $amount = '+ IQD ' . number_format($plus, 2);
    
                        }
                   }else{
     
                    $amount = '+ IQD ' . number_format($allrecord->total_amount, 2);
                }
    
            }
        } elseif ($transArr['trans_type'] == 'Topup') {
            $tranTitle = __('message.Money Added To Wallet');
            $description = $allrecord->payment_mode;
            $amount = '+ IQD ' . number_format($allrecord->amount, 2);
        } elseif ($transArr['trans_type'] == 'Request') {
            if($allrecord->payment_mode == 'Withdraw'){
                $tranTitle = __('message.Money Requested From') . " " . $transArr['sender'];
                $description = $allrecord->payment_mode;
            } else{
                $tranTitle = __('message.Deposit to') . " " . $transArr['receiver'];
                $description = $transArr['receiver_phone'];
            }
            if ($userInfo->user_type == 'Merchant') {
                if ($allrecord->payment_mode == 'Withdraw') {
                    $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                } else {
                    $amount = '+ IQD ' . number_format($allrecord->amount, 2);
                }
            } else {
                if ($allrecord->payment_mode == 'Withdraw') {
                    $amount = '+ IQD ' . number_format($allrecord->amount, 2);
                } else {
                    $amount = '- IQD ' . number_format($allrecord->amount, 2);
                }
            }
        } else {
            if ($allrecord->payment_mode == 'wallet2wallet') {
                $tranTitle = __('message.Paid to') . " " . $transArr['receiver'];
                $description = $transArr['receiver_phone'];
                $remain=$allrecord->total_amount;
                $plus=$allrecord->total_amount+$allrecord->transaction_amount;
    
                if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Merchant') {
    
                    if($spayment_mode=="Merchant Withdraw"){
                 $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
    
                    }else{
    
                        $amount = '- IQD ' . number_format($plus, 2);
    
                    }
                }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Merchant') {
                    if($spayment_mode=="Merchant Withdraw"){
                        $amount = '- IQD ' . number_format($plus, 2);
        
                        }else{
        
                            $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
        
                        }
                   }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Individual') {
                    $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                   }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Agent') {
                    $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                   }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Individual') {
                    $amount = '- IQD ' . number_format($plus, 2);
                   }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Agent') {
                    $amount = '- IQD ' . number_format($plus, 2);
                   }else{
     
                 $amount = '- IQD ' . number_format($remain, 2);
                }
            } else {
                if ($allrecord->payment_mode == "Withdraw") {
                    if ($userInfo->user_type == 'Merchant') {
                        $tranTitle = __('message.Withdraw from') . " " . $transArr['sender'];
                    $description = $transArr['sender_phone'];
                    $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                    } else{
                        $tranTitle = __('message.Withdraw by') . " " . $transArr['receiver'];
                    $description = $transArr['receiver_phone'];
                    $amount = '+ IQD ' . number_format($allrecord->amount, 2);
                    }
                } else {
                    if ($allrecord->payment_mode == "Refund") {
                        $tranTitle = __('message.Refund to') . " " . $transArr['sender'];
                        $description = $transArr['sender_phone'];
                        $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                    } else {
                        if ($userInfo->user_type == 'Merchant') {
                            if ($allrecord->payment_mode == "Agent Deposit") {
                                $tranTitle = __('message.Deposited to') . " " . $transArr['receiver'];
                            } elseif ($allrecord->payment_mode == "Withdraw") {
                                $tranTitle = __('message.Withdraw from') . " " . $transArr['sender'];
                            } else {
                                $tranTitle = $allrecord->payment_mode;
                                
                            }
    
                            if ($transArr['receiver_phone'] == "0") {
                                $description = $transArr['sender_phone'];
                            }
                            if ($allrecord->payment_mode != "Withdraw") {
                                $description = $transArr['sender_phone'];
                            } else {
                                $description = $transArr['receiver_phone'];
                            }
    
    $amount = '- IQD ' . number_format($allrecord->amount, 2);
                        } else {
                            if ($allrecord->status != 'Success') {
                                $depositText = __('message.Deposited to');
                            } else {
                                $depositText = __('message.Deposited to');
                            }
    
                            if ($allrecord->payment_mode == "Agent Deposit") {
                                $tranTitle = $depositText . ' ' . $transArr['receiver'];
                            } elseif ($allrecord->payment_mode == "Withdraw") {
                                $tranTitle = __('message.Withdraw by') . " " . $transArr['receiver'];
                            } else {
                                $tranTitle = __('message.' . $allrecord->payment_mode . '');
                            }
    
                            if ($transArr['receiver_phone'] == "0") {
                                $description = $transArr['sender_phone'];
                            } else {
                                $description = $transArr['receiver_phone'];
                            }
    
                            if ($allrecord->payment_mode == "Agent Deposit") {
                                $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                            } elseif ($allrecord->payment_mode == "Withdraw") {
                                $amount = '+ IQD ' . number_format($allrecord->amount, 2);
                            } else {
                                $amount = '- IQD ' . number_format($allrecord->amount, 2);
                            }
                        }
                    }
                }
            }
        }
    } else {
        if ($transArr['trans_type'] == 'Credit') {
            $tranTitle = $allrecord->payment_mode == "Refund" ? __('message.Refund from') . " " . $transArr['sender'] : __('message.Received from') . " " . $transArr['sender'];
            $description = $allrecord->payment_mode == "Agent Deposit" ? $allrecord->payment_mode : $transArr['sender_phone'];
            $amount = '+ IQD ' . number_format($allrecord->amount, 2);
    
            $remain=$allrecord->total_amount-$allrecord->transaction_amount;
            $plus=$allrecord->total_amount;
    
            if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Agent') {
                $amount = '+ IQD ' . number_format($remain, 2);
            }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Agent') {
                $amount = '+ IQD ' . number_format($plus, 2);
               }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Individual') {
                $amount = '+ IQD ' . number_format($remain, 2);
            }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Individual') {
                $amount = '+ IQD ' . number_format($plus, 2);
               }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Merchant') {
                   
                $amount = '+ IQD ' . number_format($remain, 2);
               }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Merchant') {
                if($spayment_mode=="Merchant Withdraw"){
                    $amount = '+ IQD ' . number_format($remain, 2);
    
                    }else{
    
                        $amount = '+ IQD ' . number_format($plus, 2);
    
                    }
               }
    
        } elseif ($transArr['trans_type'] == 'Topup') {
            $tranTitle = __('message.Money Added To Wallet');
            $description = __('message.' . $allrecord->payment_mode . '');
            $amount = '+ IQD ' . number_format($allrecord->amount, 2);
        } elseif ($transArr['trans_type'] == 'Request') {
            $tranTitle = __('message.Money Requested From') . " " . $transArr['sender'];
            $description = $allrecord->payment_mode;
            $amount = $allrecord->payment_mode == "Withdraw" ? "- IQD " . number_format($allrecord->total_amount, 2) : "+ IQD " . number_format($allrecord->amount, 2);
        } else {
            if ($allrecord->payment_mode == 'wallet2wallet') {
                $tranTitle = __('message.Paid to') . " " . $transArr['receiver'];
                $description = $transArr['receiver_phone'];
    
    
                $remain=$allrecord->total_amount-$allrecord->transaction_amount;
                $plus=$allrecord->total_amount+$allrecord->transaction_amount;
    
                if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Merchant') {
                    if($spayment_mode=="Merchant Withdraw"){
                        $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
           
                           }else{
           
                               $amount = '- IQD ' . number_format($plus, 2);
           
                           }
                }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Merchant') {
                    if($spayment_mode=="Merchant Withdraw"){
                        $amount = '- IQD ' . number_format($plus, 2);
        
                        }else{
        
                            $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
        
                        }
                   }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Individual') {
                    $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                   }else if ($allrecord->fee_pay_by == 'Merchant' && $userInfo->user_type == 'Agent') {
                    $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                   }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Individual') {
                    $amount = '- IQD ' . number_format($plus, 2);
                   }else if ($allrecord->fee_pay_by == 'User' && $userInfo->user_type == 'Agent') {
                    $amount = '- IQD ' . number_format($plus, 2);
                   }else{
     
                 $amount = '- IQD ' . number_format($remain, 2);
                }
                 
            } else {
                if ($allrecord->payment_mode == "Withdraw") {
                    $tranTitle = __('message.Withdraw from') . " " . $transArr['sender'];
                    $description = $transArr['sender_phone'];
                    $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                } else {
                    $tranTitle = __('message.' . $allrecord->payment_mode . '');
                    $description = $transArr['sender_phone'];
                    $amount = '- IQD ' . number_format($allrecord->amount, 2);
                }
            }
        }
    }


}else{
if ($userInfo->user_type != 'Individual') {
    if ($transArr['trans_type'] == 'Credit') {
        if ($allrecord->payment_mode == "Withdraw") {
            $description = $allrecord->payment_mode;
        } else {
            $description = $userInfo->user_type == 'Merchant' ? $transArr['sender_phone'] : $transArr['receiver_phone'];
        }

        if ($userInfo->user_type == 'Merchant') {
            if ($allrecord->payment_mode == 'Refund') {
               $tranTitle = __('message.Refund from') . " " . $transArr['sender'];
                $amount = '+ IQD ' . number_format($allrecord->total_amount, 2);
            } else {
                $tranTitle = __('message.Received from') . " " . $transArr['sender'];
                $amount = '+ IQD ' . number_format($allrecord->amount, 2);
            }
        } else {
            $tranTitle = __('message.Received from') . " " . $transArr['receiver'];
            $amount = '+ IQD ' . number_format($allrecord->amount, 2);
        }
    } elseif ($transArr['trans_type'] == 'Topup') {
        $tranTitle = __('message.Money Added To Wallet');
        $description = $allrecord->payment_mode;
        $amount = '+ IQD ' . number_format($allrecord->amount, 2);
    } elseif ($transArr['trans_type'] == 'Request') {
        if($allrecord->payment_mode == 'Withdraw'){
            $tranTitle = __('message.Money Requested From') . " " . $transArr['sender'];
            $description = $allrecord->payment_mode;
        } else{
            $tranTitle = __('message.Deposit to') . " " . $transArr['receiver'];
            $description = $transArr['receiver_phone'];
        }
        if ($userInfo->user_type == 'Merchant') {
            if ($allrecord->payment_mode == 'Withdraw') {
                $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
            } else {
                $amount = '+ IQD ' . number_format($allrecord->amount, 2);
            }
        } else {
            if ($allrecord->payment_mode == 'Withdraw') {
                $amount = '+ IQD ' . number_format($allrecord->amount, 2);
            } else {
                $amount = '- IQD ' . number_format($allrecord->amount, 2);
            }
        }
    } else {
        if ($allrecord->payment_mode == 'wallet2wallet') {
            $tranTitle = __('message.Paid to') . " " . $transArr['receiver'];
            $description = $transArr['receiver_phone'];
            $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
        } else {
            if ($allrecord->payment_mode == "Withdraw") {
                if ($userInfo->user_type == 'Merchant') {
                    $tranTitle = __('message.Withdraw from') . " " . $transArr['sender'];
                $description = $transArr['sender_phone'];
                $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                } else{
                    $tranTitle = __('message.Withdraw by') . " " . $transArr['receiver'];
                $description = $transArr['receiver_phone'];
                $amount = '+ IQD ' . number_format($allrecord->amount, 2);
                }
            } else {
                if ($allrecord->payment_mode == "Refund") {
                    $tranTitle = __('message.Refund to') . " " . $transArr['sender'];
                    $description = $transArr['sender_phone'];
                    $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                } else {
                    if ($userInfo->user_type == 'Merchant') {
                        if ($allrecord->payment_mode == "Agent Deposit") {
                            $tranTitle = __('message.Deposited to') . " " . $transArr['receiver'];
                        } elseif ($allrecord->payment_mode == "Withdraw") {
                            $tranTitle = __('message.Withdraw from') . " " . $transArr['sender'];
                        } else {
                            $tranTitle = $allrecord->payment_mode;
                            
                        }

                        if ($transArr['receiver_phone'] == "0") {
                            $description = $transArr['sender_phone'];
                        }
                        if ($allrecord->payment_mode != "Withdraw") {
                            $description = $transArr['sender_phone'];
                        } else {
                            $description = $transArr['receiver_phone'];
                        }

$amount = '- IQD ' . number_format($allrecord->amount, 2);
                    } else {
                        if ($allrecord->status != 'Success') {
                            $depositText = __('message.Deposited to');
                        } else {
                            $depositText = __('message.Deposited to');
                        }

                        if ($allrecord->payment_mode == "Agent Deposit") {
                            $tranTitle = $depositText . ' ' . $transArr['receiver'];
                        } elseif ($allrecord->payment_mode == "Withdraw") {
                            $tranTitle = __('message.Withdraw by') . " " . $transArr['receiver'];
                        } else {
                            $tranTitle = __('message.' . $allrecord->payment_mode . '');
                        }

                        if ($transArr['receiver_phone'] == "0") {
                            $description = $transArr['sender_phone'];
                        } else {
                            $description = $transArr['receiver_phone'];
                        }

                        if ($allrecord->payment_mode == "Agent Deposit") {
                            $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
                        } elseif ($allrecord->payment_mode == "Withdraw") {
                            $amount = '+ IQD ' . number_format($allrecord->amount, 2);
                        } else {
                            $amount = '- IQD ' . number_format($allrecord->amount, 2);
                        }
                    }
                }
            }
        }
    }
} else {
    if ($transArr['trans_type'] == 'Credit') {
        $tranTitle = $allrecord->payment_mode == "Refund" ? __('message.Refund from') . " " . $transArr['sender'] : __('message.Received from') . " " . $transArr['sender'];
        $description = $allrecord->payment_mode == "Agent Deposit" ? $allrecord->payment_mode : $transArr['sender_phone'];
        $amount = '+ IQD ' . number_format($allrecord->amount, 2);
    } elseif ($transArr['trans_type'] == 'Topup') {
        $tranTitle = __('message.Money Added To Wallet');
        $description = __('message.' . $allrecord->payment_mode . '');
        $amount = '+ IQD ' . number_format($allrecord->amount, 2);
    } elseif ($transArr['trans_type'] == 'Request') {
        $tranTitle = __('message.Money Requested From') . " " . $transArr['sender'];
        $description = $allrecord->payment_mode;
        $amount = $allrecord->payment_mode == "Withdraw" ? "- IQD " . number_format($allrecord->total_amount, 2) : "+ IQD " . number_format($allrecord->amount, 2);
    } else {
        if ($allrecord->payment_mode == 'wallet2wallet') {
            $tranTitle = __('message.Paid to') . " " . $transArr['receiver'];
            $description = $transArr['receiver_phone'];
            $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
        } else {
            if ($allrecord->payment_mode == "Withdraw") {
                $tranTitle = __('message.Withdraw from') . " " . $transArr['sender'];
                $description = $transArr['sender_phone'];
                $amount = '- IQD ' . number_format($allrecord->total_amount, 2);
            } else {
                $tranTitle = __('message.' . $allrecord->payment_mode . '');
                $description = $transArr['sender_phone'];
                $amount = '- IQD ' . number_format($allrecord->amount, 2);
            }
        }
    }
}

}

if($allrecord->payment_mode == 'Credited by admin' || $allrecord->payment_mode == 'Debited by admin'){
    $tranTitle = __('message.' . $allrecord->payment_mode . '');
    $description = 'Admin';
}

if($description == 'Withdraw'){
    $description = __('message.Withdraw');
}

if($description == 'Agent Deposit'){
    $description = __('message.Agent Deposit');
}
            ?>

            <div class="history-box-main home-history">
                <div class="col-sm-6 history-name-box">
                    <?php
                    global $tranStatus;
                    $trans_status = $tranStatus[$allrecord->status];
                    $class = '';

                    if ($trans_status == 'Reject') {
                        $amount = str_replace('+ ', ' ', $amount);
                    }
                    ?>
                    @if($trans_status == 'Success')
                    @php $class = 'green'; @endphp
                    <div class="name-tag success-name-tag">
                        <i class="fa fa-check"></i>
                    </div>
                    @elseif($trans_status == 'Reject')
                    @php $class = 'red'; @endphp
                    <div class="name-tag failed-name-tag">
                        <i class="fa fa-close"></i>
                    </div>            
                    @else
                    @php $class = 'red'; @endphp
                    <div class="name-tag pending-name-tag">
                        <i class="fa fa-exclamation"></i>
                    </div>
                    @endif
                    <div class="history-name">
                        <h6>{{$tranTitle}}</h6>
                        <span>{{$description}}</span>
                    </div>
                </div>
                <div class="col-sm-4 text-right history-detail">
                    <span class="{{$class}}">
                        {{$amount}}
                    </span>
                    <small>
                        <?php
                        $trnsDt = date_create($allrecord->created_at);
                        $transDate = date_format($trnsDt, "d M Y, h:i A");
                        ?>
                        {{$transDate}}
                    </small>
                </div>
            </div>
            @endforeach
            @else
            <div class="container mb-40"><div class="col-sm-8 history-name-box"><div class="no_record">{{__('message.No records found.')}}</div></div></div>
            @endif
        </div>
    </div>
</div>

<div class="home-card-block mobile_set" >
    <div class="container">
        <div class="row">
            <div class="col-sm-4 ">
                <div class="ticket-top-home ">
                    <a href="{{ URL::to( 'users/nearByMerchant')}}">
                        <div class="namebox-top-ticket">
                            <h6>{{__('message.Nearest Merchants')}}</h6>
                            <div class="arow-rt">
                                {{HTML::image('public/img/front/arrow-rt.svg', SITE_TITLE)}}
                            </div>
                        </div>
                        {{HTML::image('public/img/front/merchent.png', SITE_TITLE)}}
                    </a>
                </div>
            </div>
            <div class="col-sm-4 ">
                <div class="ticket-top-home">
                    <a href="{{ URL::to( 'users/nearByAgent')}}">
                        <div class="namebox-top-ticket">
                            <h6>{{__('message.Nearest Agents')}}</h6>
                            <div class="arow-rt">
                                {{HTML::image('public/img/front/arrow-rt.svg', SITE_TITLE)}}
                            </div>
                        </div>
                        {{HTML::image('public/img/front/agents.png', SITE_TITLE)}}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection