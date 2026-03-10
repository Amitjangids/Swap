<?php use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
?>
<?php echo e(HTML::script('public/assets/js/facebox.js')); ?>

<?php echo e(HTML::style('public/assets/css/facebox.css')); ?>

<?php
    use App\Http\Controllers\Admin\AdminsController;
    use App\Http\Controllers\Admin\UsersController;
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
                <div class="manage_sec">
                    <div class="topn_left">Total Register Users List</div>
                    <div class="topn_rightd ddpagingshorting paggng-txt" id="pagingLinks" align="right">
                        <div class="topn_righ">
                            Showing <?php echo e($allrecords->count()); ?> of <?php echo e($allrecords->total()); ?> record(s).
                        </div>
                        <div class="panel-heading" style="align-items:center;">
                            <?php echo e($allrecords->appends(Request::except('_token'))->render()); ?>

                        </div>
                    </div>
                </div>
                <!-- <div class="transaction_info">
                <div class="topn_left_btsec">
                    <div class="payment_info">
                        <span class="pay_head">Total Wallet Balance</span>
                        <span class="pay_body">₣ <?php echo e(number_format($total['wallet_balance'], 2)); ?></span>
                    </div>
                </div>
            </div> -->
            </div>
            <div class="tbl-resp-listing">
                <table class="table table-bordered table-striped table-condensed cf">
                    <thead class="cf ddpagingshorting">
                        <tr>
                            <th style="width:5%">#</th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('user_type', 'User Type'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('name', 'Name'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('is_verify', 'Merchant Name'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('isBulkUser', 'Merchant Type'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('email', 'Email Address'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('phone', 'Phone'));?></th>

                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('is_verify', 'Status'));?></th>

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
                                <td data-title="User Type"><?php echo e($allrecord->user_type); ?></td>
                                <td data-title="Full Name"><?php echo e($allrecord->name); ?></td>
                                <td data-title="Full Name">
                                    <?php
                                    $merchantName = UsersController::getMerchantName($allrecord->parent_id);
                                    echo $merchantName->name;
                                    ?>
                                </td>
                                <td data-title="User Type" id="user_type-<?php echo e($allrecord->slug); ?>">
                                    <?php echo e(($merchantName->isBulkUser) == 1 ? 'Bulk' : 'Swap'); ?> Merchant
                                </td>
                                <td data-title="Email Address"><?php echo e($allrecord->email ? $allrecord->email : 'N/A'); ?></td>
                                <td data-title="Contact Number"><?php echo e($allrecord->phone); ?></td>

                                <td data-title="Status" id="verify_<?php echo e($allrecord->slug); ?>">
                                    <?php if($allrecord->is_verify == 1): ?>
                                        Activated
                                    <?php else: ?>
                                        Deactivated
                                    <?php endif; ?>
                                </td>
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
                                                    <a href="<?php echo e(URL::to('admin/users/deactivate/' . $allrecord->slug)); ?>"
                                                        title="Deactivate" class="deactivate"><i
                                                            class="fa fa-check"></i>Deactivate</a>
                                                <?php else: ?>
                                                    <a href="<?php echo e(URL::to('admin/users/activate/' . $allrecord->slug)); ?>"
                                                        title="Activate" class="activate"><i
                                                            class="fa fa-ban"></i>Activate</a>
                                                <?php endif; ?>
                                            </li>
                                            <?php
                                                $roles = AdminsController::getRoles(Session::get('adminid'));
                                            ?>


                                            <?php $permissions = DB::table('permissions')->where('role_id', $roles)->pluck('permission_name')->toArray(); ?>

                                            <?php if(in_array('all', $permissions)): ?>
                                                <li><a href="<?php echo e(URL::to('admin/users/edit-all/' . $allrecord->slug)); ?>"
                                                        title="Edit" class=""><i class="fa fa-pencil"></i>Edit
                                                        User</a></li>
                                                <!--  <li><a href="<?php echo e(URL::to('admin/users/delete/' . $allrecord->slug)); ?>" title="Delete" class="" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash-o"></i>Delete</a></li> -->
                                            <?php endif; ?>
                                            <li>
                                                <a href="#info<?php echo $allrecord->id; ?>" title="View User Detail"
                                                    class="" rel='facebox'><i class="fa fa-eye"></i>
                                                    View User Detail
                                                </a>
                                            </li>
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
                    $accountStatus = [
                        'Activate' => 'Activate User',
                        'Deactivate' => 'Deactivate User',
                        // 'Delete' => "Delete",
                    ];
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
                    <legend class="head_pop"><?php echo $allrecord->name; ?></legend>
                    <div class="drt">
                        <div class="admin_pop"><span>User Type: </span> <label>
                                <?php if(isset($allrecord->user_type)): ?>
                                    <?php echo e($allrecord->user_type); ?>

                                <?php endif; ?>
                            </label></div>
                        <div class="admin_pop"><span>Full Name: </span> <label><?php echo $allrecord->name; ?></label></div>

                        <div class="admin_pop"><span>Email Address: </span>
                            <label><?php echo e($allrecord->email ? $allrecord->email : 'N/A'); ?></label>
                        </div>
                        <div class="admin_pop"><span>Phone Number: </span> <label><?php echo $allrecord->phone; ?></label></div>

                        <!-- <div class="admin_pop"><span>City: </span>  <label><?php echo $allrecord->City ? $allrecord->City->name_en : 'N/A'; ?></label></div>
                <div class="admin_pop"><span>Area: </span>  <label><?php echo e($allrecord->Area ? $allrecord->Area->name : 'N/A'); ?></label></div> -->


                    </div>
                </fieldset>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>
<?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/users/all.blade.php ENDPATH**/ ?>