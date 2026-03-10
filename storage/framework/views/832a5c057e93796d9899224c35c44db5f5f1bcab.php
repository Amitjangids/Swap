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
    $(document).ready(function($) {
        $('.close_image').hide();
        $('a[rel*=facebox]').facebox({
            closeImage: '<?php echo HTTP_PATH; ?>/public/img/close.png'
        });

        $('.dropdown-menu a').on('click', function(event) {
            $(this).parent().parent().parent().toggleClass('open');
        });
    });
</script>
<div class="admin_loader" id="loaderID"><?php echo e(HTML::image('public/img/website_load.svg', '')); ?></div>
<?php if(!$allrecords->isEmpty()): ?>
    <div class="panel-body marginzero">
        <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
        <?php echo e(Form::open(['method' => 'post', 'id' => 'actionFrom'])); ?>

        <input type="hidden" name="page" value="<?php echo e($page); ?>">
        <section id="no-more-tables" class="lstng-section">
            <div class="topn">
                <div class="topn_left">Merchant Users List</div>
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
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('business_name', 'Business Name'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('name', 'Business Owner Name'));?></th>
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
                                <th style="width:5%">
                                    <input type="checkbox" onclick="javascript:isAllSelect(this.form);"
                                        name="chkRecordId[]" value="<?php echo e($allrecord->id); ?>" />
                                </th>
                                <td data-title="Business Name"><?php echo e($allrecord->business_name); ?></td>
                                <td data-title="Business Owner Name"><?php echo e($allrecord->name); ?></td>
                                <td data-title="Email Address"><?php echo e($allrecord->email ? $allrecord->email : 'N/A'); ?></td>
                                <td data-title="Contact Number"><?php echo e($allrecord->phone); ?></td>
                                <td data-title="Wallet Balance"><?php echo e(CURR); ?><?php echo e(number_format((($allrecord->wallet_balance - floor($allrecord->wallet_balance)) > 0.5 ? ceil($allrecord->wallet_balance) : floor($allrecord->wallet_balance)), 0, '', ' ') ?? 0); ?>

                                </td>
                                <td data-title="Status" id="verify_<?php echo e($allrecord->slug); ?>">
                                    <?php if($allrecord->is_verify == 1): ?>
                                        Activated
                                    <?php else: ?>
                                        Deactivated
                                    <?php endif; ?>
                                </td>
                                <td data-title="KYC Status"><?php echo e(ucfirst($allrecord->kyc_status)); ?></td>
                                <td data-title="Date"><?php echo e($allrecord->created_at->format('M d, Y h:i A')); ?></td>
                                <td data-title="Action">
                                    <div id="loderstatus<?php echo e($allrecord->id); ?>" class="right_action_lo">
                                        <?php echo e(HTML::image('public/img/loading.gif', '')); ?></div>


                                    <div class="btn-group">
                                        <button class="btn btn-primary dropdown-toggle" type="button"
                                            data-toggle="dropdown">
                                            <i class="fa fa-list"></i>
                                            <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu pull-right">
                                            <li class="right_acdc" id="status<?php echo e($allrecord->id); ?>">

                                                <?php if($allrecord->is_verify == '1'): ?>
                                                    <a href="<?php echo e(URL::to('admin/merchants/deactivate/' . $allrecord->slug)); ?>"
                                                        title="Deactivate" class="deactivate"><i
                                                            class="fa fa-check"></i>Deactivate</a>
                                                <?php else: ?>
                                                    <a href="<?php echo e(URL::to('admin/merchants/activate/' . $allrecord->slug)); ?>"
                                                        title="Activate" class="activate"><i
                                                            class="fa fa-ban"></i>Activate</a>
                                                <?php endif; ?>
                                            </li>
                                            <?php
                                                $roles = AdminsController::getRoles(Session::get('adminid'));
                                            ?>


                                            <?php $permissions = DB::table('permissions')->where('role_id', $roles)->pluck('permission_name')->toArray(); ?>

                                            <?php if(in_array('edit-merchants', $permissions)): ?>
                                                <li>
                                                    <a href="<?php echo e(URL::to('admin/merchants/edit-merchants/' . $allrecord->slug)); ?>" title="Edit" class="">
                                                        <i class="fa fa-pencil"></i>
                                                        Edit Merchant
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <?php if(in_array('payclient', $permissions)): ?>
                                                <li>
                                                    <a href="<?php echo e(URL::to('admin/merchants/payclient/' . $allrecord->slug)); ?>" title="Pay Merchant" class="">
                                                        <i class="fa fa-money"></i>Pay Merchant
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <li>
                                                <a href="#info<?php echo $allrecord->id; ?>" title="View Merchant Detail" class="" rel='facebox'>
                                                    <i class="fa fa-eye"></i>View Merchant Detail
                                                </a>
                                            </li>
                                            
                                            <?php if(in_array('transactionHistory', $permissions)): ?>
                                                <li><a href="<?php echo e(URL::to('admin/transactions/transactionHistory/' . $allrecord->slug)); ?>"
                                                        title="Manage Transaction History" class=""><i
                                                            class="fa fa-money"></i>Transaction History</a></li>
                                            <?php endif; ?>
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
                    <?php $accountStatus = [
                        'Activate' => 'Activate Merchant',
                        'Deactivate' => 'Deactivate Merchant',
                    ]; ?>
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
                    <legend class="head_pop">Merchant Details</legend>
                    <div class="drt">
                        <div class="admin_pop"><span>User Type: </span> <label>
                                <?php if(isset($allrecord->user_type)): ?>
                                    <?php echo e($allrecord->user_type); ?>

                                <?php endif; ?>
                            </label></div>
                        <div class="admin_pop"><span>Business Name: </span> <label><?php echo $allrecord->business_name; ?></label>
                        </div>
                        <div class="admin_pop"><span>Business Owner Name: </span>
                            <label><?php echo $allrecord->name; ?></label>
                        </div>
                        <div class="admin_pop"><span>Wallet Balance: </span> <label><?php echo CURR . ' ' . number_format((($allrecord->wallet_balance - floor($allrecord->wallet_balance)) > 0.5 ? ceil($allrecord->wallet_balance) : floor($allrecord->wallet_balance)), 0, '', ' ') ?? 0; ?></label>
                        </div>
                        <div class="admin_pop"><span>Business Email: </span>
                            <label><?php echo e($allrecord->email ? $allrecord->email : 'N/A'); ?></label>
                        </div>
                        <div class="admin_pop"><span>Phone Number: </span> <label><?php echo $allrecord->phone; ?></label></div>
                        <div class="admin_pop"><span>Date Of Birth: </span> <label><?php echo $allrecord->dob; ?></label>
                        </div>
                        <?php if($allrecord->profile_image != ''): ?>
                            <div class="admin_pop"><span>Profile Image</span>
                                <label><?php echo e(HTML::image(PROFILE_FULL_DISPLAY_PATH . $allrecord->profile_image, SITE_TITLE, ['style' => 'max-width: 200px'])); ?></label>
                            </div>
                        <?php endif; ?>
                </fieldset>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>
<?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/bulk-merchants/index.blade.php ENDPATH**/ ?>