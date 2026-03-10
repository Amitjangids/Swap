<?php
use App\Http\Controllers\Admin\AdminsController;
?>
<?php
use App\Permission;
?>
<aside class="main-sidebar">
    <section class="sidebar">

            <?php
                $roles = AdminsController::getRoles(Session::get('adminid'));   
            ?>
           
      
            <?php 
                $permissions = DB::table('permissions')->where('role_id',$roles)->pluck('permission_name')->toArray();
            ?>
        <ul class="sidebar-menu">
          <?php if(in_array('dashboard',$permissions)): ?>
            <li class="treeview <?php if(isset($actdashboard)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i> <span>Dashboard</span>
             </a>
            </li>
           <?php endif; ?>
          
            
           <?php if(in_array('change-username', $permissions) ||
                in_array('change-password', $permissions) ||
                in_array('department', $permissions) ||
                in_array('transactions-limit', $permissions) ||
                in_array('customer-transaction-limit', $permissions) ||
                in_array('merchant-transaction-limit', $permissions) ||
                in_array('change-referral-bonus', $permissions) ||
                in_array('agent-transaction-limit', $permissions)): ?>


                <li class="treeview <?php if(isset($actchangeusername) || isset($actchangepassword) || isset($actchangeemail) || isset($actchangecommission) || isset($actfeatures) || isset($actconfigrole) || isset($actchangelimit) || isset($actconfigtranslimit) || isset($actmerchantlimit) || isset($actagentlimit) || isset($actchangereferralbonus)): ?><?php echo e('active'); ?><?php endif; ?>">
                    <a href="javascript:void(0)">
                        <i class="fa fa-gears"></i> <span>Configuration</span> <i class="fa fa-angle-right pull-right"></i>
                    </a>
                    <ul class="treeview-menu">
                        <?php if(in_array('change-username',$permissions)): ?>
                        <li class="<?php if(isset($actchangeusername)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/admins/change-username')); ?>"><i class="fa fa-circle-o"></i> Change Username</a></li>
                        <?php endif; ?>
                        <?php if(in_array('change-password',$permissions)): ?>
                        <li class="<?php if(isset($actchangepassword)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/admins/change-password')); ?>"><i class="fa fa-circle-o"></i> Change Password</a></li>
                        <?php endif; ?>
                        <?php if(in_array('department',$permissions)): ?>
                        <li class="<?php if(isset($actconfigrole)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/admins/department')); ?>"><i class="fa fa-circle-o"></i> Configure Department</a></li>
                        <?php endif; ?>
                        <?php if(in_array('transactions-limit',$permissions)): ?>
                        <li class="<?php if(isset($actconfigtranslimit)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/users/transactions-limit')); ?>"><i class="fa fa-circle-o"></i> Configure Trans. Limit</a></li>
                        <?php endif; ?>
                        <?php if(in_array('customer-transaction-limit',$permissions)): ?>
                        <li class="<?php if(isset($actchangelimit)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/admins/change-limit/customer-transaction-limit')); ?>"><i class="fa fa-circle-o"></i> User Transactions Limit</a></li>
                        <?php endif; ?>
                        <?php if(in_array('merchant-transaction-limit',$permissions)): ?>
                        <li class="<?php if(isset($actmerchantlimit)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/admins/change-limit/merchant-transaction-limit')); ?>"><i class="fa fa-circle-o"></i> Merchant Transactions Limit</a></li>
                        <?php endif; ?>
                        <?php if(in_array('agent-transaction-limit',$permissions)): ?>
                        <li class="<?php if(isset($actagentlimit)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/admins/change-limit/agent-transaction-limit')); ?>"><i class="fa fa-circle-o"></i> Agent Transactions Limit</a></li>
                        <?php endif; ?>
                        <?php if(in_array('change-referral-bonus',$permissions)): ?>
                        <li class="<?php if(isset($actchangereferralbonus)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/admins/change-referral-bonus')); ?>"><i class="fa fa-circle-o"></i> Change Referral Bonus</a></li>
                        <?php endif; ?>
                      
                    </ul>
                </li>   
                <?php endif; ?>
                
            <?php if(in_array('company-list', $permissions) ||
            in_array('add-company', $permissions) ||
            in_array('edit-company', $permissions)): ?>
            <li class="treeview <?php if(isset($actcompanieslist)): ?><?php echo e('active'); ?><?php endif; ?>">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Manage Companies List </span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="<?php if(isset($actcompanieslist)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(HTTP_PATH); ?>/admin/admins/company-list"><i class="fa fa-circle-o"></i>Companies List</a></li>
                </ul>
            </li>   
            <?php endif; ?>
            
            
            <li class="treeview <?php if(isset($actcardrequestlist) || isset($actcardassignlist)): ?><?php echo e('active'); ?><?php endif; ?>">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Manage Card Request List </span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="<?php if(isset($actcardrequestlist) || isset($actcardassignlist)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(HTTP_PATH); ?>/admin/card-request/list"><i class="fa fa-circle-o"></i>
                        Card Request List</a></li>
                </ul>
            </li>   
            
                
              

                <?php if(in_array('users', $permissions) ||
                in_array('agents', $permissions) ||
                in_array('department', $permissions) ||
                in_array('merchants', $permissions) ||
                in_array('all', $permissions) ||
                in_array('loginusers', $permissions) ||
                in_array('bulk-payment-merchants',$permissions)): ?>
            <li class="treeview <?php if(
                isset($actusers) 
                || isset($actagents) 
                || isset($actmerchants) 
                || isset($actallusers) 
                || isset($bulkpaymentmerchant) 
                || isset($actusers3)
                ): ?><?php echo e('active'); ?><?php endif; ?>">
                <a href="javascript:void(0)">
                    <i class="fa fa-user"></i> <span>Manage Users</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <?php if(in_array('users',$permissions)): ?>
                    <li class="<?php if(isset($actusers)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/users')); ?>"><i class="fa fa-circle-o"></i> Users List</a></li>
                    <?php endif; ?>
                    <?php if(in_array('agents',$permissions)): ?>
                    <li class="<?php if(isset($actagents)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/agents')); ?>"><i class="fa fa-circle-o"></i>Agent Users List</a></li>
                    <?php endif; ?>
                    <?php if(in_array('merchants',$permissions)): ?>
                    <li class="<?php if(isset($actmerchants)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/merchants')); ?>"><i class="fa fa-circle-o"></i>Merchant Users List</a></li>
                    <?php endif; ?>
                    <?php if(in_array('all',$permissions)): ?>
                    <li class="<?php if(isset($actallusers)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/users/all')); ?>"><i class="fa fa-circle-o"></i>Approver/Submitter List</a></li>
                    <?php endif; ?>
                    <?php if(in_array('bulk-payment-merchants',$permissions)): ?>
                        <li class="<?php if(isset($bulkpaymentmerchant)): ?> active <?php endif; ?>">
                            <a href="<?php echo e(URL::to('admin/bulk-payment-merchants')); ?>">
                                <i class="fa fa-circle-o"></i>Bulk Payment Merchant List
                            </a>
                        </li>
                    <?php endif; ?>
                    <!-- <?php if(in_array('loginusers',$permissions)): ?>
                    <li class="<?php if(isset($actusers3)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/users/loginusers')); ?>"><i class="fa fa-circle-o"></i>Logged In Users Report</a></li>
                    <?php endif; ?> -->
                </ul>
            </li>
          <?php endif; ?>

                
                
            <?php if(in_array('subadmins',$permissions)): ?>
                <li class="treeview <?php if(isset($actsubadmins)): ?><?php echo e('active'); ?><?php endif; ?>">
                    <a href="javascript:void(0)">
                        <i class="fa fa-users"></i> <span>Manage Sub Admins</span> <i class="fa fa-angle-right pull-right"></i>
                    </a>
                    <ul class="treeview-menu">
                       
                        <li class="<?php if(isset($actsubadmins)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/subadmins')); ?>"><i class="fa fa-circle-o"></i>Sub Admins List</a></li>
                        
                    </ul>
                </li>
                <?php endif; ?>

                <?php if(in_array('driver-list',$permissions)): ?>
                <li class="treeview <?php if(isset($actdrivers)): ?><?php echo e('active'); ?><?php endif; ?>">
                    <a href="javascript:void(0)">
                        <i class="fa fa-users"></i> <span>Manage Drivers</span> <i class="fa fa-angle-right pull-right"></i>
                    </a>
                    <ul class="treeview-menu">
                       
                        <li class="<?php if(isset($actdrivers)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/drivers')); ?>"><i class="fa fa-circle-o"></i>Drivers List</a></li>
                        
                    </ul>
                </li>
                <?php endif; ?>

                <?php if(in_array('location-list',$permissions)): ?>
                <li class="treeview <?php if(isset($actlocations)): ?><?php echo e('active'); ?><?php endif; ?>">
                    <a href="javascript:void(0)">
                        <i class="fa fa-users"></i> <span>Manage Ecobank Location</span> <i class="fa fa-angle-right pull-right"></i>
                    </a>
                    <ul class="treeview-menu">
                       
                        <li class="<?php if(isset($actlocations)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/locations')); ?>"><i class="fa fa-circle-o"></i>Ecobank Location List</a></li>
                        
                    </ul>
                </li>
                <?php endif; ?>

                <?php if(in_array('help-ticket',$permissions)): ?>
                <li class="treeview <?php if(isset($acthelpticket)): ?><?php echo e('active'); ?><?php endif; ?>">
                    <a href="javascript:void(0)">
                        <i class="fa fa-users"></i> <span>Help Center</span> <i class="fa fa-angle-right pull-right"></i>
                    </a>
                    <ul class="treeview-menu">
                       
                        <li class="<?php if(isset($acthelpticket)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/help-ticket')); ?>"><i class="fa fa-circle-o"></i>Customer Support</a></li>
                        
                    </ul>
                </li>
                <?php endif; ?>
                
         
                
       
              
                <?php if(in_array('banners',$permissions)): ?>
            <li class="treeview <?php if(isset($actbanners)): ?><?php echo e('active'); ?><?php endif; ?>">
                <a href="javascript:void(0)">
                    <i class="fa fa-picture-o"></i> <span>Manage Banners</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                
                       
                    <li class="<?php if(isset($actbanners)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/banners')); ?>"><i class="fa fa-circle-o"></i>Banners List</a></li>
                    
                </ul>
            </li>
            <?php endif; ?>
                
          
                
                <?php if(in_array('transactionfees',$permissions) || in_array('amountSlab',$permissions)): ?>
                        <li class="treeview <?php if(isset($acttranfees) || isset($acttranfees1)): ?><?php echo e('active'); ?><?php endif; ?>">
                            <a href="javascript:void(0)">
                                <i class="fa fa-money"></i> <span>Manage Transactions Fees</span> <i class="fa fa-angle-right pull-right"></i>
                            </a>
                             <ul class="treeview-menu">
                                <?php if( in_array('amountSlab',$permissions)): ?>                           
                                    <li class="<?php if(isset($acttranfees1)): ?> <?php echo e('active'); ?> <?php endif; ?>">
                                        <a href="<?php echo e(HTTP_PATH); ?>/admin/transactionfees/amountSlab">
                                            <i class="fa fa-circle-o"></i>
                                            Amount Slab List
                                        </a>
                                    </li>
                                    <?php endif; ?>

                          
                                    <?php if(in_array('transactionfees',$permissions)): ?>
                                    <li class="<?php if(isset($acttranfees)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/transactionfees')); ?>"><i class="fa fa-circle-o"></i>Transaction Fees List</a></li>
                                    <?php endif; ?>
                            </ul>
                        </li>
                       
                        <?php endif; ?>
           
                        <?php if(in_array('transactions',$permissions)): ?>
            <li class="treeview <?php if(isset($acttransactions) || isset($actadmintransactions)): ?><?php echo e('active'); ?><?php endif; ?>">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Manage Transactions</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
               
                    <li class="<?php if(isset($acttransactions)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/transactions')); ?>"><i class="fa fa-circle-o"></i>Transactions List</a></li>
                  
                            <!-- <li class="<?php if(isset($actadmintransactions)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/transactions/adminTrans')); ?>"><i class="fa fa-circle-o"></i>Admin Transactions List</a></li> -->
                  
                </ul>
            </li>
            <?php endif; ?>
                        
            <?php if(in_array('referral-listing',$permissions)): ?>
            <li class="treeview <?php if(isset($actreferral) || isset($actreferralfees)): ?><?php echo e('active'); ?><?php endif; ?>">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Manage Referral</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="<?php if(isset($actreferral)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/referral-listing')); ?>"><i class="fa fa-circle-o"></i>Referral Earning List</a></li>
                    <?php if(in_array('referral-setting', $permissions) ): ?>
                    <li class="<?php if(isset($actreferralfees)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/referral-setting')); ?>"><i class="fa fa-circle-o"></i>Referral Setting</a></li>
                  <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>
            
           
            <?php if(in_array('earning',$permissions)): ?>
            <li class="treeview <?php if(isset($actearnings)): ?><?php echo e('active'); ?><?php endif; ?>">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Balance Management</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                
                    <li class="<?php if(isset($actearnings)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(URL::to('admin/transactions/earning')); ?>"><i class="fa fa-circle-o"></i>Balance Management List</a></li>
                    
                </ul>
            </li>
            <?php endif; ?>
                

                
            <?php if(in_array('pages',$permissions)): ?>
            <li class="treeview <?php if(isset($actpages)): ?><?php echo e('active'); ?><?php endif; ?>">
                <a href="javascript:void(0)">
                    <i class="fa fa-file-text-o"></i> <span>Manage Pages</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                   
                    <li class="<?php if(isset($actpages)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(HTTP_PATH); ?>/admin/pages"><i class="fa fa-circle-o"></i>Page List</a></li>
                    
                </ul>
            </li>
            <?php endif; ?>

            <?php if(in_array('cardcontents',$permissions)): ?>
            <li class="treeview <?php if(isset($actcardcontents)): ?><?php echo e('active'); ?><?php endif; ?>">
                <a href="javascript:void(0)">
                    <i class="fa fa-file-text-o"></i> <span>Manage Card Content</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                   
                    <li class="<?php if(isset($actcardcontents)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(HTTP_PATH); ?>/admin/cardcontents"><i class="fa fa-circle-o"></i>Card Content</a></li>
                    
                </ul>
            </li>
            <?php endif; ?>

            <?php if(in_array('gemic-transation',$permissions)): ?>
            <li class="treeview <?php if(isset($actgemic)): ?><?php echo e('active'); ?><?php endif; ?>">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Manage Gimac Transactions </span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                
                    <li class="<?php if(isset($actgemic)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(HTTP_PATH); ?>/admin/gemic-transation"><i class="fa fa-circle-o"></i>Gimac Transactions List</a></li>
                    
                </ul>
            </li>
            <?php endif; ?>

            



            <?php if(in_array('bda-transactions', $permissions)): ?>
                <li class="treeview <?php if(isset($act_bda_transactions) || isset($act_bda_transactions)): ?> <?php echo e('active'); ?> <?php endif; ?>">
                    <a href="javascript:void(0)">
                        <i class="fa fa-money"></i> <span>Manage BDA Transactions</span> <i
                            class="fa fa-angle-right pull-right"></i>
                    </a>
                    <ul class="treeview-menu">

                        <li class="<?php if(isset($act_bda_transactions)): ?> <?php echo e('active'); ?> <?php endif; ?>"><a
                                href="<?php echo e(URL::to('admin/bda-transactions')); ?>"><i class="fa fa-circle-o"></i>BDA
                                Transactions List</a></li>

                        <!-- <li class="<?php if(isset($actadmintransactions)): ?> <?php echo e('active'); ?> <?php endif; ?>"><a href="<?php echo e(URL::to('admin/transactions/adminTrans')); ?>"><i class="fa fa-circle-o"></i>Admin Transactions List</a></li> -->

                    </ul>
                </li>
            <?php endif; ?>
            <?php if(in_array('onafriq-transactions', $permissions)): ?>
                <li class="treeview <?php if(isset($act_onafriq_transactions) || isset($act_onafriq_transactions)): ?> <?php echo e('active'); ?> <?php endif; ?>">
                    <a href="javascript:void(0)">
                        <i class="fa fa-money"></i> <span>Manage Onafriq Transactions</span> <i
                            class="fa fa-angle-right pull-right"></i>
                    </a>
                    <ul class="treeview-menu">

                        <li class="<?php if(isset($act_onafriq_transactions)): ?> <?php echo e('active'); ?> <?php endif; ?>"><a
                                href="<?php echo e(URL::to('admin/onafriq-transactions')); ?>"><i
                                    class="fa fa-circle-o"></i>Onafriq Transactions List</a></li>

                        <!-- <li class="<?php if(isset($actadmintransactions)): ?> <?php echo e('active'); ?> <?php endif; ?>"><a href="<?php echo e(URL::to('admin/transactions/adminTrans')); ?>"><i class="fa fa-circle-o"></i>Admin Transactions List</a></li> -->

                    </ul>
                </li>
            <?php endif; ?>


            <?php if(in_array('swaptoswap-transation',$permissions)): ?>
            <li class="treeview <?php if(isset($actSwap)): ?><?php echo e('active'); ?><?php endif; ?>">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Manage Swap To Swap Transactions </span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                
                    <li class="<?php if(isset($actSwap)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(HTTP_PATH); ?>/admin/swaptoswap-transation"><i class="fa fa-circle-o"></i>Swap To Swap Transactions List</a></li>
                    
                </ul>
            </li><?php endif; ?>
            
            <?php if(in_array('airtel-transactions',$permissions)): ?>
            <li class="treeview <?php if(isset($act_airtel_transactions)): ?><?php echo e('active'); ?><?php endif; ?>">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Manage Airtel Money </span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                
                    <li class="<?php if(isset($act_airtel_transactions)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(HTTP_PATH); ?>/admin/airtel-transaction"><i class="fa fa-circle-o"></i>Airtel Money Transactions List</a></li>
                    
                </ul>
            </li><?php endif; ?>

            <?php if(in_array('airtel-transactions',$permissions)): ?>
            <li class="treeview <?php if(isset($act_visa_transactions)): ?><?php echo e('active'); ?><?php endif; ?>">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Manage VISA Transactions </span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                
                    <li class="<?php if(isset($act_visa_transactions)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(HTTP_PATH); ?>/admin/visa-transaction"><i class="fa fa-circle-o"></i>VISA Transactions List</a></li>
                    
                </ul>
            </li><?php endif; ?>

            <?php if(in_array('external-transactions',$permissions)): ?>
            <li class="treeview <?php if(isset($act_external_transactions)): ?><?php echo e('active'); ?><?php endif; ?>">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Manage External Transactions </span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                
                    <li class="<?php if(isset($act_external_transactions)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(HTTP_PATH); ?>/admin/external-transaction"><i class="fa fa-circle-o"></i>External Transactions List</a></li>
                    
                </ul>
            </li><?php endif; ?>

            <!-- <li class="<?php if(isset($act_airtel_transactions)): ?> <?php echo e('active'); ?> <?php endif; ?>">
            <li class="treeview <?php if(isset($act_airtel_transactions) || isset($act_airtel_transactions)): ?> <?php echo e('active'); ?> <?php endif; ?>"></li> -->

            <?php if(in_array('reports',$permissions)): ?>
            <li class="treeview <?php if(isset($actearningsreports)): ?><?php echo e('active'); ?><?php endif; ?>">
                <a href="javascript:void(0)">
                    <i class="fa fa-money"></i> <span>Reports Management</span> <i class="fa fa-angle-right pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li class="<?php if(isset($actearningsreports)): ?><?php echo e('active'); ?><?php endif; ?>"><a href="<?php echo e(HTTP_PATH); ?>/admin/agents/reports"><i class="fa fa-circle-o"></i>Agent Reports</a></li>
                </ul>
            </li><?php endif; ?>
        </ul>
    </section>
</aside><?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/left_menu.blade.php ENDPATH**/ ?>