<?php use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
?>
<?php echo e(HTML::script('public/assets/js/facebox.js')); ?>

<?php echo e(HTML::style('public/assets/css/facebox.css')); ?>

<?php
    use App\Http\Controllers\Admin\AdminsController;
?>
<?php
    use App\Permission;
?>
<script type="text/javascript">
    $(document).ready(function ($) {
        $('.close_image').hide();
        $('a[rel*=facebox]').facebox({
            closeImage: '<?php echo HTTP_PATH; ?>/public/img/close.png'
        });

        $('.dropdown-menu a').on('click', function (event) {
            $(this).parent().parent().parent().toggleClass('open');
        });
    });
</script>

<div class="admin_loader" id="loaderID"><?php echo e(HTML::image("public/img/website_load.svg", '')); ?></div>
<?php if(!$allrecords->isEmpty()): ?>
    <div class="panel-body marginzero">
        <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
        <?php echo e(Form::open(array('method' => 'post', 'id' => 'actionFrom'))); ?>

        <input type="hidden" name="page" value="<?php echo e($page); ?>">
        <section id="no-more-tables" class="lstng-section">
            <div class="topn">
                <div class="topn_left"> Users List</div>
                <div class="topn_rightd ddpagingshorting paggng-txt" id="pagingLinks" align="right">
                    <div class="topn_righ">
                        Showing <?php echo e($allrecords->count()); ?> of <?php echo e($allrecords->total()); ?> record(s).
                    </div>
                    <div class="panel-heading" style="align-items:center;">
                        <?php echo e($allrecords->appends(Request::except('_token'))->render()); ?>

                    </div>
                </div>
            </div>
            <div class="tbl-resp-listing">
                <table class="table table-bordered table-striped table-condensed cf">
                    <thead class="cf ddpagingshorting">
                        <tr>
                            <th style="width:5%">#</th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('name', 'Name'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('email', 'Email Address'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('phone', 'Phone'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('wallet_balance', 'Wallet Balance'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('is_verify', 'Status'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('is_kyc_done', 'KYC Status'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('created_at', 'Date'));?></th>
                            <th class="action_dvv"> Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allrecord): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <th style="width:5%"><input type="checkbox" onclick="javascript:isAllSelect(this.form);"
                                        name="chkRecordId[]" value="<?php echo e($allrecord->id); ?>" /></th>
                                <td data-title="Full Name"><?php echo e($allrecord->name); ?></td>
                                <td data-title="Email Address"><?php echo e($allrecord->email ? $allrecord->email : 'N/A'); ?></td>
                                <td data-title="Contact Number"><?php echo e($allrecord->phone); ?></td>
                                <td data-title="Wallet Balance"><?php echo e(CURR); ?><?php echo e(number_format((($allrecord->wallet_balance - floor($allrecord->wallet_balance)) > 0.5 ? ceil($allrecord->wallet_balance) : floor($allrecord->wallet_balance)), 0, '', ' ') ?? 0); ?>  </td>
                                <td data-title="Status" id="verify_<?php echo e($allrecord->slug); ?>">
                                    <?php if($allrecord->is_verify == 1): ?>
                                        Activated
                                    <?php else: ?>
                                        Deactivated
                                    <?php endif; ?>
                                </td>
                                <td data-title="KYC Status"><?php echo e(ucfirst($allrecord->kyc_status)); ?>

                                    <!-- <?php if($allrecord->kyc_status == 1): ?>
                                                    Approved
                                                    <?php elseif($allrecord->kyc_status == 2): ?>
                                                    Declined
                                                    <?php elseif($allrecord->kyc_status == 3): ?>
                                                    Not Submitted
                                                    <?php else: ?>
                                                    Pending
                                                    <?php endif; ?> -->
                                </td>
                                <td data-title="Date"><?php echo e($allrecord->created_at->format('M d, Y h:i A')); ?></td>
                                <td data-title="Action">
                                    <div id="loderstatus<?php echo e($allrecord->id); ?>" class="right_action_lo">
                                        <?php echo e(HTML::image("public/img/loading.gif", '')); ?>

                                    </div>


                                    <div class="btn-group">
                                        <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                            <i class="fa fa-list"></i>
                                            <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu pull-right">
                                            <li class="right_acdc" id="status<?php echo e($allrecord->id); ?>">
                                                <?php if($allrecord->is_verify == '1'): ?>
                                                    <a href="<?php echo e(URL::to('admin/users/deactivate/' . $allrecord->slug)); ?>"
                                                        title="Deactivate" class="deactivate"><i
                                                            class="fa fa-check"></i>Deactivate</a>
                                                <?php else: ?>
                                                    <a href="<?php echo e(URL::to('admin/users/activate/' . $allrecord->slug)); ?>"
                                                        title="Activate" class="activate"><i class="fa fa-ban"></i>Activate</a>
                                                <?php endif; ?>
                                            </li>
                                            <?php
                                                $roles = AdminsController::getRoles(Session::get('adminid'));   
                                            ?>
                                            
                                            <?php        
                                            $permissions = DB::table('permissions')->where('role_id', $roles)->pluck('permission_name')->toArray();
                                            ?>

                                            <?php if(in_array('edit-users', $permissions)): ?>
                                                <li><a href="<?php echo e(URL::to('admin/users/edit-users/' . $allrecord->slug)); ?>"
                                                        title="Edit" class=""><i class="fa fa-pencil"></i>Edit User</a></li>
                                            <?php endif; ?>
                                            <?php if(in_array('payclient', $permissions)): ?>
                                                <!--   <li><a href="<?php echo e(URL::to( 'admin/users/delete/'.$allrecord->slug)); ?>" title="Delete" class="" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash-o"></i>Delete</a></li> -->
                                                <li><a href="<?php echo e(URL::to('admin/users/payclient/' . $allrecord->slug)); ?>"
                                                        title="Pay User" class=""><i class="fa fa-money"></i>Pay User</a></li>
                                            <?php endif; ?>
                                            <?php if($allrecord->cardActiveInactive != ""): ?>
                                            <?php if(in_array('payclient', $permissions)): ?>
                                                <li><a href="<?php echo e(URL::to('admin/users/payclientrebate/' . $allrecord->slug)); ?>"
                                                        title="Pay Rebate" class=""><i class="fa fa-money"></i>Pay Rebate</a></li>
                                            <?php endif; ?>
                                            <?php endif; ?>
                                            <li><a href="#info<?php echo $allrecord->id; ?>" title="View User Detail" class=""
                                                    rel='facebox'><i class="fa fa-eye"></i>View User Detail</a></li>
                                            <?php if($allrecord->gabonStampImg != ""): ?>
                                                <li><a href="<?php echo e(URL::to('admin/users/gabon-visa-stamp/' . $allrecord->slug)); ?>"
                                                    title="Gabon Visa Stamp" class=""><i class="fa fa-file"></i>Gabon Visa Stamp</a>
                                                </li>
                                            <?php endif; ?>

                                            <?php if(in_array('kycdetail', $permissions)): ?>
                                                <li><a href="<?php echo e(URL::to('admin/users/kycdetail/' . $allrecord->slug)); ?>"
                                                        title="View KYC Details" class=""><i class="fa fa-file"></i>View KYC
                                                        Details</a></li>
                                            <?php endif; ?>
                                            <?php if(in_array('travel-document', $permissions)): ?>
                                                <li><a href="<?php echo e(URL::to('admin/users/travel-document/' . $allrecord->id)); ?>"
                                                        title="View KYC Details" class=""><i class="fa fa-file"></i>Travel Document</a></li>
                                            <?php endif; ?>
                                            <!-- <li><a href="<?php echo e(URL::to( 'admin/transactionfees/transactionfee/'.$allrecord->slug)); ?>" title="Manage Transaction Fees" class=""><i class="fa fa-file-text"></i>Transaction Fees</a></li> -->
                                            <?php if(in_array('transactionHistory', $permissions)): ?>
                                                <li><a href="<?php echo e(URL::to('admin/transactions/transactionHistory/' . $allrecord->slug)); ?>"
                                                        title="Manage Transaction History" class=""><i
                                                            class="fa fa-money"></i>Transaction History</a></li>
                                            <?php endif; ?>
                                            <!-- <li><a href="<?php echo e(URL::to( 'admin/users/homeFeatures/'.$allrecord->slug)); ?>" title="Manage Home Features" class=""><i class="fa fa-home"></i>Manage Home Features</a></li> -->
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
                <div class="search_frm">
                    <button type="button" name="chkRecordId" onclick="checkAll(true);" class="btn btn-info">Select
                        All</button>
                    <button type="button" name="chkRecordId" onclick="checkAll(false);" class="btn btn-info">Unselect
                        All</button>
                    <?php
        $accountStatus = array(
            'Activate' => "Activate User",
            'Deactivate' => "Deactivate User",
            // 'Delete' => "Delete",
        );
        ;
                            ?>
                    <div class="list_sel">
                        <?php echo e(Form::select('action', $accountStatus, null, ['class' => 'small form-control', 'placeholder' => 'Action for selected record', 'id' => 'action'])); ?>

                    </div>
                    <button type="submit" class="small btn btn-success btn-cons btn-info"
                        onclick="return ajaxActionFunction();" id="submit_action">OK</button>
                </div>
            </div>
        </section>
        <?php echo e(Form::close()); ?>

    </div>
    </div>
<?php else: ?>
    <div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
    <div class="admin_no_record">No record found.</div>
<?php endif; ?>

<?php if(!$allrecords->isEmpty()): ?>
    <?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allrecord): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div id="info<?php echo $allrecord->id; ?>" style="display: none;">
            <div class="nzwh-wrapper">
                <fieldset class="nzwh">
                    <legend class="head_pop">User Details</legend>
                    <div class="drt">
                        <div class="admin_pop"><span>User Type: </span> <label><?php if(isset($allrecord->user_type)): ?>
                        <?php echo e($allrecord->user_type); ?> <?php endif; ?></label></div>
                        <div class="admin_pop"><span>Full Name: </span> <label><?php echo $allrecord->name; ?></label></div>
                        <div class="admin_pop"><span>Wallet Balance: </span>
                            <label><?php echo e(CURR); ?><?php echo number_format((($allrecord->wallet_balance - floor($allrecord->wallet_balance)) > 0.5 ? ceil($allrecord->wallet_balance) : floor($allrecord->wallet_balance)), 0, '', ' ') ?? 0; ?></label>
                        </div>
                        <div class="admin_pop"><span>Email Address: </span>
                            <label><?php echo e($allrecord->email ? $allrecord->email : 'N/A'); ?></label>
                        </div>
                        <div class="admin_pop"><span>Phone Number: </span> <label><?php echo $allrecord->phone; ?></label></div>
                        <div class="admin_pop"><span>Date Of Birth: </span> <label><?php echo $allrecord->dob; ?></label></div>
                        <div class="admin_pop"><span>Card Type: </span> <label><?php echo ($allrecord->virtualCardType ? $allrecord->virtualCardType : "-" ); ?></label></div>
                        <div class="admin_pop"><span>Account ID: </span> <label><?php echo ($allrecord->virtualAccountId ? $allrecord->virtualAccountId : "-" ); ?> </label></div>
                        <div class="admin_pop"><span>Vitual Card Balance: </span><label><?php echo ($allrecord->virtualBalance ? CURR .number_format((($allrecord->virtualBalance - floor($allrecord->virtualBalance)) > 0.5 ? ceil($allrecord->virtualBalance) : floor($allrecord->virtualBalance)), 0, '', ' ') ?? 0 : "-" ); ?></label></div>

                        <div class="admin_pop"><span>Card Type: </span> <label><?php echo ($allrecord->physicalCardType ? $allrecord->physicalCardType : "-" ); ?></label></div>
                        <div class="admin_pop"><span>Account ID: </span> <label><?php echo ($allrecord->physicalAccountId ? $allrecord->physicalAccountId : "-" ); ?> </label></div>
                        <div class="admin_pop"><span>Physical Card Balance: </span><label><?php echo ($allrecord->physicalBalance ? CURR .number_format((($allrecord->physicalBalance - floor($allrecord->physicalBalance)) > 0.5 ? ceil($allrecord->physicalBalance) : floor($allrecord->physicalBalance)), 0, '', ' ') ?? 0 : "-" ); ?></label></div>
                        <!-- <div class="admin_pop"><span>City: </span>  <label><?php echo $allrecord->City?$allrecord->City->name_en:'N/A'; ?></label></div>
                                        <div class="admin_pop"><span>Area: </span>  <label><?php echo e($allrecord->Area?$allrecord->Area->name:'N/A'); ?></label></div> -->

                        <?php if($allrecord->profile_image != ''): ?>
                            <div class="admin_pop"><span>Profile Image</span>
                                <label><?php echo e(HTML::image(PROFILE_FULL_DISPLAY_PATH . $allrecord->profile_image, SITE_TITLE, ['style' => "max-width: 200px"])); ?></label>
                            </div>
                        <?php endif; ?> 
                    </div>
                </fieldset>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?><?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/users/index.blade.php ENDPATH**/ ?>