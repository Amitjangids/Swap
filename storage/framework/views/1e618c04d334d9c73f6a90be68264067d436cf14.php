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
                    <div class="topn_left">Transactions List</div>

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
                                <span class="pay_body"><?php echo e(CURR); ?>&nbsp;
                                    <?php echo e(number_format(intval(str_replace(
            ',',
            '',
            $totalamount
        )), 0, '.', ' ')); ?></span>

                            </div>
                            <div class="payment_info">
                                <span class="pay_head">Total Earning</span>
                                <span class="pay_body"><?php echo e(CURR); ?>&nbsp;<?php echo e(number_format(intval(str_replace(
            ',',
            '',
            $totalfee
        )), 0, '.', ' ')); ?>

                            </div></span>

                        </div>
                        <div class="download_excel">
                            <a href="javascript:void(0)" class="btn btn-success export_excel">Download Excel <i
                                    class="fa fa-file-excel-o" aria-hidden="true"></i></a>
                        </div>
                    </div>
                </div>

                <script>
                                        /* $('.export_excel').on('click', function () {
                                            // alert('ok');
                                            window.location.href = '<?php echo e(HTTP_PATH); ?>/admin/transactions / export_excel_individual/<?php echo e($slug); ?>';
                                        }); */


                    $('.export_excel').on('click', function () {
                        var sender = $('input[name=sender]').val();
                        var sender_phone = $('input[name=sender_phone]').val();
                        var receiver = $('input[name=receiver]').val();
                        var receiver_phone = $('input[name=receiver_phone]').val();
                        var forS = $('select[name=for]').val();
                        var refrence = $('input[name=refrence]').val();
                        var requestD = $('input[name=to]').val();
                        var accountId = $('input[name=accountId]').val();
                        console.log(forS);
                        var url = '<?php echo e(HTTP_PATH); ?>/admin/transactions/export_excel_individual?sender=' + sender + '&sender_phone=' + sender_phone + ' & receiver=' + receiver + ' & receiver_phone=' + receiver_phone + '&fors=' + forS + '&refrence=' + refrence + '&to=' + requestD + '&accountId=' + accountId + '&slug=' + "<?php    echo $slug ?>";
                        window.location.href = url;
                    });

                </script>

            </div>
            <div class="tbl-resp-listing">
                <table class="table table-bordered table-striped table-condensed cf">
                    <thead class="cf ddpagingshorting">
                        <tr>
                            <!--<th style="width:5%">#</th>-->
                            <!--<th style="width:5%">Trans Id</th>-->
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('User.name', 'Sender Name'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('User.phone', 'Sender Phone'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Receiver.name', 'Receiver Name'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Receiver.phone', 'Receiver Phone / Account ID'));?></th>
                            <?php    /* <th class="sorting_paging">@sortablelink('trans_type', 'Transaction Type')</th> */ ?>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('payment_mode', 'Transaction For'));?></th>
                            <!-- <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('company_name', 'Company Name'));?></th> -->
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('amount', 'Amount'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('transaction_amount', 'Transaction Fee'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('total_amount', 'Total Amount'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Receiver.phone', 'Balance'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('refrence_id', 'Transaction ID'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('status', 'Status'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('created_at', 'Transaction / Request Date'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('updated_at', 'Transaction Process Date'));?></th>
                            <th class="action_dvv"> Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allrecord): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                        <tr>
                                            <?php if($allrecord->user_id == 1 && $allrecord->trans_for == 'Admin'): ?>
                                                            <?php
                                                if ($allrecord->trans_to != "") {
                                                    $admin = DB::table('admins')->where('id', $allrecord->trans_to)->first();
                                                } else {
                                                    $admin = DB::table('admins')->where('id', 1)->first();
                                                }
                                                                                                                                                                                                                                                                                                    ?>
                                                            <td data-title="Sender Name"><?php echo e(isset($admin->username) ? ucfirst($admin->username) : 'N/A'); ?>

                                                            </td>
                                                            <td data-title="Sender Phone"><?php echo e('N/A'); ?></td>
                                            <?php else: ?>
                                                <?php if($allrecord->payment_mode == "airtelwallet"): ?>
                                                    <td data-title="Sender Name">Airtel Money</td>
                                                    <td data-title="Sender Phone">
                                                        <?php echo e(isset($allrecord->receiver_mobile) ? ucfirst($allrecord->receiver_mobile) : 'N/A'); ?>

                                                    </td>
                                                <?php else: ?>
                                                        <td data-title="Sender Name"><?php echo e(isset($allrecord->User->name) ? ucfirst($allrecord->User->name)
                                                    : 'N/A'); ?></td>
                                                        <td data-title="Sender Phone"><?php echo e(isset($allrecord->User->phone) ?
                                                    ucfirst($allrecord->User->phone) : 'N/A'); ?></td>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <?php if($allrecord->receiver_id == 0 && $allrecord->receiver_mobile != ''): ?>
                                                        <td data-title="Receiver Name">
                                                            <?php echo e(isset($allrecord->ExcelTransaction->first_name) ?
                                                ucfirst($allrecord->ExcelTransaction->first_name) : 'N/A'); ?>

                                                        </td>
                                                        <td data-title="Receiver Phone">
                                                            <?php echo e($allrecord->receiver_mobile); ?>

                                                        </td>
                                            <?php elseif($allrecord->receiver_id == 0 && $allrecord->receiver_mobile == ''): ?>
                                                            <td data-title="Receiver Name">
                                                                <?php            $admin = DB::table('admins')->where('id', 1)->first(); ?>
                                                                <?php 
                                                                                                                                                                                                                                                    if ($allrecord->payment_mode == "TRANSAFEROUT" || $allrecord->payment_mode == "CARDPAYMENT") {
                                                    echo isset($allrecord->User->name) ? ucfirst($allrecord->User->name) : 'N/A';
                                                } else {
                                                    echo isset($admin->username) ? ucfirst($admin->username) : 'N/A';
                                                }   
                                                                                                                                                                                                                                                                                                        ?>
                                                            </td>
                                                            <td data-title="Receiver Phone">
                                                                <?php
                                                if ($allrecord->payment_mode == "TRANSAFEROUT" || $allrecord->payment_mode == "CARDPAYMENT") {
                                                    echo isset($allrecord->accountId) ? $allrecord->accountId : 'N/A';
                                                } else {
                                                    echo 'N/A';
                                                }   
                                                                                                                                                                                                                                                                                                        ?>
                                                            </td>
                                            <?php elseif($allrecord->receiver_id == 1 && $allrecord->trans_for == 'Admin'): ?>
                                                            <?php
                                                if ($allrecord->trans_to != "") {
                                                    $admin = DB::table('admins')->where('id', $allrecord->trans_to)->first();
                                                } else {
                                                    $admin = DB::table('admins')->where('id', 1)->first();
                                                }
                                                                                                                                                                                                                                                                                                    ?>
                                                            <td data-title="Receiver Name">
                                                                <?php echo e(isset($admin->username) ? ucfirst($admin->username) : 'N/A'); ?>

                                                            </td>
                                                            <td data-title="Receiver Phone">
                                                                <?php echo e('N/A'); ?>

                                                            </td>
                                            <?php else: ?>
                                                <td data-title="Receiver Name">

                                                    <?php echo e(ucfirst($allrecord->Receiver->name)); ?>


                                                </td>
                                                <td data-title="Receiver Phone">

                                                    <?php echo e(ucfirst($allrecord->Receiver->phone)); ?>


                                                </td>
                                            <?php endif; ?>


                                            <?php        /* <td data-title="Transaction Type">
                                                                                                                                                                                        @if($allrecord->trans_type == 1)
                                                                                                                                                                                        {{'Credit'}}
                                                                                                                                                                                        @elseif($allrecord->trans_type == 2)
                                                                                                                                                                                        {{'Debit'}}
                                                                                                                                                                                        @elseif($allrecord->trans_type == 3)
                                                                                                                                                                                        {{'Topup'}}
                                                                                                                                                                                        @elseif($allrecord->trans_type == 4)
                                                                                                                                                                                        {{'Request'}}
                                                                                                                                                                                        @endif
                                                                                                                                                                                        </td> */ ?>
                                            <td data-title="Transaction For">
                                                <?php
                            if ($allrecord->payment_mode == 'Withdraw' && $allrecord->trans_for == 'Admin' && $allrecord->trans_type == 2) {
                                echo 'Admin Withdraw';
                            } elseif ($allrecord->payment_mode == 'Deposit' && $allrecord->trans_for == 'Admin' && $allrecord->trans_type == 1) {
                                echo 'Admin Deposit';
                            } elseif ($allrecord->payment_mode == 'Withdraw') {
                                echo 'Buy Balance';
                            } elseif ($allrecord->payment_mode == 'Agent Deposit') {
                                echo 'Sell Balance';
                            } elseif ($allrecord->payment_mode == 'Referral') {
                                echo 'Referral Bonus';
                            } elseif ($allrecord->payment_mode == 'airtelwallet') {
                                echo 'Airtel Money';
                            } elseif (isset($allrecord->receiver_mobile)) {
                                if ($allrecord->transactionType == 'SWAPTOGIMAC') {
                                    echo 'GIMAC Transfer';
                                } elseif ($allrecord->transactionType == 'SWAPTOONAFRIQ') {
                                    echo 'ONAFRIQ Transfer';
                                } elseif ($allrecord->transactionType == 'SWAPTOBDA') {
                                    echo 'BDA Transfer';
                                } else {

                                    echo 'wallet2wallet';
                                }
                            } else {
                                if ($allrecord->payment_mode == "TRANSAFEROUT" || $allrecord->payment_mode == "CARDPAYMENT") {
                                    echo $allrecord->description == "Denial - POS Purchase" ? "POS Denial" : $allrecord->description;
                                } else {
                                    echo $allrecord->payment_mode;
                                }
                            }
                                                                               ?>
                                                <!--<?php echo e($allrecord->payment_mode); ?>-->
                                            </td>
                                            <!-- <td data-title="Comapny Name"><?php echo e($allrecord->company_name?$allrecord->company_name:'N/A'); ?></td> -->
                                            <td data-title="Amount Paid">
                                                <!-- <?php echo e(CURR); ?><?php echo e(number_format($allrecord->amount , 0, '.', ',')); ?> -->
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

                                            <td data-title="Transaction Fee">
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
                            $val = isset($allrecord->runningBalance) ? $allrecord->runningBalance : $allrecord->afterBalance;
                            $val = (($val - floor($val)) > 0.5) ? ceil($val) : floor($val);

                            echo CURR . number_format($val, 0, '', ' ');
                                                                    ?>

                                            </td>
                                            <td data-title="Transaction ID">
                                                <?php
                            if ($allrecord->payment_mode == "CARDPAYMENT" || $allrecord->payment_mode == "TRANSAFEROUT") {
                                if ($allrecord->transactionId == 0) {
                                    echo $allrecord->id;
                                } else {
                                    echo $allrecord->transactionId;
                                }
                            } else {
                                echo $allrecord->refrence_id ?? "";
                            }
                                                                               ?>
                                            </td>
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
                                            <td data-title="Transaction / Request Date"><?php echo e($allrecord->created_at->format('M d, Y h:i:s A')); ?>

                                            </td>
                                            <td data-title="Transaction Process Date"><?php echo e($allrecord->updated_at->format('M d, Y h:i:s A')); ?>

                                            </td>
                                            <td data-title="Action">
                                                <a href="#info<?php echo $allrecord->id; ?>" title="View Transaction Details"
                                                    class="btn btn-primary btn-xs" rel='facebox'><i class="fa fa-eye"></i></a>
                                                <!--<a href="<?php echo e(URL::to( 'admin/transactions/delete/'.$allrecord->id)); ?>" title="Delete" class="btn btn-danger btn-xs action-list delete-list" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash-o"></i></a>-->
                                                <!-- <div class="btn-group">
                                                                                                        <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                                                                                            <i class="fa fa-list"></i>
                                                                                                            <span class="caret"></span>
                                                                                                        </button>
                                                                                                            <ul class="dropdown-menu pull-right">
                                                                                                            <li><a href="<?php echo e(URL::to( 'admin/transactions/delete/'.$allrecord->id)); ?>" title="Delete" class="" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash-o"></i>Delete</a></li>
                                                                                                            <li><a href="<?php echo e(URL::to( 'admin/transactions/delete/'.$allrecord->id)); ?>" title="Delete" class="" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash-o"></i>Delete</a></li>

                                                                                                        </ul> 
                                                                                                    </div>-->
                                            </td>
                                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
                <!--            <div class="search_frm">
                                                        <button type="button" name="chkRecordId" onclick="checkAll(true);"  class="btn btn-info">Select All</button>
                                                        <button type="button" name="chkRecordId" onclick="checkAll(false);" class="btn btn-info">Unselect All</button>
                                        <?php
        $accountStatus = array(
            'Delete' => "Delete",
        );
                                        ?>
                                                        <div class="list_sel"><?php echo e(Form::select('action', $accountStatus,null, ['class' => 'small form-control','placeholder' => 'Action for selected record', 'id' => 'action'])); ?></div>
                                                        <button type="submit" class="small btn btn-success btn-cons btn-info" onclick="return ajaxActionFunction();" id="submit_action">OK</button>
                                                    </div>    -->
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
                    <legend class="head_pop">#<?php echo $allrecord->id; ?></legend>
                    <div class="drt">
                        <div class="admin_pop"><span>Sender Name: </span> <label>
                                <?php if($allrecord->user_id == 1 && $allrecord->trans_for == 'Admin'): ?>
                                    <?php echo e(isset($admin->username) ? ucfirst($admin->username) : 'N/A'); ?>

                                <?php else: ?>
                                    <?php echo e(isset($allrecord->User->name) ? ucfirst($allrecord->User->name) : 'N/A'); ?>

                                <?php endif; ?>
                            </label>
                        </div>

                        <div class="admin_pop"><span>Sender Phone: </span> <label>
                                <?php if($allrecord->user_id == 1 && $allrecord->trans_for == 'Admin'): ?>
                                    N/A
                                <?php else: ?>
                                    <?php echo e(isset($allrecord->User->phone) ? ucfirst($allrecord->User->phone) : 'N/A'); ?>

                                <?php endif; ?>
                            </label>
                        </div>

                        <div class="admin_pop"><span>Receiver Name: </span> <label>
                                <?php if($allrecord->receiver_id == 1 && $allrecord->trans_for == 'Admin'): ?>
                                    <?php echo e(isset($admin->username) ? ucfirst($admin->username) : 'N/A'); ?>

                                <?php else: ?>
                                    <?php if(isset($allrecord->Receiver->name)): ?>
                                        <?php echo e(ucfirst($allrecord->Receiver->name)); ?>

                                    <?php elseif(isset($allrecord->User->name)): ?>
                                        <?php echo e(ucfirst($allrecord->User->name)); ?>

                                    <?php else: ?>
                                        <?php echo e('N/A'); ?>

                                    <?php endif; ?>
                                <?php endif; ?>
                            </label>
                        </div>
                        <div class="admin_pop"><span>Receiver Phone / Account ID: </span> <label>
                                <?php if($allrecord->receiver_id == 1 && $allrecord->trans_for == 'Admin'): ?>
                                    N/A
                                <?php else: ?>
                                    <?php if($allrecord->accountId): ?>
                                        <?php echo e($allrecord->accountId); ?>

                                    <?php elseif(isset($allrecord->Receiver->phone)): ?>
                                        <?php echo e(ucfirst($allrecord->Receiver->phone)); ?>

                                    <?php elseif(isset($allrecord->User->phone)): ?>
                                        <?php echo e(ucfirst($allrecord->User->phone)); ?>

                                    <?php else: ?>
                                        <?php echo e('N/A'); ?>

                                    <?php endif; ?>
                                <?php endif; ?>
                            </label>
                        </div>
                        <?php        /* <div class="admin_pop"><span>Transaction Type: </span>  <label>
                                                                            @if($allrecord->trans_type == 1)
                                                                            {{'Credit'}}
                                                                            @elseif($allrecord->trans_type == 2)
                                                                            {{'Debit'}}
                                                                            @elseif($allrecord->trans_type == 3)
                                                                            {{'Topup'}}
                                                                            @elseif($allrecord->trans_type == 4)
                                                                            {{'Request'}}
                                                                            @endif
                                                                            </label>
                                                                            </div> */ ?>
                        <div class="admin_pop"><span>Transaction For: </span> <label>
                                <?php
                if ($allrecord->payment_mode == 'Withdraw' && $allrecord->trans_for == 'Admin' && $allrecord->trans_type == 2) {
                    echo 'Admin Withdraw';
                } elseif ($allrecord->payment_mode == 'Deposit' && $allrecord->trans_for == 'Admin' && $allrecord->trans_type == 1) {
                    echo 'Admin Deposit';
                } elseif ($allrecord->payment_mode == 'Withdraw') {
                    echo 'Buy Balance';
                } elseif ($allrecord->payment_mode == 'Agent Deposit') {
                    echo 'Sell Balance';
                } elseif ($allrecord->payment_mode == 'Referral') {
                    echo 'Referral Bonus';
                } elseif (isset($allrecord->receiver_mobile)) {
                    if ($allrecord->transactionType == 'SWAPTOGIMAC') {
                        echo 'GIMAC Transfer';
                    } elseif ($allrecord->transactionType == 'SWAPTOONAFRIQ') {
                        echo 'ONAFRIQ Transfer';
                    } elseif ($allrecord->transactionType == 'SWAPTOBDA') {
                        echo 'BDA Transfer';
                    } else {

                        echo 'wallet2wallet';
                    }
                } else {
                    if ($allrecord->payment_mode == "TRANSAFEROUT") {
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
                    }
                }
                                                                                ?>
                            </label>
                        </div>
                        <div class="admin_pop"><span>Company Name: </span> <label>
                                <?php echo e($allrecord->company_name ? $allrecord->company_name : 'N/A'); ?>

                            </label>
                        </div>
                        <div class="admin_pop"><span>Real Value: </span> <label>
                                <?php echo e($allrecord->real_value ? $allrecord->currency . ' ' . $allrecord->real_value : 'N/A'); ?>

                            </label>
                        </div>


                        <div class="admin_pop"><span>Amount: </span> <label>
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
                            </label>
                        </div>

                        <div class="admin_pop"><span>Billing Discription: </span> <label>
                                <?php        $bllngDesc = str_replace("<br>", "##", $allrecord->billing_description);
                $descArr = explode("##", $bllngDesc);
                foreach ($descArr as $val) {
                    echo $val . '<br>';
                } ?>
                            </label>
                        </div>


                        <?php if($allrecord->payment_mode == 'Refund'): ?>

                                <?php
                            $transArr = explode('-', $allrecord->billing_description);
                            if (isset($transArr[1])) {
                                $addrecord1 = DB::table('transactions')
                                    ->where('id', $transArr[1])
                                    ->first();
                                                                                                                                    ?>
                                <div class="admin_pop"><span>Reference ID: </span> <label><?php echo e($addrecord1->refrence_id); ?></label></div>
                                <?php            }
                                                                                                                                ?>

                        <?php endif; ?>

                        <div class="admin_pop"><span>Transaction ID: </span> <label><?php echo e($allrecord->refrence_id); ?></label></div>
                        <div class="admin_pop"><span>Status: </span>
                            <label>
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
                            <div class="admin_pop"><span>Transaction Process Date: </span>
                                <label><?php echo e($allrecord->updated_at->format('M d, Y h:i:s A')); ?></label>
                            </div>
                        <?php endif; ?>

                </fieldset>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?><?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/transactions/transactionHistory.blade.php ENDPATH**/ ?>