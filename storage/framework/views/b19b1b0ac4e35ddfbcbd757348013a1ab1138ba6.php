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
    });
</script>

<?php
                        $roles = AdminsController::getRoles(Session::get('adminid'));   
                    ?>
                
            
                    <?php $permissions = DB::table('permissions')->where('role_id',$roles)->pluck('permission_name')->toArray();?>
                     
<div class="admin_loader" id="loaderID"><?php echo e(HTML::image("public/img/website_load.svg", '')); ?></div>
<?php if(!$allrecords->isEmpty()): ?>
<div class="panel-body marginzero">
    
    <?php echo e(Form::open(array('method' => 'post', 'id' => 'actionFrom'))); ?>

    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="topn_left">Transaction Fees List</div>
            <div class="topn_rightd ddpagingshorting" id="pagingLinks" align="right">
                <div class="panel-heading" style="align-items:center;">
                    <?php echo e($allrecords->appends(Request::except('_token'))->render()); ?>

                </div>
            </div>                
        </div>
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        <!--<th style="width:5%">#</th>-->
                                                <!--<th style="width:5%">Trans Id</th>-->
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('transaction_type', 'Transaction Type'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('user_charge', 'Min Amount'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('agent_charge', 'Max Amount'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('merchant_charge', 'Fee Amount'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('merchant_charge', 'Fee Type'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Admin.username', 'Last Updated By'));?></th>
                        <!--<th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('status', 'Status'));?></th>-->
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('created_at', 'Date'));?></th>
                        <?php if(in_array('edit-transactionfees',$permissions)): ?>    
                        <th class="action_dvv"> Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
        <?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allrecord): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> 
        <tr>
            <td data-title="Transaction Type"><?php echo e(isset($allrecord->transaction_type) ? ucfirst($allrecord->transaction_type) : 'N/A'); ?></td>
            <td data-title="User Fee"><?php echo e(CURR . (isset($allrecord->min_amount) ? number_format((($allrecord->min_amount - floor($allrecord->min_amount)) > 0.5 ? ceil($allrecord->min_amount) : floor($allrecord->min_amount)), 0, '', ' ') ?? 0 : 'N/A')); ?></td>
            <td data-title="Agent Fee"><?php echo e(CURR . (isset($allrecord->max_amount) ? number_format((($allrecord->max_amount - floor($allrecord->max_amount)) > 0.5 ? ceil($allrecord->max_amount) : floor($allrecord->max_amount)), 0, '', ' ') ?? 0 : 'N/A')); ?></td>

            <td data-title="Merchant Fee"><?php echo e(isset($allrecord->fee_amount) ? $allrecord->fee_amount : 'N/A'); ?></td>
            <td data-title="Status">
                <?php if($allrecord->fee_type == 0): ?>
                    Percentage
                <?php else: ?>
                    Flat Rate
                <?php endif; ?>
            </td>
            <td data-title="Last Updated By">
                <?php echo e($allrecord->admin->username); ?> - <?php echo e($allrecord->admin->id != 1 ? 'Subadmin' : 'Admin'); ?>

            </td>
            <td data-title="Date">
                <?php if($allrecord->created_at): ?>
                    <?php echo e($allrecord->created_at->format('M d, Y')); ?>

                <?php else: ?>
                    No date available
                <?php endif; ?>
            </td>
            <?php if(in_array('edit-transactionfees',$permissions)): ?>   
            <td data-title="Action">
                <div class="btn-group">
                    <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fa fa-list"></i>
                        <span class="caret"></span>
                    </button>
                         
                    <ul class="dropdown-menu pull-right">
                  
                        <li><a href="<?php echo e(URL::to('admin/transactionfees/edit-transactionfees/'.$allrecord->slug)); ?>" title="Edit Transaction Fee"><i class="fa fa-pencil"></i> Edit Transaction Fee</a></li>
                       
                        <!-- <li><a href="<?php echo e(URL::to('admin/transactionfees/delete/'.$allrecord->id)); ?>" title="Delete" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash-o"></i> Delete</a></li> -->
                    </ul>  
                </div>
            </td>
            <?php endif; ?>
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

<?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/transactionfees/index.blade.php ENDPATH**/ ?>