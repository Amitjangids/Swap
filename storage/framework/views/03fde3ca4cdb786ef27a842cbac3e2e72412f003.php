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
<div class="admin_loader" id="loaderID"><?php echo e(HTML::image('public/img/website_load.svg', '')); ?></div>
<?php if(!$allrecords->isEmpty()): ?>
    <div class="panel-body marginzero">
        <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
        <?php echo e(Form::open(['method' => 'post', 'id' => 'actionFrom'])); ?>

        <input type="hidden" name="page" value="<?php echo e($page); ?>">
        <section id="no-more-tables" class="lstng-section">
            <div class="topn">
                <div class="topn_left">Card Request List</div>
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
                            <!-- <th style="width:5%">#</th> -->
                            <th class="sorting_paging">ID</th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('user_id', 'Username'));?></th>
                            <th class="sorting_paging">Email</th>
                            <th class="sorting_paging">Phone</th>
                            <th class="sorting_paging">Account ID</th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('status', 'Status'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('created_at', 'Date'));?></th>
                            <th class="action_dvv"> Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allrecord): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php //dd($allrecord->userCard->cardStatus); ?>
                            <tr>
                                <th><?php echo e($allrecord->id); ?></th>
                                <td data-title="Full Name"><?php echo e($allrecord->user->name); ?>

                                    <?php echo e($allrecord->user->lastName); ?></td>
                                <td data-title="Email Address">
                                    <?php echo e($allrecord->user->email ? $allrecord->user->email : 'N/A'); ?></td>
                                <td data-title="Contact Number">
                                    <?php echo e($allrecord->user->phone ? $allrecord->user->phone : 'N/A'); ?></td>
                                <td data-title="Account ID">
                                    <?php  
                                        if(isset($allrecord->userCard['cardType']) && $allrecord->userCard['cardType'] == "PHYSICAL"){
                                            echo $allrecord->userCard['accountId']; 
                                        }
                                    ?>
                                    <!-- <?php echo e($allrecord->user->accountId ? $allrecord->user->accountId : 'N/A'); ?> -->
                                </td>
                                <td data-title="Status">
                                    <?php if($allrecord->status == 0): ?>
                                        Pending
                                    <?php elseif($allrecord->status == 1): ?>
                                    <?php if(isset($allrecord->userCard->cardStatus) && $allrecord->userCard->cardStatus=="Active"): ?> 
                                        Activated
                                        <?php elseif(isset($allrecord->userCard->cardStatus) && $allrecord->userCard->cardStatus=="Inactive"): ?> 
                                        Inactive
                                        <?php else: ?>
                                        Assigned
                                    <?php endif; ?>
                                    <?php elseif($allrecord->status == 2): ?>
                                        Rejected
                                    <?php else: ?>
                                        Unknown
                                    <?php endif; ?>
                                </td>
                                <td data-title="Date"><?php echo e($allrecord->created_at->format('M d, Y h:i A')); ?></td>
                                <td data-title="Action">
                                    <a href="<?php echo e(URL::to('admin/card-assign/'.$allrecord->id)); ?>" title="View Card Request Details"
                                        class="btn btn-primary btn-xs">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php echo e(Form::close()); ?>

    </div>
    </div>
<?php else: ?>
    <div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
    <div class="admin_no_record">No record found.</div>
<?php endif; ?>
<?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/card-request/list.blade.php ENDPATH**/ ?>