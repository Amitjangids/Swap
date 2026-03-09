@extends('layouts.admin')
@section('content')
@php
use App\Http\Controllers\Admin\AdminsController;
@endphp
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<div class="content-wrapper">
    <section class="content-header">
        <h1>Dashboard</h1>
    </section>
    <section class="content">
        <div class="row">
           
           
            <?php if($adminInfo->company_name=="") {  ?>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3>{{$dadhboardData['users_count']}}</h3>
                        <p> Users</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-user"></i>
                    </div>
                    <a href="{{URL::to( 'admin/users')}}" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <?php } ?>
            
           
           
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3>{{$dadhboardData['agents_count']}}</h3>
                        <p>Agent Users</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-user-secret"></i>
                    </div>
                    <a href="{{URL::to( 'admin/agents')}}" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
          
          
            <?php if($adminInfo->company_name=="") {  ?>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3>{{$dadhboardData['merchants_count']}}</h3>
                        <p>Merchant Users</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-users"></i>
                    </div>
                    <a href="{{URL::to( 'admin/merchants')}}" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
      
         
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3>{{$dadhboardData['subadmins_count']}}</h3>
                        <p>Sub Admin Users</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-users"></i>
                    </div>
                    <a href="{{URL::to( 'admin/subadmins')}}" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <?php } ?>
           
        
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3>{{$dadhboardData['transactions_count']}}</h3>
                        <p>Transactions</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-money"></i>
                    </div>
                    <a href="{{URL::to( 'admin/transactions')}}" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-green">
                    <div class="inner">
                    <h3>{{CURR}}{{number_format((($adminInfo->wallet_balance - floor($adminInfo->wallet_balance)) > 0.5 ? ceil($adminInfo->wallet_balance) : floor($adminInfo->wallet_balance)), 0, '', ' ') ?? 0}}</h3>
                        <p><?php echo $adminInfo->company_name!="" ? 'Wallet Balance' : 'Admin Wallet Balance' ?></p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-money"></i>
                    </div>
                    <a href="#" class="small-box-footer"><i class="fa fa-"></i></a>
                </div>
            </div>
           
           
            <?php if($adminInfo->company_name=="") {  ?>
            <div class="col-lg-12 col-xs-12">
                <h4 class="admin_st">Users Statistics</h2>
                    <div class="relative_box_esjad">
                        <div class="company_tab">
                            <span class="cpc" id="cchart0" onclick="updateUser(0)">Today</span>
                            <span class="cpc" id="cchart1"  onclick="updateUser(1)">Yesterday</span>
                            <span class="cpc active" id="cchart4"  onclick="updateUser(4)">Last 7 days</span>
                            <span class="cpc active" id="cchart2"  onclick="updateUser(2)">Last 30 days</span>                            
                            <span  class="cpc" id="cchart3" onclick="updateUser(3)">Last 12 months</span>
                        </div>
                        <div class="chart_loader" id="user_chart_loader">{{HTML::image("public/img/website_load.svg", SITE_TITLE)}}</div>
                        <div class="admin_chart" id="user_chart"></div>
                    </div>
            </div>
            <?php } ?>
           
            <div class="col-lg-12 col-xs-12">
                <h4 class="admin_st">Agents Statistics</h2>
                    <div class="relative_box_esjad">
                        <div class="company_tab">
                            <span class="apc" id="achart0" onclick="updateAgent(0)">Today</span>
                            <span class="apc" id="achart1"  onclick="updateAgent(1)">Yesterday</span>
                            <span class="apc active" id="achart4"  onclick="updateAgent(4)">Last 7 days</span>
                            <span class="apc active" id="achart2"  onclick="updateAgent(2)">Last 30 days</span>                            
                            <span  class="apc" id="achart3" onclick="updateAgent(3)">Last 12 months</span>
                        </div>
                        <div class="chart_loader" id="agent_chart_loader">{{HTML::image("public/img/website_load.svg", SITE_TITLE)}}</div>
                        <div class="admin_chart" id="agent_chart"></div>
                    </div>
            </div>
        
           
            <?php if($adminInfo->company_name=="") {  ?>
            <div class="col-lg-12 col-xs-12">
                <h4 class="admin_st">Merchants Statistics</h2>
                    <div class="relative_box_esjad">
                        <div class="company_tab">
                            <span class="mpc" id="mchart0" onclick="updateMerchant(0)">Today</span>
                            <span class="mpc" id="mchart1"  onclick="updateMerchant(1)">Yesterday</span>
                            <span class="mpc active" id="mchart4"  onclick="updateMerchant(4)">Last 7 days</span>
                            <span class="mpc active" id="mchart2"  onclick="updateMerchant(2)">Last 30 days</span>                            
                            <span  class="mpc" id="mchart3" onclick="updateMerchant(3)">Last 12 months</span>
                        </div>
                        <div class="chart_loader" id="merchant_chart_loader">{{HTML::image("public/img/website_load.svg", SITE_TITLE)}}</div>
                        <div class="admin_chart" id="merchant_chart"></div>
                    </div>
            </div>
            <?php } ?>
           
           
           
            <div class="col-lg-12 col-xs-12">
                <h4 class="admin_st">Transactions Statistics</h2>
                    <div class="relative_box_esjad">
                        <div class="company_tab">
                            <span class="tpc" id="tchart0" onclick="updateTransaction(0)">Today</span>
                            <span class="tpc" id="tchart1"  onclick="updateTransaction(1)">Yesterday</span>
                            <span class="tpc active" id="tchart4"  onclick="updateTransaction(4)">Last 7 days</span>
                            <span class="tpc active" id="tchart2"  onclick="updateTransaction(2)">Last 30 days</span>                            
                            <span  class="tpc" id="tchart3" onclick="updateTransaction(3)">Last 12 months</span>
                        </div>
                        <div class="chart_loader" id="trans_chart_loader">{{HTML::image("public/img/website_load.svg", SITE_TITLE)}}</div>
                        <div class="admin_chart" id="trans_chart"></div>
                    </div>
            </div>
           
        </div>
    </section>
</div>
<script>
    $(function () {
        updateUser(2);
        updateAgent(2);
        updateMerchant(2);
        updateTransaction(2);
    });
    function updateUser(daycnt) {
        $('.cpc').removeClass('active');
        $('#cchart' + daycnt).addClass('active');
        $.ajax({
            type: 'get',
            url: '{{HTTP_PATH}}/admin/admins/userchart/' + daycnt,
            beforeSend: function () {
                $("#user_chart_loader").show();
            },
            success: function (result) {
                $("#user_chart").html(result);
            }
        });
    }
    function updateAgent(daycnt) {
        $('.apc').removeClass('active');
        $('#achart' + daycnt).addClass('active');
        $.ajax({
            type: 'get',
            url: '{{HTTP_PATH}}/admin/admins/agentchart/' + daycnt,
            beforeSend: function () {
                $("#agent_chart_loader").show();
            },
            success: function (result) {
                $("#agent_chart").html(result);
            }
        });
    }
    function updateMerchant(daycnt) {
        $('.mpc').removeClass('active');
        $('#mchart' + daycnt).addClass('active');
        $.ajax({
            type: 'get',
            url: '{{HTTP_PATH}}/admin/admins/merchantchart/' + daycnt,
            beforeSend: function () {
                $("#merchant_chart_loader").show();
            },
            success: function (result) {
                $("#merchant_chart").html(result);
            }
        });
    }
    function updateTransaction(daycnt) {
        $('.tpc').removeClass('active');
        $('#tchart' + daycnt).addClass('active');
        $.ajax({
            type: 'get',
            url: '{{HTTP_PATH}}/admin/admins/transchart/' + daycnt,
            beforeSend: function () {
                $("#trans_chart_loader").show();
            },
            success: function (result) {
                $("#trans_chart").html(result);
            }
        });
    }
</script>
@endsection