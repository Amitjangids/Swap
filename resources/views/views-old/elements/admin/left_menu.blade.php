@php
use App\Http\Controllers\Admin\AdminsController;
@endphp
<aside class="main-sidebar">
    <section class="sidebar">
        <ul class="sidebar-menu">
            <li class="treeview @if(isset($actdashboard)){{'active'}}@endif">
                <a href="{{URL::to('admin/admins/dashboard')}}">
                    <i class="fa fa-dashboard"></i> <span>Dashboard</span>
                </a>
            </li>
            @php
            $roles = AdminsController::getRoles(Session::get('adminid'),1);
            @endphp
            <?php if ($roles == 1) { ?>
                <li class="treeview @if(isset($actchangeusername) || isset($actchangepassword) || isset($actchangeemail) || isset($actchangecommission) || isset($actfeatures) || isset($actchangelimit)){{'active'}}@endif">
                    <a href="javascript:void(0)">
                        <i class="fa fa-gears"></i> <span>Configuration</span> <i class="fa fa-angle-right pull-right"></i>
                    </a>
                    <ul class="treeview-menu">
                        <li class="@if(isset($actchangeusername)){{'active'}}@endif"><a href="{{URL::to('admin/admins/change-username')}}"><i class="fa fa-circle-o"></i> Change Username</a></li>
                        <li class="@if(isset($actchangepassword)){{'active'}}@endif"><a href="{{URL::to('admin/admins/change-password')}}"><i class="fa fa-circle-o"></i> Change Password</a></li>
                        <li class="@if(isset($actfeatures)){{'active'}}@endif"><a href="{{URL::to('admin/homeFeatures')}}"><i class="fa fa-circle-o"></i> Enable/Disable Features</a></li>
                        <!--<li class="@if(isset($actchangeemail)){{'active'}}@endif"><a href="{{URL::to('admin/admins/change-email')}}"><i class="fa fa-circle-o"></i> Change Email</a></li>-->
                        <!--<li class="@if(isset($actchangecommission)){{'active'}}@endif"><a href="{{URL::to('admin/admins/change-commission')}}"><i class="fa fa-circle-o"></i> Change Commission</a></li>-->
                        <li class="@if(isset($actchangelimit)){{'active'}}@endif"><a href="{{URL::to('admin/admins/change-limit')}}"><i class="fa fa-circle-o"></i> Max Transaction Limit</a></li>
                        
                    </ul>
                </li>   
            <?php } ?>
                
                @php
            $roles1 = AdminsController::getRoles(Session::get('adminid'),2);
            $roles2 = AdminsController::getRoles(Session::get('adminid'),3);
            $roles3 = AdminsController::getRoles(Session::get('adminid'),4);
            $roles4 = AdminsController::getRoles(Session::get('adminid'),16);
            $roles5 = AdminsController::getRoles(Session::get('adminid'),15);
            @endphp
            <?php if ($roles1 == 1 || $roles2 == 1 || $roles3 == 1 || $roles4 == 1) { ?>
            <li class="treeview @if(isset($actusers) || isset($actagents) || isset($actmerchants) || isset($actallusers) || isset($actusers3)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-user"></i> <span>Manage Users</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <?php if ($roles1 == 1){ ?>
                    <li class="@if(isset($actusers)){{'active'}}@endif"><a href="{{URL::to('admin/users')}}"><i class="fa fa-circle-o"></i> Users List</a></li>
                    <?php } if ($roles2 == 1){ ?>
                    <li class="@if(isset($actagents)){{'active'}}@endif"><a href="{{URL::to('admin/agents')}}"><i class="fa fa-circle-o"></i>Agent Users List</a></li>
                    <?php } if ($roles3 == 1){ ?>
                    <li class="@if(isset($actmerchants)){{'active'}}@endif"><a href="{{URL::to('admin/merchants')}}"><i class="fa fa-circle-o"></i>Merchant Users List</a></li>
                    <?php } if ($roles4 == 1){ ?>
                    <li class="@if(isset($actallusers)){{'active'}}@endif"><a href="{{URL::to('admin/users/all')}}"><i class="fa fa-circle-o"></i>Total Register Users List</a></li>
                    <?php } if ($roles5 == 1){ ?>
                    <li class="@if(isset($actusers3)){{'active'}}@endif"><a href="{{URL::to('admin/users/loginusers')}}"><i class="fa fa-circle-o"></i>Logged In Users Report</a></li>
                    <?php } ?>
                </ul>
            </li>
            <?php } ?>
                
<!--                @php
            $roles = AdminsController::getRoles(Session::get('adminid'),3);
            @endphp-->
            <?php /*if ($roles == 1) { ?>
            <li class="treeview @if(isset($actagents)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-user-secret"></i> <span>Manage Agent Users</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="@if(isset($actagents)){{'active'}}@endif"><a href="{{URL::to('admin/agents')}}"><i class="fa fa-circle-o"></i>Agent Users List</a></li>
                </ul>
            </li>
            <?php } ?>
                
                @php
            $roles = AdminsController::getRoles(Session::get('adminid'),4);
            @endphp
            <?php if ($roles == 1) { ?>
            <li class="treeview @if(isset($actmerchants)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-users"></i> <span>Manage Merchant Users</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="@if(isset($actmerchants)){{'active'}}@endif"><a href="{{URL::to('admin/merchants')}}"><i class="fa fa-circle-o"></i>Merchant Users List</a></li>
                </ul>
            </li>
            <?php } */ ?>
                
                @php
            $roles = AdminsController::getRoles(Session::get('adminid'),5);
            @endphp
            <?php if ($roles == 1) { ?>
            <?php //if (Session::get('admin_usertype') == 'Admin') { ?>
                <li class="treeview @if(isset($actsubadmins)){{'active'}}@endif">
                    <a href="javascript:void(0)">
                        <i class="fa fa-users"></i> <span>Manage Sub Admins</span> <i class="fa fa-angle-right pull-right"></i>
                    </a>
                    <ul class="treeview-menu">
                        <li class="@if(isset($actsubadmins)){{'active'}}@endif"><a href="{{URL::to('admin/subadmins')}}"><i class="fa fa-circle-o"></i>Sub Admins List</a></li>
                    </ul>
                </li>
            <?php //} ?>
                <?php } ?>
                
                <!-- @php
            $roles = AdminsController::getRoles(Session::get('adminid'),6);
            @endphp
            <?php if ($roles == 1) { ?>
            <li class="treeview @if(isset($actscratchcards)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-ticket"></i> <span>Manage Scratch Cards</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="@if(isset($actscratchcards)){{'active'}}@endif"><a href="{{URL::to('admin/scratchcards')}}"><i class="fa fa-circle-o"></i>Scratch Cards List</a></li>
                    <li class="@if(isset($actscratchcards)){{'active'}}@endif"><a href="{{URL::to('admin/scratchcards/usedcard')}}"><i class="fa fa-circle-o"></i>Used/Purchased Cards</a></li>
                </ul>
            </li>
            <?php } ?> -->
                
                @php
            $roles = AdminsController::getRoles(Session::get('adminid'),7);
            @endphp
            <?php if ($roles == 1) { ?>
            <li class="treeview @if(isset($actcards)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-credit-card"></i> <span>Manage Cards</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="@if(isset($actcards)){{'active'}}@endif"><a href="{{URL::to('admin/cards')}}"><i class="fa fa-circle-o"></i>Cards List</a></li>
                    <li class="@if(isset($actcards)){{'active'}}@endif"><a href="{{URL::to('admin/cards/usedcard')}}"><i class="fa fa-circle-o"></i>Used Cards</a></li>
                </ul>
            </li>
            <?php } ?>
                
                @php
            $roles = AdminsController::getRoles(Session::get('adminid'),8);
            @endphp
            <?php if ($roles == 1) { ?>
            <li class="treeview @if(isset($actbanners)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-picture-o"></i> <span>Manage Banners</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="@if(isset($actbanners)){{'active'}}@endif"><a href="{{URL::to('admin/banners')}}"><i class="fa fa-circle-o"></i>Banners List</a></li>
                </ul>
            </li>
            <?php } ?>
                
                <!-- @php
            $roles = AdminsController::getRoles(Session::get('adminid'),9);
            @endphp
            <?php if ($roles == 1) { ?>
            <li class="treeview @if(isset($actoffers)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-gift"></i> <span>Manage Offers</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="@if(isset($actoffers)){{'active'}}@endif"><a href="{{URL::to('admin/offers')}}"><i class="fa fa-circle-o"></i>Offers List</a></li>
                </ul>
            </li>
            <?php } ?> -->
                
                @php
            $roles = AdminsController::getRoles(Session::get('adminid'),13);
            @endphp
            <?php if ($roles == 1) { ?>
                        <li class="treeview @if(isset($acttranfees)){{'active'}}@endif">
                            <a href="javascript:void(0)">
                                <i class="fa fa-money"></i> <span>Manage Transactions Fees</span> <i class="fa fa-angle-right pull-right"></i>
                            </a>
                            <ul class="treeview-menu">
                                <li class="@if(isset($acttranfees)){{'active'}}@endif"><a href="{{URL::to('admin/transactionfees')}}"><i class="fa fa-circle-o"></i>Transaction Fees List</a></li>
                            </ul>
                        </li>
            <?php } ?>
                
                @php
            $roles1 = AdminsController::getRoles(Session::get('adminid'),10);
            $roles2 = AdminsController::getRoles(Session::get('adminid'),17);
            @endphp
            <?php if ($roles1 == 1 || $roles2 == 1) { ?>
            <li class="treeview @if(isset($acttransactions) || isset($actadmintransactions)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Manage Transactions</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <?php if ($roles1 == 1){ ?>
                    <li class="@if(isset($acttransactions)){{'active'}}@endif"><a href="{{URL::to('admin/transactions')}}"><i class="fa fa-circle-o"></i>Transactions List</a></li>
                    <?php } if ($roles2 == 1){ ?>
                            <li class="@if(isset($actadmintransactions)){{'active'}}@endif"><a href="{{URL::to('admin/transactions/adminTrans')}}"><i class="fa fa-circle-o"></i>Admin Transactions List</a></li>
                    <?php } ?>
                </ul>
            </li>
            <?php } ?>
            
            @php
            $roles = AdminsController::getRoles(Session::get('adminid'),14);
            @endphp
            <?php if ($roles == 1) { ?>
            <li class="treeview @if(isset($actearnings)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Balance Management</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="@if(isset($actearnings)){{'active'}}@endif"><a href="{{URL::to('admin/transactions/earning')}}"><i class="fa fa-circle-o"></i>Balance Management</a></li>
                </ul>
            </li>
            <?php } ?>
                
                <!-- @php
            $roles = AdminsController::getRoles(Session::get('adminid'),11);
            @endphp
            <?php if ($roles == 1) { ?>
            <li class="treeview @if(isset($actcontacts)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-address-book"></i> <span>Manage Inquiries</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="@if(isset($actcontacts)){{'active'}}@endif"><a href="{{URL::to('admin/contacts')}}"><i class="fa fa-circle-o"></i>Inquiries List</a></li>
                </ul>
            </li>
            <?php } ?> -->
                
                @php
            $roles = AdminsController::getRoles(Session::get('adminid'),12);
            @endphp
            <?php if ($roles == 1) { ?>
            <li class="treeview @if(isset($actpages)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-file-text-o"></i> <span>Manage Pages</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="@if(isset($actpages)){{'active'}}@endif"><a href="{{HTTP_PATH}}/admin/pages"><i class="fa fa-circle-o"></i>Page List</a></li>
                </ul>
            </li>
            <?php } ?>

        </ul>
    </section>
</aside>