<?php echo e(HTML::script('public/assets/js/facebox.js')); ?>

<?php echo e(HTML::style('public/assets/css/facebox.css')); ?>


<style>
   section.lstng-section.amountslab-section {
    padding: 50px 0 0;
    position: relative;
}


section.lstng-section.amountslab-section .add_new_record {
    padding: 0;
    right: 0;
    top: 0;
}
</style>
<script type="text/javascript">
    $(document).ready(function ($) {
        $('.close_image').hide();
        $('a[rel*=facebox]').facebox({
            closeImage: '<?php echo HTTP_PATH; ?>/public/img/close.png'
        });
    });
</script>
<div class="admin_loader" id="loaderID"><?php echo e(HTML::image("public/img/website_load.svg", '')); ?></div>

<?php if(!$allrecords->isEmpty()): ?>
<div class="panel-body marginzero">
    <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
    <?php echo e(Form::open(array('method' => 'post', 'id' => 'actionFrom'))); ?>

    <section id="no-more-tables" class="lstng-section amountslab-section">
  
    <div class="topn">
            <div class="manage_sec">
                <div class="topn_left">Amount Slab List</div>

                <div class="topn_rightd ddpagingshorting paggng-txt" id="pagingLinks" align="right">
                    <div class="topn_righ">
                    Showing <?php echo e($allrecords->count()); ?> of <?php echo e($allrecords->total()); ?> record(s).
                    </div>
                    <div class="panel-heading" style="align-items:center;">
                    <?php echo e($allrecords->appends(Request::except('_token'))->render()); ?>

                    </div>
                    <?php echo e(Form::close()); ?>

              
                </div> 
               
            </div> 
            <?php echo e(Form::close()); ?>

          
    </div>
    
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('id', 'ID'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('min_amount', 'MIN Amonunt'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('max_amount', 'Max Amount'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('created_at', 'Date'));?></th>
                        <!-- <th class="action_dvv"> Action</th> -->
                    </tr>
                </thead>
                <tbody>
                <?php $serialNumber = 1; ?>
                <?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allrecord): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> 
              
                    <tr>
                      
                        <td data-title="ID"><?php echo e($serialNumber); ?></td>
                        <td data-title="MIN Amonunt"><?php echo e(number_format((($allrecord->min_amount - floor($allrecord->min_amount)) > 0.5 ? ceil($allrecord->min_amount) : floor($allrecord->min_amount)), 0, '', ' ') ?? 0); ?></td>
                        <td data-title="Max Amount"><?php echo e(number_format((($allrecord->max_amount - floor($allrecord->max_amount)) > 0.5 ? ceil($allrecord->max_amount) : floor($allrecord->max_amount)), 0, '', ' ') ?? 0); ?></td>
                        
                        <td data-title="Date"><?php echo e($allrecord->created_at); ?></td>
                        <td data-title="Action">
                            <div id="loderstatus<?php echo e($allrecord->id); ?>" class="right_action_lo"><?php echo e(HTML::image("public/img/loading.gif", '')); ?></div>
                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                <div class="add_new_record"><a href="<?php echo e(URL::to('admin/transactionfees/editSlab/'.$allrecord->id)); ?>" class="btn btn-default"><i class="fa fa-pencil"></i>Edit Slab Amount</a></li>
                                
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php $serialNumber++; ?>
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

<?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/transactionfees/slablist.blade.php ENDPATH**/ ?>