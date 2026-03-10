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

    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="topn_left">Transaction Limit</div>
            <div class="topn_rightd ddpagingshorting" id="pagingLinks" align="right">
                <div class="panel-heading" style="align-items:center;">
                   
                </div>
            </div>                
        </div>
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('id', 'ID'));?></th>
                        <!-- <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('account_category', 'Membership Name'));?></th> -->
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('category_for', 'Membership Type'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('daily_limit', 'Daily Limit'));?></th>
						<th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('week_limit', 'Week Limit'));?></th>
						<th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('month_limit', 'Month Limit'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('edited_by', 'Edited By'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('updated_at', 'Date'));?></th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allrecord): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
					<?php
					$editedBy = getAdminNameById($allrecord->edited_by);
					?>
                    <tr>
                        <td data-title="ID"><?php echo e($allrecord->id); ?></td>
						<!-- <td data-title="Membership Name"><?php echo e($allrecord->account_category); ?></td> -->
                        <td data-title="Membership Type">
						<?php if($allrecord->category_for == 1): ?>
						<?php echo e('Customer'); ?>

                        <?php elseif($allrecord->category_for == 2): ?>
						<?php echo e('Merchant'); ?>

						<?php else: ?>
						<?php echo e('Agent'); ?>

						<?php endif; ?>
						</td>
                        <td data-title="Daily Limit"><?php echo e(number_format((($allrecord->daily_limit - floor($allrecord->daily_limit)) > 0.5 ? ceil($allrecord->daily_limit) : floor($allrecord->daily_limit)), 0, '', ' ') ?? 0); ?></td>
						<td data-title="Week Limit"><?php echo e(number_format((($allrecord->week_limit - floor($allrecord->week_limit)) > 0.5 ? ceil($allrecord->week_limit) : floor($allrecord->week_limit)), 0, '', ' ') ?? 0); ?></td>
                        <td data-title="Month Limit"><?php echo e(number_format((($allrecord->month_limit - floor($allrecord->month_limit)) > 0.5 ? ceil($allrecord->month_limit) : floor($allrecord->month_limit)), 0, '', ' ') ?? 0); ?></td>
                        <td data-title="Last Edited By"><?php echo e($editedBy); ?></td>
                        <td data-title="Date"><?php echo e($allrecord->updated_at->format('M d, Y h:i A')); ?></td>
                        <td data-title="Action">
                            <div id="loderstatus<?php echo e($allrecord->id); ?>" class="right_action_lo"><?php echo e(HTML::image("public/img/loading.gif", '')); ?></div>
                            
                            <?php echo e(Form::close()); ?>

                            <?php
                                $roles = AdminsController::getRoles(Session::get('adminid'));   
                            ?>
                        
                    
                            <?php $permissions = DB::table('permissions')->where('role_id',$roles)->pluck('permission_name')->toArray();?>
                            <?php if(in_array('edit-transaction-limit',$permissions)): ?>
                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
								<li><a href="<?php echo e(URL::to('admin/users/edit-transaction-limit/'.$allrecord->id)); ?>" title="Edit Membership Limit" class=""><i class="fa fa-edit"></i>Edit Limit</a></li>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
           <!-- <div class="search_frm">
                <button type="button" name="chkRecordId" onclick="checkAll(true);"  class="btn btn-info">Select All</button>
                <button type="button" name="chkRecordId" onclick="checkAll(false);" class="btn btn-info">Unselect All</button>
                <?php $accountStatus = array(
				'Verify' => "Approve Request"
				); ?>
                <div class="list_sel"><?php echo e(Form::select('action', $accountStatus,null, ['class' => 'small form-control','placeholder' => 'Action for selected record', 'id' => 'action'])); ?></div>
                <button type="submit" class="small btn btn-success btn-cons btn-info" onclick="return ajaxActionFunction();" id="submit_action">OK</button>
            </div> -->    
        </div>
    </section>
    <?php echo e(Form::close()); ?>

</div>         
</div> 
<?php else: ?> 
<div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
<div class="admin_no_record">No record found.</div>
<?php endif; ?><?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/users/limitTrans.blade.php ENDPATH**/ ?>