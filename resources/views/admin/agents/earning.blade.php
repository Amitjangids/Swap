@extends('layouts.admin')
@section('content')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<style>
    .stretch-card .mdc-card {
	height: 120px;
}
</style>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Total Earnings By Agents</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li class="active"> Manage Balance Management</li>
        </ol>
    </section>

    <script>
        $(document).ready(function () {
            $(".nav-link").click(function () {
                var elementId = $(this).html();
                getSummary(elementId);
            });
        });


     getSummary('User');  

     function getSummary(elementId)
     {  
        $.ajax({
        type: "POST",
        url: "{{HTTP_PATH}}/admin/transaction/getSummary",  // Add a comma here
        data: { 'elementId' : elementId,'_token': '{{ csrf_token() }}' },
        success: function (response) {
           $('#fundTransfer'+elementId).html(response.fundTransfer);
           $('#withdraw'+elementId).html(response.withdrawAmount);
           $('#sendMoney'+elementId).html(response.sendMoney);
           $('#totalEarning'+elementId).html(response.totalEarning);
        },
      });
     }
    </script>

    <section class="content">
        <div class="box box-info">
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
            <div class="earning-wrapper-section">
                <div class="earning-inner-wrapper">
                    <div class="tab-content" id="nav-tabContent">
                      <div class="tab-pane fade show active" id="nav-contact" role="tabpanel" aria-labelledby="nav-contact-tab">
                          <div class="row">

                          <div class="col-lg-3">
                                   <div class="small-box bg-green">
                                        <div class="inner">
                                            <h3 id="fundTransferAgent">{{$fundTransfer}}</h3>
                                            <p>Total Earning By Agent Deposit</p>
                                        </div>
                                    </div>
                               </div>
  
                               <div class="col-lg-3">
                                    <div class="small-box bg-blue">
                                        <div class="inner">
                                            <h3 id="sendMoneyAgent">{{$sendMoney}}</h3>
                                            <p>Total Earning By Agent Withdraw</p>
                                        </div>
                                    </div>
                               </div>

                               <div class="col-lg-3">
                                    <div class="small-box bg-primary">
                                        <div class="inner">
                                            <h3 id="totalEarningAgent">{{$totalEarning}}</h3>
                                            <p>Total Earning</p>
                                        </div>
                                    </div>
                               </div>

                           </div>
                      </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="box box-info">
            <div class="admin_search">
                {{ Form::open(array('method' => 'post', 'id' => 'adminSearch')) }}
                <div class="form-group align_box dtpickr_inputs">
                    <span class="hints">Search by Transaction ID or Reference ID</span>
                    <span class="hint">{{Form::text('keyword', null, ['class'=>'form-control', 'placeholder'=>'Search by Transaction ID or Reference ID', 'autocomplete' => 'off'])}}</span>
                    <span class="hint">
                        {{Form::text('to', null, ['id'=>'toDate','class'=>'form-control', 'placeholder'=>'Search by date', 'autocomplete' => 'off'])}}
                    </span>
                    <div class="admin_asearch">
                        <div class="ad_s ajshort">{{Form::button('Submit', ['class' => 'btn btn-info admin_ajax_search'])}}</div>
                        <div class="ad_cancel"><a href="{{URL::to('admin/agents/reports')}}" class="btn btn-default canlcel_le">Clear Search</a></div>
                    </div>
                </div>
                {{ Form::close()}}
                <!--<div class="add_new_record"><a href="{{URL::to('admin/transactions/adjustWallet')}}" class="btn btn-default"><i class="fa fa-plus"></i> Adjust User Balance</a></div>-->
            </div>      
            <div class="mdc-layout-grid row">
               <!--  <div class="mdc-layout-grid__inner col-md-4">
                    <div class="stretch-card">
                        <div class="mdc-card info-card info-card--success">
                            <div class="card-inner">
                                <h5 class="card-title">Total Earning</h5> <?php //echo $transactionTotal->transactionTotal;exit;?>
                                <h5 class="font-weight-light">₣  {{$transactionTotal->transactionTotal}}</h5>
                                <div class="card-icon-wrapper">
                                    <i class="fa fa-money"></i>
                                </div>
                            </div>
                        </div>
                    </div>


                </div> -->
                <!-- <div class="mdc-layout-grid__inner col-md-4">
                    <div class="stretch-card">
                        <div class="mdc-card info-card info-card--success bg-cyan">
                            <a href="{{URL::to('admin/transactions/adjustWallet')}}">
                            <div class="card-inner">
                                <h5 class="card-title">Adjust Wallet Balance</h5> <?php //echo $transactionTotal->transactionTotal;exit;?>
                                <h5 class="font-weight-light"></h5>
                                <div class="card-icon-wrapper">
                                    <i class="fa fa-google-wallet"></i>
                                </div>
                            </div>
                                </a>
                        </div>
                    </div>


                </div> -->
<!--                <div class="mdc-layout-grid__inner card card-hover col-md-2 col-lg-2 col-xlg-2">
                    <a href="{{URL::to('admin/cards/usedcard')}}">
                        <div class="box bg-purple text-center">
                            <h1 class="font-light text-white">
                                <i class="fa fa-credit-card"></i>
                            </h1>
                            <h6 class="text-white">Recharge Cards List</h6>
                        </div>
                    </a>
                </div>
                <div class="mdc-layout-grid__inner card card-hover col-md-2 col-lg-2 col-xlg-2">
                    <a href="{{URL::to('admin/scratchcards/usedcard')}}">
                        <div class="box bg-orange text-center">
                            <h1 class="font-light text-white">
                                <i class="fa fa-ticket"></i>
                            </h1>
                            <h6 class="text-white">Used/Purchased Scratch Cards</h6>
                        </div>
                    </a>
                </div>-->
<!--<div class="mdc-layout-grid__inner card card-hover col-md-2 col-lg-2 col-xlg-2"></div>
<div class="mdc-layout-grid__inner card card-hover col-md-2 col-lg-2 col-xlg-2"></div>
                <div class="mdc-layout-grid__inner card card-hover col-md-2 col-lg-2 col-xlg-2">
                    <a href="{{URL::to('admin/transactions/adjustWallet')}}">
                        <div class="box bg-cyan text-center">
                            <h1 class="font-light text-white">
                                <i class="fa fa-google-wallet"></i>
                            </h1>
                            <h6 class="text-white">Adjust Wallet Balance</h6>
                        </div>
                    </a>
                </div>
            </div>-->


            <div class="m_content" id="listID">
                @include('elements.admin.agents.earning')
            </div>
        </div>
    </section>
   
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"></script>

@endsection