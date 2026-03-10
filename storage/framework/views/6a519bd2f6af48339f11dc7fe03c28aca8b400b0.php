<?php echo e(HTML::script('public/assets/js/facebox.js')); ?>

<?php echo e(HTML::style('public/assets/css/facebox.css')); ?>

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

    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="manage_sec">
                <div class="topn_left" style="font-size: 20px;">Earnings List</div>
                <div class="topn_rightd ddpagingshorting" id="pagingLinks" align="right">
                    <div class="panel-heading" style="align-items:center;">
                        <?php echo e($allrecords->appends(Request::except('_token'))->render()); ?>

                    </div>
                </div>                
            </div>   
            <div class="transaction_info">
                <div class="topn_left_btsec">
                    <div class="payment_info">
                        <span class="pay_head">Total Transaction</span>
                        <span class="pay_body"> 
                            <?php echo e(CURR); ?> <?php echo e(number_format((($total['total'] - floor($total['total'])) > 0.5 ? ceil($total['total']) : floor($total['total'])), 0, '', ' ') ?? 0); ?>


                        </span>
                    </div>
                    <div class="payment_info">
                        <span class="pay_head">Total Earning</span>
                        <span class="pay_body"> 
                            <?php echo e(CURR); ?> <?php echo e(number_format((($total['total_fee'] - floor($total['total_fee'])) > 0.5 ? ceil($total['total_fee']) : floor($total['total_fee'])), 0, '', ' ') ?? 0); ?>



                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        <!--<th style="width:5%">#</th>-->
                                                <!--<th style="width:5%">Trans Id</th>-->
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('sender', 'Sender Phone'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('receiver', 'Receiver Phone'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('id', 'Transaction ID'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('refrence_id', 'Reference ID'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('amount', 'Transaction Amount'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('transaction_amount', 'Transaction Fee'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('total_amount', 'Transaction Total'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('updated_at', 'Transaction Date'));?></th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allrecord): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> 
                    <tr>
                        <!--<th style="width:5%"><input type="checkbox" onclick="javascript:isAllSelect(this.form);" name="chkRecordId[]" value="<?php echo e($allrecord->id); ?>" /></th>-->
                                                <!--<td data-title="Transaction Id"><?php echo e($allrecord->id); ?></td>-->
                        <td data-title="SenderPhone"><?php echo e($allrecord->User->phone); ?></td>
                        <td data-title="ReceiverPhone"><?php echo e((!empty($allrecord->Receiver->phone) ? $allrecord->Receiver->phone : $allrecord->receiver_mobile)); ?></td>
                        <td data-title="Transaction ID"><?php echo e($allrecord->refrence_id); ?></td>
                        <td data-title="Reference ID"><?php echo e($allrecord->id); ?></td>
                        <td data-title="Transaction Amount"><?php echo e(CURR); ?>

                            <?php echo e(number_format((($allrecord->amount - floor($allrecord->amount)) > 0.5 ? ceil($allrecord->amount) : floor($allrecord->amount)), 0, '', ' ') ?? 0); ?>


                        </td>
                        <td data-title="Transaction Fee">
                            <?php echo e(CURR); ?> <?php echo e(number_format((($allrecord->transaction_amount - floor($allrecord->transaction_amount)) > 0.5 ? ceil($allrecord->transaction_amount) : floor($allrecord->transaction_amount)), 0, '', ' ') ?? 0); ?>


                        </td>
                        <td data-title="Transaction Total">
                            <?php echo e(CURR); ?> <?php echo e(number_format((($allrecord->total_amount - floor($allrecord->total_amount)) > 0.5 ? ceil($allrecord->total_amount) : floor($allrecord->total_amount)), 0, '', ' ') ?? 0); ?>


                        </td>

                        <td data-title="Transaction Date"><?php echo e($allrecord->updated_at->format('M d, Y h:i:s A')); ?></td>
                        <td data-title="Action">
                            <a href="#info<?php echo $allrecord->id; ?>" title="View Transaction Details" class="btn btn-primary btn-xs" rel='facebox'><i class="fa fa-eye"></i></a>

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

<?php

use App\Models\Transaction;
?>
<?php if(!$allrecords->isEmpty()): ?>
<?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allrecord): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div id="info<?php echo $allrecord->id; ?>" style="display: none;">
    <div class="nzwh-wrapper">
        <fieldset class="nzwh">
        <legend class="head_pop">Earnings Details</legend>
            <!-- <legend class="head_pop"></legend> -->
            <div class="drt">
                <div class="admin_pop"><span>Transaction ID: </span>  <label><?php echo e($allrecord->refrence_id); ?></label></div>
                <div class="admin_pop"><span>Reference ID: </span>  <label><?php echo e($allrecord->id); ?></label></div>

                <div class="admin_pop"><span>Sender Name: </span>  <label><?php echo e($allrecord->User->name); ?></label></div>
                <?php if($allrecord->Receiver): ?>
                    <?php if($allrecord->Receiver->id == 0 && $allrecord->excel_trans_id == 0): ?>
                        <div class="admin_pop"><span>Receiver Name:</span> <label>N/A</label></div>
                    <?php elseif($allrecord->Receiver->id == 0 && $allrecord->excel_trans_id != 0): ?>
                      <?php $excel=DB::table('excel_transactions')->where('id',$allrecord->excel_trans_id)->first();?>
                        <div class="admin_pop"><span>Receiver Name:</span> <label><?php echo e($excel->first_name); ?></label></div>
                    <?php else: ?>
                        <div class="admin_pop"><span>Receiver Name:</span> <label><?php echo e($allrecord->Receiver->name); ?></label></div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="admin_pop"><span>Receiver Name:</span> <label>N/A</label></div>
                <?php endif; ?>

                <div class="admin_pop"><span>Payment For: </span>  <label><?php echo e($allrecord->payment_mode); ?></label></div>

                <div class="admin_pop"><span>Transaction Amount: </span>  <label>
                    <?php echo e(CURR); ?> <?php echo e(number_format((($allrecord->amount - floor($allrecord->amount)) > 0.5 ? ceil($allrecord->amount) : floor($allrecord->amount)), 0, '', ' ') ?? 0); ?>

                    </label></div>
                <div class="admin_pop"><span>Transaction Fee: </span>  <label>
                    <?php echo e(CURR); ?> <?php echo e(number_format((($allrecord->transaction_amount - floor($allrecord->transaction_amount)) > 0.5 ? ceil($allrecord->transaction_amount) : floor($allrecord->transaction_amount)), 0, '', ' ') ?? 0); ?>

                    </label></div>
                <div class="admin_pop"><span>Transaction Total: </span>  <label>
                    <?php echo e(CURR); ?> <?php echo e(number_format((($allrecord->total_amount - floor($allrecord->total_amount)) > 0.5 ? ceil($allrecord->total_amount) : floor($allrecord->total_amount)), 0, '', ' ') ?? 0); ?>

                    </label></div>

                <div class="admin_pop"><span>Transaction Date: </span>  <label><?php echo e($allrecord->updated_at->format('M d, Y h:i:s A')); ?></label></div>

        </fieldset>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>
<?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/transactions/earning.blade.php ENDPATH**/ ?>