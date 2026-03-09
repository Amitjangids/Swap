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
            <div class="topn_left">Activation Cards</div>
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
                        <th style="width:5%">#</th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('drivername', 'Driver Name'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('username', 'Fullname'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('accountId', 'Account ID'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('cardtype', 'Card Type'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('created_at', 'Date'));?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allrecord): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> 
                    <tr>
                        <td data-title="ID"><?php echo e($allrecord->id); ?></td>
                        <td data-title="DriverName"><?php echo e($allrecord->driverName); ?></td>
                        <td data-title="UserName"><?php echo e($allrecord->firstName.' '.$allrecord->lastName ?? ""); ?></td>
                        <td data-title="AccountID"><?php echo e($allrecord->accountId ?? 'N/A'); ?></td>
                        <td data-title="cardType"><?php echo e($allrecord->cardType ?? 'N/A'); ?></td>
                        <td data-title="Date"><?php echo e($allrecord->created_at->format('M d, Y h:i A')); ?></td>
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
<?php endif; ?> <?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/drivers/view-activation-card.blade.php ENDPATH**/ ?>