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
            <div class="topn_left">Help Ticket List</div>
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
                        <!-- <th style="width:5%">#</th> -->
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('ticketId', 'Ticket ID'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('name', 'Name'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('phone', 'Phone'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('categoryId', 'Subject'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('description', 'Description'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('document', 'Image/Document'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('status', 'Status'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('created_at', 'Date'));?></th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allrecord): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <!-- <th style="width:5%"><input type="checkbox" onclick="javascript:isAllSelect(this.form);"
                                name="chkRecordId[]" value="<?php echo e($allrecord->id); ?>" /></th> -->
                                <td data-title="Ticket ID"><?php echo e($allrecord->ticketId); ?></td>
                        <td data-title="User Name"><?php echo e(($allrecord->User->name ?? "").' '.($allrecord->User->lastName ?? "")); ?></td>
                        <td data-title="Phone"><?php echo e($allrecord->User->phone ?? ""); ?></td>
                        <td data-title="Category"><?php echo e($allrecord->HelpCat->name); ?></td>
                        <td data-title="Email Address"><?php echo e($allrecord->description); ?></td>
                        <td data-title="Email Address"><?php echo e($allrecord->imagePath); ?></td>
                        <td data-title="Status" id="verify_<?php echo e($allrecord->slug); ?>">
                            <?php echo e($allrecord->status); ?>

                        </td>
                        <td data-title="Date"><?php echo e($allrecord->created_at->format('M d, Y h:i A')); ?></td>
                        <td data-title="Action">
                            <div id="loderstatus<?php echo e($allrecord->id); ?>" class="right_action_lo">
                                <?php echo e(HTML::image("public/img/loading.gif", '')); ?></div>


                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                    <?php
                                    $roles = AdminsController::getRoles(Session::get('adminid'));
                                    ?>
                                    <?php $permissions = DB::table('permissions')->where('role_id', $roles)->pluck('permission_name')->toArray(); ?>
                                    <?php if(in_array('edit-driver',$permissions)): ?>
                                    <?php if($allrecord->status=="Pending"): ?>

                                    

                                    <?php endif; ?>
                                    <li><a href="<?php echo e(URL::to( 'admin/help-ticket/view-help-ticket/'.$allrecord->id)); ?>"
                                            title="View" class=""><i class="fa fa-eye"></i>View Ticket</a></li>
                                    <?php endif; ?>
                                    <!-- <li><a href="#info<?php echo $allrecord->id; ?>" title="View Driver Detail" class="" rel='facebox'><i class="fa fa-eye"></i>View Driver Detail</a></li> -->
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
            <!-- <div class="search_frm">
                <button type="button" name="chkRecordId" onclick="checkAll(true);"  class="btn btn-info">Select All</button>
                <button type="button" name="chkRecordId" onclick="checkAll(false);" class="btn btn-info">Unselect All</button>
                <?php
                $accountStatus = array(
                    'Activate' => "Activate",
                    'Deactivate' => "Deactivate",
                );
                ;
                ?>
                <div class="list_sel"><?php echo e(Form::select('action', $accountStatus,null, ['class' => 'small form-control','placeholder' => 'Action for selected record', 'id' => 'action'])); ?></div>
                <button type="submit" class="small btn btn-success btn-cons btn-info" onclick="return ajaxActionFunction();" id="submit_action">OK</button>
            </div>   -->
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
            <legend class="head_pop">Driver Details</legend>
            <div class="drt">
                <div class="admin_pop"><span>Name: </span> <label><?php echo $allrecord->name; ?></label></div>
                <div class="admin_pop"><span>Email Address: </span>
                    <label><?php echo e($allrecord->email?$allrecord->email:'N/A'); ?></label></div>
                <div class="admin_pop"><span>Phone: </span> <label><?php echo e($allrecord->phone?$allrecord->email:'N/A'); ?></label>
                </div>
        </fieldset>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?><?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/helpticket/index.blade.php ENDPATH**/ ?>