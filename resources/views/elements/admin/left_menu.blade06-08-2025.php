@php
use App\Http\Controllers\Admin\AdminsController;
@endphp
@php
use App\Permission;
@endphp
<aside class="main-sidebar">
    <section class="sidebar">

            @php
                $roles = AdminsController::getRoles(Session::get('adminid'));   
            @endphp
           
      
            <?php 
                $permissions = DB::table('permissions')->where('role_id',$roles)->pluck('permission_name')->toArray();
            ?>
        <ul class="sidebar-menu">
          @if(in_array('dashboard',$permissions))
            <li class="treeview @if(isset($actdashboard)){{'active'}}@endif"><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span>
             </a>
            </li>
           @endif
          
            
           @if(in_array('change-username', $permissions) ||
                in_array('change-password', $permissions) ||
                in_array('department', $permissions) ||
                in_array('transactions-limit', $permissions) ||
                in_array('customer-transaction-limit', $permissions) ||
                in_array('merchant-transaction-limit', $permissions) ||
                in_array('change-referral-bonus', $permissions) ||
                in_array('agent-transaction-limit', $permissions))


                <li class="treeview @if(isset($actchangeusername) || isset($actchangepassword) || isset($actchangeemail) || isset($actchangecommission) || isset($actfeatures) || isset($actconfigrole) || isset($actchangelimit) || isset($actconfigtranslimit) || isset($actmerchantlimit) || isset($actagentlimit) || isset($actchangereferralbonus)){{'active'}}@endif">
                    <a href="javascript:void(0)">
                        <i class="fa fa-gears"></i> <span>Configuration</span> <i class="fa fa-angle-right pull-right"></i>
                    </a>
                    <ul class="treeview-menu">
                        @if(in_array('change-username',$permissions))
                        <li class="@if(isset($actchangeusername)){{'active'}}@endif"><a href="{{URL::to('admin/admins/change-username')}}"><i class="fa fa-circle-o"></i> Change Username</a></li>
                        @endif
                        @if(in_array('change-password',$permissions))
                        <li class="@if(isset($actchangepassword)){{'active'}}@endif"><a href="{{URL::to('admin/admins/change-password')}}"><i class="fa fa-circle-o"></i> Change Password</a></li>
                        @endif
                        @if(in_array('department',$permissions))
                        <li class="@if(isset($actconfigrole)){{'active'}}@endif"><a href="{{URL::to('admin/admins/department')}}"><i class="fa fa-circle-o"></i> Configure Department</a></li>
                        @endif
                        @if(in_array('transactions-limit',$permissions))
                        <li class="@if(isset($actconfigtranslimit)){{'active'}}@endif"><a href="{{URL::to('admin/users/transactions-limit')}}"><i class="fa fa-circle-o"></i> Configure Trans. Limit</a></li>
                        @endif
                        @if(in_array('customer-transaction-limit',$permissions))
                        <li class="@if(isset($actchangelimit)){{'active'}}@endif"><a href="{{URL::to('admin/admins/change-limit/customer-transaction-limit')}}"><i class="fa fa-circle-o"></i> User Transactions Limit</a></li>
                        @endif
                        @if(in_array('merchant-transaction-limit',$permissions))
                        <li class="@if(isset($actmerchantlimit)){{'active'}}@endif"><a href="{{URL::to('admin/admins/change-limit/merchant-transaction-limit')}}"><i class="fa fa-circle-o"></i> Merchant Transactions Limit</a></li>
                        @endif
                        @if(in_array('agent-transaction-limit',$permissions))
                        <li class="@if(isset($actagentlimit)){{'active'}}@endif"><a href="{{URL::to('admin/admins/change-limit/agent-transaction-limit')}}"><i class="fa fa-circle-o"></i> Agent Transactions Limit</a></li>
                        @endif
                        @if(in_array('change-referral-bonus',$permissions))
                        <li class="@if(isset($actchangereferralbonus)){{'active'}}@endif"><a href="{{URL::to('admin/admins/change-referral-bonus')}}"><i class="fa fa-circle-o"></i> Change Referral Bonus</a></li>
                        @endif
                      
                    </ul>
                </li>   
                @endif
                
            @if(in_array('company-list', $permissions) ||
            in_array('add-company', $permissions) ||
            in_array('edit-company', $permissions))
            <li class="treeview @if(isset($actcompanieslist)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Manage Companies List </span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="@if(isset($actcompanieslist)){{'active'}}@endif"><a href="{{HTTP_PATH}}/admin/admins/company-list"><i class="fa fa-circle-o"></i>Companies List</a></li>
                </ul>
            </li>   
            @endif    
                
              

                @if(in_array('users', $permissions) ||
                in_array('agents', $permissions) ||
                in_array('department', $permissions) ||
                in_array('merchants', $permissions) ||
                in_array('all', $permissions) ||
                in_array('loginusers', $permissions) ||
                in_array('bulk-payment-merchants',$permissions))
            <li class="treeview @if(
                isset($actusers) 
                || isset($actagents) 
                || isset($actmerchants) 
                || isset($actallusers) 
                || isset($bulkpaymentmerchant) 
                || isset($actusers3)
                ){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-user"></i> <span>Manage Users</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    @if(in_array('users',$permissions))
                    <li class="@if(isset($actusers)){{'active'}}@endif"><a href="{{URL::to('admin/users')}}"><i class="fa fa-circle-o"></i> Users List</a></li>
                    @endif
                    @if(in_array('agents',$permissions))
                    <li class="@if(isset($actagents)){{'active'}}@endif"><a href="{{URL::to('admin/agents')}}"><i class="fa fa-circle-o"></i>Agent Users List</a></li>
                    @endif
                    @if(in_array('merchants',$permissions))
                    <li class="@if(isset($actmerchants)){{'active'}}@endif"><a href="{{URL::to('admin/merchants')}}"><i class="fa fa-circle-o"></i>Merchant Users List</a></li>
                    @endif
                    @if(in_array('all',$permissions))
                    <li class="@if(isset($actallusers)){{'active'}}@endif"><a href="{{URL::to('admin/users/all')}}"><i class="fa fa-circle-o"></i>Approver/Submitter List</a></li>
                    @endif
                    @if(in_array('bulk-payment-merchants',$permissions))
                        <li class="@if(isset($bulkpaymentmerchant)) active @endif">
                            <a href="{{ URL::to('admin/bulk-payment-merchants') }}">
                                <i class="fa fa-circle-o"></i>Bulk Payment Merchant List
                            </a>
                        </li>
                    @endif
                    <!-- @if(in_array('loginusers',$permissions))
                    <li class="@if(isset($actusers3)){{'active'}}@endif"><a href="{{URL::to('admin/users/loginusers')}}"><i class="fa fa-circle-o"></i>Logged In Users Report</a></li>
                    @endif -->
                </ul>
            </li>
          @endif

                
                
            @if(in_array('subadmins',$permissions))
                <li class="treeview @if(isset($actsubadmins)){{'active'}}@endif">
                    <a href="javascript:void(0)">
                        <i class="fa fa-users"></i> <span>Manage Sub Admins</span> <i class="fa fa-angle-right pull-right"></i>
                    </a>
                    <ul class="treeview-menu">
                       
                        <li class="@if(isset($actsubadmins)){{'active'}}@endif"><a href="{{URL::to('admin/subadmins')}}"><i class="fa fa-circle-o"></i>Sub Admins List</a></li>
                        
                    </ul>
                </li>
                @endif

                @if(in_array('driver-list',$permissions))
                <li class="treeview @if(isset($actdrivers)){{'active'}}@endif">
                    <a href="javascript:void(0)">
                        <i class="fa fa-users"></i> <span>Manage Drivers</span> <i class="fa fa-angle-right pull-right"></i>
                    </a>
                    <ul class="treeview-menu">
                       
                        <li class="@if(isset($actdrivers)){{'active'}}@endif"><a href="{{URL::to('admin/drivers')}}"><i class="fa fa-circle-o"></i>Drivers List</a></li>
                        
                    </ul>
                </li>
                @endif

                @if(in_array('help-ticket',$permissions))
                <li class="treeview @if(isset($acthelpticket)){{'active'}}@endif">
                    <a href="javascript:void(0)">
                        <i class="fa fa-users"></i> <span>Help Center</span> <i class="fa fa-angle-right pull-right"></i>
                    </a>
                    <ul class="treeview-menu">
                       
                        <li class="@if(isset($acthelpticket)){{'active'}}@endif"><a href="{{URL::to('admin/help-ticket')}}"><i class="fa fa-circle-o"></i>Customer Support</a></li>
                        
                    </ul>
                </li>
                @endif
                
         
                
       
              
                @if(in_array('banners',$permissions))
            <li class="treeview @if(isset($actbanners)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-picture-o"></i> <span>Manage Banners</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                
                       
                    <li class="@if(isset($actbanners)){{'active'}}@endif"><a href="{{URL::to('admin/banners')}}"><i class="fa fa-circle-o"></i>Banners List</a></li>
                    
                </ul>
            </li>
            @endif
                
          
                
                @if(in_array('transactionfees',$permissions) || in_array('amountSlab',$permissions))
                        <li class="treeview @if(isset($acttranfees) || isset($acttranfees1)){{'active'}}@endif">
                            <a href="javascript:void(0)">
                                <i class="fa fa-money"></i> <span>Manage Transactions Fees</span> <i class="fa fa-angle-right pull-right"></i>
                            </a>
                             <ul class="treeview-menu">
                               @if( in_array('amountSlab',$permissions))
                           
                                <li class="@if(isset($acttranfees1)){{'active'}}@endif"><a href="{{HTTP_PATH}}/admin/transactionfees/amountSlab"><i class="fa fa-circle-o"></i>Amount Slab List</a></li>
                                @endif

                          
                                @if(in_array('transactionfees',$permissions))
                                <li class="@if(isset($acttranfees)){{'active'}}@endif"><a href="{{URL::to('admin/transactionfees')}}"><i class="fa fa-circle-o"></i>Transaction Fees List</a></li>
                                @endif
                            </ul>
                        </li>
                       
                        @endif
           
                        @if(in_array('transactions',$permissions))
            <li class="treeview @if(isset($acttransactions) || isset($actadmintransactions)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Manage Transactions</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
               
                    <li class="@if(isset($acttransactions)){{'active'}}@endif"><a href="{{URL::to('admin/transactions')}}"><i class="fa fa-circle-o"></i>Transactions List</a></li>
                  
                            <!-- <li class="@if(isset($actadmintransactions)){{'active'}}@endif"><a href="{{URL::to('admin/transactions/adminTrans')}}"><i class="fa fa-circle-o"></i>Admin Transactions List</a></li> -->
                  
                </ul>
            </li>
            @endif
            
           
            @if(in_array('earning',$permissions))
            <li class="treeview @if(isset($actearnings)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Balance Management</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                
                    <li class="@if(isset($actearnings)){{'active'}}@endif"><a href="{{URL::to('admin/transactions/earning')}}"><i class="fa fa-circle-o"></i>Balance Management List</a></li>
                    
                </ul>
            </li>
            @endif
                

                
            @if(in_array('pages',$permissions))
            <li class="treeview @if(isset($actpages)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-file-text-o"></i> <span>Manage Pages</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                   
                    <li class="@if(isset($actpages)){{'active'}}@endif"><a href="{{HTTP_PATH}}/admin/pages"><i class="fa fa-circle-o"></i>Page List</a></li>
                    
                </ul>
            </li>
            @endif

            @if(in_array('cardcontents',$permissions))
            <li class="treeview @if(isset($actcardcontents)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-file-text-o"></i> <span>Manage Card Content</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                   
                    <li class="@if(isset($actcardcontents)){{'active'}}@endif"><a href="{{HTTP_PATH}}/admin/cardcontents"><i class="fa fa-circle-o"></i>Card Content</a></li>
                    
                </ul>
            </li>
            @endif

            @if(in_array('gemic-transation',$permissions))
            <li class="treeview @if(isset($actgemic)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Manage Gimac Transactions </span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                
                    <li class="@if(isset($actgemic)){{'active'}}@endif"><a href="{{HTTP_PATH}}/admin/gemic-transation"><i class="fa fa-circle-o"></i>Gimac Transactions List</a></li>
                    
                </ul>
            </li>
            @endif

            



            @if (in_array('bda-transactions', $permissions))
                <li class="treeview @if (isset($act_bda_transactions) || isset($act_bda_transactions)) {{ 'active' }} @endif">
                    <a href="javascript:void(0)">
                        <i class="fa fa-money"></i> <span>Manage BDA Transactions</span> <i
                            class="fa fa-angle-right pull-right"></i>
                    </a>
                    <ul class="treeview-menu">

                        <li class="@if (isset($act_bda_transactions)) {{ 'active' }} @endif"><a
                                href="{{ URL::to('admin/bda-transactions') }}"><i class="fa fa-circle-o"></i>BDA
                                Transactions List</a></li>

                        <!-- <li class="@if (isset($actadmintransactions)) {{ 'active' }} @endif"><a href="{{ URL::to('admin/transactions/adminTrans') }}"><i class="fa fa-circle-o"></i>Admin Transactions List</a></li> -->

                    </ul>
                </li>
            @endif
            @if (in_array('onafriq-transactions', $permissions))
                <li class="treeview @if (isset($act_onafriq_transactions) || isset($act_onafriq_transactions)) {{ 'active' }} @endif">
                    <a href="javascript:void(0)">
                        <i class="fa fa-money"></i> <span>Manage Onafriq Transactions</span> <i
                            class="fa fa-angle-right pull-right"></i>
                    </a>
                    <ul class="treeview-menu">

                        <li class="@if (isset($act_onafriq_transactions)) {{ 'active' }} @endif"><a
                                href="{{ URL::to('admin/onafriq-transactions') }}"><i
                                    class="fa fa-circle-o"></i>Onafriq Transactions List</a></li>

                        <!-- <li class="@if (isset($actadmintransactions)) {{ 'active' }} @endif"><a href="{{ URL::to('admin/transactions/adminTrans') }}"><i class="fa fa-circle-o"></i>Admin Transactions List</a></li> -->

                    </ul>
                </li>
            @endif


            @if(in_array('swaptoswap-transation',$permissions))
            <li class="treeview @if(isset($actSwap)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Manage Swap To Swap Transactions </span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                
                    <li class="@if(isset($actSwap)){{'active'}}@endif"><a href="{{HTTP_PATH}}/admin/swaptoswap-transation"><i class="fa fa-circle-o"></i>Swap To Swap Transactions List</a></li>
                    
                </ul>
            </li>@endif

            @if(in_array('reports',$permissions))
            <li class="treeview @if(isset($actearningsreports)){{'active'}}@endif">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Reports Management</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="@if(isset($actearningsreports)){{'active'}}@endif"><a href="{{HTTP_PATH}}/admin/agents/reports"><i class="fa fa-circle-o"></i>Agent Reports</a></li>
                </ul>
            </li>@endif
        </ul>
    </section>
</aside>