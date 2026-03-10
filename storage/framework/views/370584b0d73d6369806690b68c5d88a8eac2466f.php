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
<div class="admin_loader" id="loaderID"><?php echo e(HTML::image('public/img/website_load.svg', '')); ?></div>
<?php if(!$allrecords->isEmpty()): ?>
    <div class="panel-body marginzero">
        <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
        <?php echo e(Form::open(['method' => 'post', 'id' => 'actionFrom'])); ?>

        <section id="no-more-tables" class="lstng-section">
            <div class="topn">
                <div class="manage_sec">
                    <div class="topn_left">VISA Transactions List</div>

                    <div class="topn_rightd ddpagingshorting paggng-txt" id="pagingLinks" align="right">
                        <div class="topn_righ">
                            Showing <?php echo e($allrecords->count()); ?> of <?php echo e($allrecords->total()); ?> record(s).
                        </div>
                        <div class="panel-heading" style="align-items:center;">
                            <?php echo e($allrecords->appends(Request::except('_token'))->render()); ?>

                        </div>
                    </div>
                </div>
                <div class="transaction_info">
                    <div class="topn_left_btsec">
                        <div class="payment-info-parent">
                            <div class="payment_info">
                                <span class="pay_head">Total Transaction</span>
                                <span
                                    class="pay_body"><?php echo e(CURR); ?><?php echo e(intval(str_replace(',', '', $totalAmount['total_amount']))); ?></span>
                            </div>
                            <div class="payment_info">
                                <span class="pay_head">Total Earning</span>
                                <span
                                    class="pay_body"><?php echo e(CURR); ?><?php echo e(intval(str_replace(',', '', $totalAmount['total_fee']))); ?></span>
                            </div>
                        </div>
                        <div class="download_excel">
                            <a href="javascript:void(0)" class="btn btn-success export_excel">
                                Download Excel
                                <i class="fa fa-file-excel-o" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>


            <script>
                $('.export_excel').on('click', function () {

                    var name = $('input[name=name]').val();
                    var phone = $('input[name=phone]').val();
                    var refrence = $('input[name=refrence]').val();
                    var requestD = $('input[name=to]').val();
                    var accountId = $('input[name=accountId]').val();
                    var url = '<?php echo e(route("exportexcelvisa")); ?>?name=' + name + '&phone=' + phone + '&refrence=' + refrence + '&to=' + requestD + '&accountId=' + accountId;
                    window.location.href = url;
                });

            </script>


            <div class="tbl-resp-listing">
                <table class="table table-bordered table-striped table-condensed cf">
                    <thead class="cf ddpagingshorting">
                        <tr>
                            <!--<th style="width:5%">#</th>-->
                            <!--<th style="width:5%">Trans Id</th>-->
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Name', 'Name'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Phone', 'Phone'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Account ID', 'Account ID'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('transactionSourceAmount', 'Amount'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('transactionTargetAmount', 'Transaction Fee'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Total Amount', 'Total Amount'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Balance', 'Balance'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('payment_mode', 'Transaction For'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('transactionId', 'Transaction ID'));?></th>
                            <!-- <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('type', 'Transaction Type'));?></th> -->
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('status', 'Status'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('created_at', 'Transaction /Request Date'));?></th>
                            <th class="action_dvv"> Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allrecord): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td data-title="Name">
                                                        <?php echo e(isset($allrecord->name) ? ucfirst($allrecord->name) : 'N/A'); ?>

                                                    </td>
                                                    <td data-title="Phone">
                                                        <?php echo e(isset($allrecord->phone) ? ucfirst($allrecord->phone) : 'N/A'); ?>

                                                    </td>
                                                    <td data-title="Account ID">
                                                        <?php echo e(isset($allrecord->accountId) ? $allrecord->accountId : 'N/A'); ?>

                                                    </td>


                                                    <td data-title="Amount Paid">
                                                        <!-- <?php echo e(CURR); ?><?php echo e($allrecord->amount); ?> -->

                                                        <?php
                                    $rawAmount = (
                                        $allrecord->payment_mode === "TRANSAFEROUT" ||
                                        $allrecord->payment_mode === "CARDPAYMENT" ||
                                        $allrecord->description === "Card Issuance \ Activation Fee" ||
                                        $allrecord->description === "Denial - ATM Withdrawal"
                                    )
                                        ? (
                                            in_array($allrecord->description, [
                                                "Denial - POS Purchase",
                                                "Manual PIN Change",
                                                "Card Issuance \ Activation Fee",
                                                "Denial - ATM Withdrawal"
                                            ])
                                            ? $allrecord->transaction_amount
                                            : $allrecord->amount
                                        )
                                        : $allrecord->amount;

                                    // custom rounding: .5 stays same, > .5 goes up
                                    $value = (($rawAmount - floor($rawAmount)) > 0.5)
                                        ? ceil($rawAmount)
                                        : floor($rawAmount);

                                    echo CURR . number_format($value, 0, '', ' ');
                                                                                                                                                                            ?>
                                                    </td>
                                                    <td data-title="Source Amount">
                                                        <?php if($allrecord->description === "Card Load"): ?>
                                                            <?php echo e('-' . $allrecord->transaction_amount ?? 0); ?> <?php echo e(CURR); ?>

                                                        <?php else: ?>
                                                            <?php echo e(CURR); ?>

                                                            <?php echo e(number_format((($allrecord->transaction_amount - floor($allrecord->transaction_amount)) > 0.5 ? ceil($allrecord->transaction_amount) : floor($allrecord->transaction_amount)), 0, '', ' ') ?? 0); ?>

                                                        <?php endif; ?>
                                                    </td>
                                                    <td data-title="Total Amount">
                                                        <?php if($allrecord->description === "Card Load"): ?>
                                                                        <?php echo e(CURR); ?>

                                                                        <?php echo e(number_format((($allrecord->amount - $allrecord->transaction_amount) - floor($allrecord->amount - $allrecord->transaction_amount)) > 0.5
                                                            ? ceil($allrecord->amount - $allrecord->transaction_amount)
                                                            : floor($allrecord->amount - $allrecord->transaction_amount), 0, '', ' ')); ?>


                                                        <?php else: ?>
                                                            <?php echo e(CURR); ?><?php echo e(number_format((($v = $allrecord->amount + $allrecord->transaction_amount) - floor($v)) > 0.5 ? ceil($v) : floor($v), 0, '', ' ')); ?>


                                                        <?php endif; ?>

                                                    </td>
                                                    <td data-title="Balance">
                                                        <?php
                                    $val = isset($allrecord->runningBalance) ? $allrecord->runningBalance : $allrecord->runningBalance;
                                    $val = (($val - floor($val)) > 0.5) ? ceil($val) : floor($val);

                                    echo CURR . number_format($val, 0, '', ' ');
                                                                                                                ?>
                                                    </td>
                                                    <td data-title="Transaction For">
                                                        <?php 
                                                                                                                                                    echo $allrecord->description == "Denial - POS Purchase" ? "POS Denial" : $allrecord->description;
                                    /* if ($allrecord->payment_mode == "TRANSAFEROUT") {
                                        if ($allrecord->trans_type == 2) {
                                            echo "Transfer Out";
                            } else if ($allrecord->trans_type == 1) {
                            echo "Money Received from Card";
                            }
                            } else if ($allrecord->payment_mode == "CARDPAYMENT") {
                            if ($allrecord->trans_type == 2) {
                            echo "Card Recharge";
                            } else if ($allrecord->trans_type == 1) {
                            echo "Transfer IN";
                            }
                            } else {
                            echo $allrecord->payment_mode;
                            }  */
                                                                                                                                                                                                                                            ?>
                                                    </td>
                                                    <td data-title="Transaction ID">
                                                        <?php echo e($allrecord->transactionId == 0 ? $allrecord->id : $allrecord->transactionId); ?>

                                                    </td>
                                                    <!-- <td data-title="Type">
                                                                                                                                                                                                                                <?php echo e($allrecord->payment_mode); ?>

                                                                                                                                                                                                                            </td> -->
                                                    <td data-title="Status">
                                                        <?php if($allrecord->status == '1'): ?>
                                                            Completed
                                                        <?php elseif($allrecord->status == '2'): ?>
                                                            Pending
                                                        <?php elseif($allrecord->status == '3'): ?>
                                                            Failed
                                                        <?php elseif($allrecord->status == '4'): ?>
                                                            Cancelled
                                                        <?php endif; ?>
                                                    </td>
                                                    <td data-title="Transaction / Request Date">
                                                        <?php echo e($allrecord->created_at->format('M d, Y h:i:s A')); ?>

                                                    </td>
                                                    <td data-title="Action">
                                                        <a href="#info<?php echo $allrecord->id; ?>" title="View Transaction Details"
                                                            class="btn btn-primary btn-xs" rel='facebox'><i class="fa fa-eye"></i></a>
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
                    <legend class="head_pop">VISA Transactions Details</legend>
                    <div class="drt">
                        <div class="admin_pop"><span>Name : </span> <label>
                                <?php echo e(isset($allrecord->name) ? ucfirst($allrecord->name) : 'N/A'); ?>

                            </label>
                        </div>
                        <div class="admin_pop"><span>Phone : </span> <label>
                                <?php echo e(isset($allrecord->phone) ? ucfirst($allrecord->phone) : 'N/A'); ?>

                            </label>
                        </div>
                        <div class="admin_pop"><span>Amount: </span> <label>
                                <td data-title="Amount Paid">
                                    <!-- <?php echo e(CURR); ?><?php echo e($allrecord->amount); ?> -->

                                    <?php
                $rawAmount = (
                    $allrecord->payment_mode === "TRANSAFEROUT" ||
                    $allrecord->payment_mode === "CARDPAYMENT" ||
                    $allrecord->description === "Card Issuance \ Activation Fee" ||
                    $allrecord->description === "Denial - ATM Withdrawal"
                )
                    ? (
                        in_array($allrecord->description, [
                            "Denial - POS Purchase",
                            "Manual PIN Change",
                            "Card Issuance \ Activation Fee",
                            "Denial - ATM Withdrawal"
                        ])
                        ? $allrecord->transaction_amount
                        : $allrecord->amount
                    )
                    : $allrecord->amount;

                // custom rounding: .5 stays same, > .5 goes up
                $value = (($rawAmount - floor($rawAmount)) > 0.5)
                    ? ceil($rawAmount)
                    : floor($rawAmount);

                echo CURR . number_format($value, 0, '', ' ');
                                                            ?>
                                </td>
                            </label>
                        </div>
                        <div class="admin_pop"><span>Transaction Fee: </span> <label>
                                <?php if($allrecord->description === "Card Load"): ?>
                                                            <?php echo e('-' . $allrecord->transaction_amount ?? 0); ?> <?php echo e(CURR); ?>

                                                        <?php else: ?>
                                                            <?php echo e(CURR); ?>

                                                            <?php echo e(number_format((($allrecord->transaction_amount - floor($allrecord->transaction_amount)) > 0.5 ? ceil($allrecord->transaction_amount) : floor($allrecord->transaction_amount)), 0, '', ' ') ?? 0); ?>

                                                        <?php endif; ?>
                            </label>
                        </div>
                        <div class="admin_pop"><span>Total Amount: </span> <label>

                                <?php if($allrecord->description === "Card Load"): ?>
                                            <?php echo e(CURR); ?>

                                            <?php echo e(number_format((($allrecord->amount - $allrecord->transaction_amount) - floor($allrecord->amount - $allrecord->transaction_amount)) > 0.5
                                    ? ceil($allrecord->amount - $allrecord->transaction_amount)
                                    : floor($allrecord->amount - $allrecord->transaction_amount), 0, '', ' ')); ?>


                                <?php else: ?>
                                    <?php echo e(CURR); ?><?php echo e(number_format((($v = $allrecord->amount + $allrecord->transaction_amount) - floor($v)) > 0.5 ? ceil($v) : floor($v), 0, '', ' ')); ?>


                                <?php endif; ?>
                            </label>
                        </div>
                        <div class="admin_pop"><span>Transaction Type: </span> <label>
                                <?php echo e($allrecord->transactionType); ?>

                            </label>
                        </div>

                        <div class="admin_pop"><span>Status: </span> <label>
                                <?php if($allrecord->status == '1'): ?>
                                    Completed
                                <?php elseif($allrecord->status == '2'): ?>
                                    Pending
                                <?php elseif($allrecord->status == '3'): ?>
                                    Failed
                                <?php elseif($allrecord->status == '4'): ?>
                                    Cancelled
                                <?php endif; ?>
                            </label>
                        </div>

                        <?php if($allrecord->payment_mode == 'CARDPAYMENT' || $allrecord->payment_mode == 'TRANSAFEROUT'): ?>
                            <div class="admin_pop"><span>Transaction ID: </span>
                                <label><?php echo e($allrecord->transactionId == 0 ? $allrecord->id : $allrecord->transactionId); ?></label>
                            </div>
                            <div class="admin_pop"><span>Account ID: </span>
                                <label><?php echo e($allrecord->accountId); ?></label>
                            </div>
                            <?php if($allrecord->transactionDate): ?>
                                <div class="admin_pop"><span>Transaction Date: </span>
                                    <label><?php echo e($allrecord->transactionDate); ?></label>
                                </div>
                            <?php endif; ?>
                            <?php if($allrecord->transactionTime): ?>
                                <div class="admin_pop"><span>Transaction Time: </span>
                                    <label><?php echo e($allrecord->created_at->format('h:i:s')); ?></label>
                                </div>
                            <?php endif; ?>
                            <?php if($allrecord->merchantName): ?>
                                <div class="admin_pop"><span>Merchant Name: </span>
                                    <label><?php echo e($allrecord->merchantName); ?></label>
                                </div>
                            <?php endif; ?>
                            <?php if($allrecord->merchantCountry): ?>
                                <div class="admin_pop"><span>Merchant Country: </span>
                                    <label><?php echo e($allrecord->merchantCountry); ?></label>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="admin_pop"><span>Transaction/Request Date: </span>
                                <label><?php echo e($allrecord->created_at->format('M d, Y h:i:s A')); ?></label>
                            </div>
                        <?php endif; ?>
                        <!-- <div class="admin_pop"><span>Transaction Process Date: </span>  <label><?php echo e($allrecord->updated_at->format('M d, Y h:i:s A')); ?></label></div> -->

                </fieldset>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?><?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/transactions/visa_index.blade.php ENDPATH**/ ?>