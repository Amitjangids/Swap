<?php echo e(HTML::script('public/assets/js/facebox.js')); ?>

<?php echo e(HTML::style('public/assets/css/facebox.css')); ?>

<?php
    use App\Http\Controllers\Admin\TransactionsController;
?>

<style>
    div#facebox .popup .alert-body form input[type="text"] {
        width: 100%;
        max-width: 150px;
    }

    .refund-value-box {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .refund-value-box span {
        margin-right: 20px;
        font-size: 16px;
    }

    div#facebox .popup .alert-body form .alert-btn button.btn.btn-alert {
        background: #4b2e74;
        padding: 7px 10px;
        min-width: 100px;
        color: #fff;
        font-size: 18px;
        border: 1px solid transparent;
        border-radius: 10px;
        text-align: center;
        transition: 0.4s;
        -webkit-transition: 0.4s;
    }

    div#facebox .popup .alert-body form .alert-btn button.btn.btn-alert:hover {
        background-color: transparent;
        border-color: #4b2e74;
        color: #4b2e74;
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
                                <span class="pay_body"><?php echo e(CURR); ?> 
                                <?php echo e(number_format((($total['total_amount_a'] - floor($total['total_amount_a'])) > 0.5 ? ceil($total['total_amount_a']) : floor($total['total_amount_a'])), 0, '', ' ') ?? 0); ?>

                                </span>
                            </div>
                            <div class="payment_info">
                                <span class="pay_head">Total Earning</span>
                                <span class="pay_body"><?php echo e(CURR); ?><?php echo e(number_format((($total['total_fee_a'] - floor($total['total_fee_a'])) > 0.5 ? ceil($total['total_fee_a']) : floor($total['total_fee_a'])), 0, '', ' ') ?? 0); ?></span>
                            </div>
                        </div>
                        <div class="download_excel">
                            <a href="javascript:void(0)" class="btn btn-success export_excel">Download Excel <i
                                    class="fa fa-file-excel-o" aria-hidden="true"></i></a>
                        </div>
                    </div>
                </div>

            </div>

            <script>
                        /*  $('.export_excel').on('click', function () {
                            // alert('ok');
                            window.location.href = '<?php echo e(HTTP_PATH); ?>/admin/transactions / export_excel';
                        }); */


                $('.export_excel').on('click', function () {
                    var sender = $('input[name=sender]').val();
                    var sender_phone = $('input[name=sender_phone]').val();
                    var receiver = $('input[name=receiver]').val();
                    var receiver_phone = $('input[name=receiver_phone]').val();
                    var paymentMode = $('select[name=for]').val();
                    var refrence = $('input[name=refrence]').val();
                    var requestD = $('input[name=to]').val();
                    var processD = $('input[name=to1]').val();

                    // Redirect to the export URL with query parameters
                    var url = '<?php echo e(route("report")); ?>?sender=' + sender + '&sender_phone=' + sender_phone + '&receiver=' + receiver + '&receiver_phone=' + receiver_phone + '&for=' + paymentMode + '&refrence=' + refrence + '&to=' + requestD + '&to1=' + processD;
                    window.location.href = url;
                });

            </script>

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
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('refrence_id', 'Transaction ID'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('status', 'Status'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('created_at', 'Transaction Request Date'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('updated_at', 'Transaction Process Date'));?></th>
                            <th class="action_dvv"> Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allrecord): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php //dd($allrecord); ?>
                                        <tr>
                                            <?php if($allrecord->user_id == 1 && $allrecord->trans_for == 'Admin'): ?>
                                                            <?php
                                                if ($allrecord->trans_to != "") {
                                                    $admin = DB::table('admins')->where('id', $allrecord->trans_to)->first();
                                                } else {
                                                    $admin = DB::table('admins')->where('id', 1)->first();
                                                }
                                                                                                                                    ?>

                                                            <td data-title="Sender Name">
                                                                <?php echo e(isset($admin->username) ? ucfirst($admin->username) : 'N/A'); ?>

                                                            </td>
                                                            <td data-title="Sender Phone">
                                                                <?php echo e('N/A'); ?>

                                                            </td>

                                            <?php else: ?>
                                                            <td data-title="Sender Name 1">
                                                                <?php
                                                if (isset($allrecord->senderName) && !empty($allrecord->senderName)) {
                                                    echo ucfirst($allrecord->senderName . ' ' . $allrecord->senderSurname);
                                                } elseif ($allrecord->payment_mode == "airtelwallet") {
                                                    echo "Airtel Money";
                                                } elseif ($allrecord->payment_mode == "External") {
                                                    echo $allrecord->transaction_sender_name.' '.$allrecord->transaction_sender_lastname ?? 'N/A';
                                                } else {
                                                    echo $allrecord->User->name ?? 'N/A';
                                                } ?>
                                                            </td>
                                                            <td data-title="Sender Phone 1">
                                                                <?php
                                                if (isset($allrecord->senderMsisdn) && !empty($allrecord->senderMsisdn)) {
                                                    echo $allrecord->senderMsisdn;
                                                } elseif ($allrecord->payment_mode == "airtelwallet") {
                                                    echo $allrecord->receiver_mobile ?? 'N/A';
                                                }elseif ($allrecord->payment_mode == "External") {
                                                        echo $allrecord->externalPhone ;
                                                } else {
                                                    echo $allrecord->User->phone ?? 'N/A';
                                                } ?>
                                                            </td>
                                            <?php endif; ?>


                                            <?php if($allrecord->receiver_id == 0 && $allrecord->receiver_mobile != ''): ?>
                                                            <td data-title="Receiver Name">
                                                                <?php
                                                if (isset($allrecord->recipientName) && !empty($allrecord->recipientName)) {
                                                    echo ucfirst($allrecord->recipientName . ' ' . $allrecord->recipientSurname);
                                                } elseif ($allrecord->payment_mode == "External") {
                                                    echo $allrecord->externalFirstName .' '. $allrecord->externalName  ?? 'N/A';
                                                } else {
                                                    if (isset($allrecord->ExcelTransaction->first_name)) {
                                                        echo ucfirst($allrecord->ExcelTransaction->first_name) ?? 'N/A';

                                                    }
                                                }
                                                                                                                                        ?>
                                                            </td>
                                                            <td data-title="Receiver Phone">
                                                                <?php
                                                if (isset($allrecord->recipientMsisdn) && !empty($allrecord->recipientMsisdn)) {
                                                    echo $allrecord->recipientMsisdn;
                                                } else {
                                                    echo $allrecord->receiver_mobile;
                                                }
                                                                                                                                        ?>
                                                            </td>
                                            <?php elseif($allrecord->receiver_id == 0 && $allrecord->receiver_mobile == ''): ?>
                                                            <td data-title="Receiver Name">
                                                                <?php            $admin = DB::table('admins')->where('id', 1)->first(); ?>
                                                                <?php
                                                if (isset($allrecord->recipientName) && !empty($allrecord->recipientName)) {
                                                    echo ucfirst($allrecord->recipientName . ' ' . $allrecord->recipientSurname);
                                                } else {
                                                    if ($allrecord->payment_mode == "TRANSAFEROUT" || $allrecord->payment_mode == "CARDPAYMENT") {
                                                        echo isset($allrecord->User->name) ? ucfirst($allrecord->User->name) : 'N/A';
                                                    } else {
                                                        echo isset($admin->username) ? ucfirst($admin->username) : 'N/A';
                                                    }
                                                }
                                                                                                                                        ?>
                                                            </td>
                                                            <td data-title="Receiver Phone">
                                                                <?php
                                                if (isset($allrecord->recipientMsisdn) && !empty($allrecord->recipientMsisdn)) {
                                                    echo $allrecord->recipientMsisdn;
                                                } else {
                                                    if ($allrecord->payment_mode == "TRANSAFEROUT" || $allrecord->payment_mode == "CARDPAYMENT") {
                                                        echo isset($allrecord->accountId) ? $allrecord->accountId : 'N/A';
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                }
                                                                                                                                        ?>
                                                            </td>
                                            <?php elseif($allrecord->receiver_id == 1 && $allrecord->tomember == 'Admin'): ?>
                                                            <?php
                                                if ($allrecord->trans_to != "") {
                                                    $admin = DB::table('admins')->where('id', $allrecord->trans_to)->first();
                                                } else {
                                                    $admin = DB::table('admins')->where('id', 1)->first();
                                                }
                                                                                                                                    ?>
                                                            <td data-title="Receiver Name">
                                                                <?php
                                                if (isset($allrecord->recipientName) && !empty($allrecord->recipientName)) {
                                                    echo ucfirst($allrecord->recipientName . ' ' . $allrecord->recipientSurname);
                                                } else {
                                                    echo ucfirst($admin->username) ?? 'N/A';
                                                }
                                                                                                                                        ?>
                                                            </td>
                                                            <td data-title="Receiver Phone">
                                                                <?php echo e('N/A'); ?>

                                                            </td>
                                            <?php else: ?>
                                                            <td data-title="Receiver Name">
                                                                <?php
                                                if (isset($allrecord->recipientName) && !empty($allrecord->recipientName)) {
                                                    echo ucfirst($allrecord->recipientName . ' ' . $allrecord->recipientSurname);
                                                } elseif ($allrecord->payment_mode == "External") {
                                                    echo $allrecord->externalFirstName .' '. $allrecord->externalName  ?? 'N/A';
                                                } else {
                                                    if (isset($allrecord->Receiver->name))
                                                        echo ucfirst($allrecord->Receiver->name);
                                                    elseif (isset($allrecord->User->name))
                                                        echo ucfirst($allrecord->User->name);
                                                    else
                                                        echo 'N/A';
                                                }
                                                                                                                                        ?>
                                                            </td>
                                                            <td data-title="Receiver Phone">
                                                                <?php
                                                if (isset($allrecord->recipientMsisdn) && !empty($allrecord->recipientMsisdn)) {
                                                    echo $allrecord->recipientMsisdn;
                                                }elseif ($allrecord->payment_mode == "External") {
                                                    echo $allrecord->receiver_mobile  ?? 'N/A';
                                                } else {
                                                    if (isset($allrecord->Receiver->phone))
                                                        echo ucfirst($allrecord->Receiver->phone);
                                                    elseif (isset($allrecord->User->phone))
                                                        echo ucfirst($allrecord->User->phone);
                                                    else
                                                        echo 'N/A';

                                                }
                                                                                                                                        ?>
                                                            </td>
                                            <?php endif; ?>
                                            <?php
                            /* 
                                <td data-title="Transaction Type">
                                @if($allrecord->trans_type == 1)
                                {{'Credit'}}
                                @elseif($allrecord->trans_type == 2)
                                {{'Debit'}}
                                @elseif($allrecord->trans_type == 3)
                                {{'Topup'}}
                                @elseif($allrecord->trans_type == 4)
                                {{'Request'}}
                                @endif
                                </td> 
                            */
                                                                                    ?>
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
                            } elseif ($allrecord->payment_mode == "External") {
                                echo $allrecord->payment_mode ?? 'N/A';
                            } elseif ($allrecord->transactionType == "AIRTELMONEY") {
                                echo "Airtel Money";
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
                            } elseif ($allrecord->payment_mode == "Referral") {
                                echo 'Referral';
                            } else {
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
                                } */

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
                                            <td data-title="Transaction ID">
                                                <?php
                            if (isset($allrecord->transactionId) && !empty($allrecord->transactionId)) {
                                echo $allrecord->transactionId ?? "";
                            } else if (isset($allrecord->remitanceTransactionId) && !empty($allrecord->remitanceTransactionId) && $allrecord->transactionType == 'SWAPTOBDA') {
                                echo $allrecord->remitanceTransactionId ? $allrecord->remitanceTransactionId : "N/A";
                            } else {
                                if ($allrecord->payment_mode == "CARDPAYMENT" || $allrecord->payment_mode == "TRANSAFEROUT") {
                                    if ($allrecord->cardTransId == 0) {
                                        echo $allrecord->id;
                                    } else {
                                        echo $allrecord->cardTransId;
                                    }
                                } else {
                                    echo $allrecord->refrence_id ?? "";
                                }
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
                                            <?php        //print_r($allrecord);  die; ?>
                                            <td data-title="Transaction / Request Date"><?php echo e($allrecord->created_at->format('M d, Y h:i:s A')); ?>

                                            </td>
                                            <td data-title="Transaction Process Date"><?php echo e($allrecord->updated_at->format('M d, Y h:i:s A')); ?>

                                            </td>
                                            <td data-title="Action">
                                                <div id="loderstatus<?php echo e($allrecord->id); ?>" class="right_action_lo">
                                                    <?php echo e(HTML::image("public/img/loading.gif", '')); ?>

                                                </div>
                                                <div class="btn-group">
                                                    <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                                        <i class="fa fa-list"></i>
                                                        <span class="caret"></span>
                                                    </button>
                                                    <ul class="dropdown-menu pull-right">
                                                        <li><a href="#info<?php echo $allrecord->id; ?>" title="View Transaction Details" class=""
                                                                rel='facebox'><i class="fa fa-eye"></i>Transaction History</a></li>
                                                        <?php        if ($allrecord->payment_mode != "Refund" && $allrecord->status == 1 && $allrecord->receiver_id != 0) { ?>
                                                        <?php
                                                            $checkRefund = TransactionsController::checkRefund($allrecord->id);
                                                        ?>
                                                        <?php            if ($checkRefund == 0) { ?>
                                                        <li><a href="#alertbox<?php echo $allrecord->id; ?>" title="Refund" class=""
                                                                rel='facebox'><i class="fa fa-money"></i>Refund</a></li>
                                                        <?php            }
                            } ?>
                                                    </ul>
                                                </div>
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
                    <legend class="head_pop">Transaction History Details</legend>
                    <div class="drt">


                        <?php if($allrecord->user_id == 1 && $allrecord->trans_for == 'Admin'): ?>


                                <?php
                            if ($allrecord->trans_to != "") {
                                $admin = DB::table('admins')->where('id', $allrecord->trans_to)->first();
                            } else {
                                $admin = DB::table('admins')->where('id', 1)->first();
                            }
                                                                ?>


                                <div class="admin_pop"><span>Sender Name: </span>
                                    <label>
                                        <?php echo e(isset($admin->username) ? ucfirst($admin->username) : 'N/A'); ?>

                                    </label>
                                </div>

                                <div class="admin_pop"><span>Sender Phone: </span>
                                    <label>
                                        <?php echo e('N/A'); ?>

                                    </label>
                                </div>

                        <?php else: ?>

                                <div class="admin_pop"><span>Sender Name: </span>
                                    <label>
                                        <?php
                            if (isset($allrecord->senderName) && !empty($allrecord->senderName)) {
                                echo ucfirst($allrecord->senderName . ' ' . $allrecord->senderSurname);
                            } elseif ($allrecord->payment_mode == "airtelwallet") {
                                echo "Airtel Money";
                            } elseif ($allrecord->payment_mode == "External") {
                                echo $allrecord->transaction_sender_name.' '.$allrecord->transaction_sender_lastname ?? 'N/A';
                            } else {
                                echo $allrecord->User->name ?? 'N/A';
                            } ?>
                                    </label>
                                </div>

                                <div class="admin_pop"><span>Sender Phone: </span>
                                    <label>
                                        <?php
                            if (isset($allrecord->senderMsisdn) && !empty($allrecord->senderMsisdn)) {
                                echo $allrecord->senderMsisdn;
                            } elseif ($allrecord->payment_mode == "External") {
                                echo $allrecord->externalPhone ?? 'N/Aaa';
                            }elseif ($allrecord->payment_mode == "airtelwallet") {
                                echo $allrecord->receiver_mobile ?? 'N/A';
                            } else {
                                echo $allrecord->User->phone ?? 'N/A';
                            } ?>
                                    </label>
                                </div>
                        <?php endif; ?>


                        <?php if($allrecord->receiver_id == 0 && $allrecord->receiver_mobile != ''): ?>
                                <!-- <div class="admin_pop"><span>Sender Name3: </span><label>
                                                                            <?php
                            if (isset($allrecord->recipientName) && !empty($allrecord->recipientName)) {
                                echo ucfirst($allrecord->recipientName . ' ' . $allrecord->recipientSurname);
                            } else {
                                if (isset($allrecord->ExcelTransaction->first_name)) {
                                    echo ucfirst($allrecord->ExcelTransaction->first_name) ?? 'N/A';

                                }
                            }
                                                                            ?>
                                                                                </label>
                                                                            </div> -->
                                <div class="admin_pop"><span>Receiver Phone: </span>
                                    <label>
                                        <?php
                            if (isset($allrecord->recipientMsisdn) && !empty($allrecord->recipientMsisdn)) {
                                echo $allrecord->recipientMsisdn;
                            } else {
                                echo $allrecord->receiver_mobile;
                            }
                                                                        ?>
                                    </label>
                                </div>
                        <?php elseif($allrecord->receiver_id == 0 && $allrecord->receiver_mobile == ''): ?>
                                <div class="admin_pop"><span>Receiver Name: </span>
                                    <label>
                                        <?php            $admin = DB::table('admins')->where('id', 1)->first(); ?>
                                        <?php
                            if (isset($allrecord->recipientName) && !empty($allrecord->recipientName)) {
                                echo ucfirst($allrecord->recipientName . ' ' . $allrecord->recipientSurname);
                            }elseif ($allrecord->payment_mode == "External") {
                                echo $allrecord->externalFirstName .' '. $allrecord->externalName  ?? 'N/A';
                            } else {
                                if ($allrecord->payment_mode == "TRANSAFEROUT" || $allrecord->payment_mode == "CARDPAYMENT") {
                                    echo isset($allrecord->User->name) ? ucfirst($allrecord->User->name) : 'N/A';
                                } else {
                                    echo isset($admin->username) ? ucfirst($admin->username) : 'N/A';
                                }
                            }
                                                                        ?>
                                    </label>
                                </div>
                                <div class="admin_pop"><span>Receiver Phone / Account ID: </span>
                                    <label>
                                        <?php
                            if (isset($allrecord->recipientMsisdn) && !empty($allrecord->recipientMsisdn)) {
                                echo $allrecord->recipientMsisdn;
                            }elseif ($allrecord->payment_mode == "External") {
                                echo $allrecord->receiver_mobile ?? 'N/A';
                            } else {
                                if ($allrecord->payment_mode == "TRANSAFEROUT" || $allrecord->payment_mode == "CARDPAYMENT") {
                                    echo isset($allrecord->accountId) ? $allrecord->accountId : 'N/A';
                                } else {
                                    echo 'N/A';
                                }
                            }
                                                                        ?>
                                    </label>
                                </div>
                        <?php elseif($allrecord->receiver_id == 1 && $allrecord->tomember == 'Admin'): ?>
                                <?php
                            if ($allrecord->trans_to != "") {
                                $admin = DB::table('admins')->where('id', $allrecord->trans_to)->first();
                            } else {
                                $admin = DB::table('admins')->where('id', 1)->first();
                            }
                                                                ?>
                                <div class="admin_pop"><span>Receiver Name: </span>
                                    <label>
                                        <?php
                            if (isset($allrecord->recipientName) && !empty($allrecord->recipientName)) {
                                echo ucfirst($allrecord->recipientName . ' ' . $allrecord->recipientSurname);
                            } else {
                                echo ucfirst($admin->username) ?? 'N/A';
                            }
                                                                        ?>
                                    </label>
                                </div>
                                <div class="admin_pop"><span>Receiver Phone: </span>
                                    <label>
                                        <?php echo e('N/A'); ?>

                                    </label>
                                </div>
                        <?php else: ?>
                                <div class="admin_pop"><span>Receiver Name: </span>
                                    <label>
                                        <?php
                            if (isset($allrecord->recipientName) && !empty($allrecord->recipientName)) {
                                echo ucfirst($allrecord->recipientName . ' ' . $allrecord->recipientSurname);
                            } elseif ($allrecord->payment_mode == "External") {
                                echo $allrecord->externalFirstName .' '. $allrecord->externalName  ?? 'N/A';
                            } else {
                                if (isset($allrecord->Receiver->name))
                                    echo ucfirst($allrecord->Receiver->name);
                                elseif (isset($allrecord->User->name))
                                    echo ucfirst($allrecord->User->name);
                                else
                                    echo 'N/A';
                            }
                                                                        ?>
                                    </label>
                                </div>
                                <div class="admin_pop"><span>Receiver Phone / Account ID: </span>
                                    <label>
                                        <?php
                            if (isset($allrecord->recipientMsisdn) && !empty($allrecord->recipientMsisdn)) {
                                echo $allrecord->recipientMsisdn;
                            }elseif ($allrecord->payment_mode == "External") {
                                echo $allrecord->receiver_mobile ?? 'N/A';
                            } else {
                                if ($allrecord->accountId)
                                    echo $allrecord->accountId;
                                elseif (isset($allrecord->Receiver->phone))
                                    echo ucfirst($allrecord->Receiver->phone);
                                elseif (isset($allrecord->User->phone))
                                    echo ucfirst($allrecord->User->phone);
                                else
                                    echo 'N/A';
                            }
                                                                        ?>
                                    </label>
                                </div>
                        <?php endif; ?>
                        <?php
                /* <div class="admin_pop"><span>Transaction Type: </span>  
                    <label>
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
                    </div> */
                                        ?>
                        <?php
                if (isset($allrecord->transactionType) && $allrecord->transactionType == 'SWAPTOBDA') { ?>
                        <div class="admin_pop"><span>Beneficiary: </span> <label>
                                <?php
                    if (isset($allrecord->titleAccount) && !empty($allrecord->titleAccount)) {
                        echo $allrecord->titleAccount;
                    } else {
                        echo "-";
                    }
                                                    ?>
                            </label>
                        </div>

                        <div class="admin_pop"><span>IBAN: </span> <label>
                                <?php
                    if (isset($allrecord->iban) && !empty($allrecord->iban)) {
                        echo $allrecord->iban;
                    } else {
                        echo "-";
                    }
                                                    ?>
                            </label>
                        </div>

                        <div class="admin_pop"><span>Comment (Reason): </span> <label>
                                <?php
                    if (isset($allrecord->reason) && !empty($allrecord->reason)) {
                        echo $allrecord->reason;
                    } else {
                        echo "-";
                    }
                                                    ?>
                            </label>
                        </div>
                        <?php        } ?>

                        <div class="admin_pop"><span>Transaction For: </span> <label>
                                <?php
                if ($allrecord->payment_mode == 'Withdraw' && $allrecord->trans_for == 'Admin' && $allrecord->trans_type == 2) {
                    echo 'Admin Withdraw';
                } elseif ($allrecord->payment_mode == 'Deposit' && $allrecord->trans_for == 'Admin' && $allrecord->trans_type == 1) {
                    echo 'Admin Deposit';
                } elseif ($allrecord->payment_mode == 'Withdraw') {
                    echo 'Buy Balance';
                } elseif ($allrecord->payment_mode == "External") {
                    echo $allrecord->payment_mode ?? 'N/A';
                } elseif ($allrecord->payment_mode == 'Agent Deposit') {
                    echo 'Sell Balance';
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

                        <div class="admin_pop"><span>Billing Discription: </span> <label>
                                <?php        $bllngDesc = str_replace("<br>", "##", $allrecord->billing_description);
                $descArr = explode("##", $bllngDesc);
                foreach ($descArr as $val) {
                    echo $val . '<br>';
                } ?>
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
                        <div class="admin_pop"><span>Transaction ID: </span> <label>
                                <!-- <?php echo e($allrecords->transactionId ?? $allrecords->transactionId ?? 'N/A'); ?> -->
                                <?php
                if (isset($allrecord->transactionId) && !empty($allrecord->transactionId)) {
                    echo $allrecord->transactionId ?? "";
                } else if (isset($allrecord->remitanceTransactionId) && !empty($allrecord->remitanceTransactionId) && $allrecord->transactionType == 'SWAPTOBDA') {
                    echo $allrecord->remitanceTransactionId ? $allrecord->remitanceTransactionId : "N/A";
                } else {
                    echo $allrecord->refrence_id;
                }
                                                ?>
                            </label></div>
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


        <div id="alertbox<?php echo $allrecord->id; ?>" class="alert-box" style="display: none;">
            <div class="popup">
                <div class="content">
                    <div class="nzwh-wrapper">
                        <div class="alert-body">
                            <form method="post" action="<?php echo e(HTTP_PATH); ?>/admin/transaction/refund/<?php echo e($allrecord->id); ?>">
                                <?php echo csrf_field(); ?>
                                <figure>
                                    <img src="https://nimbleappgenie.live/swap-local-v2/public/img/alert-img.png" class="alert">
                                </figure>
                                <div class="refund-value-box">
                                    <span>Refund Amount:</span>
                                    <input type="text" name="refund" placeholder="" class="form-control"
                                        value="<?php echo e($allrecord->total_amount - ($allrecord->transaction_amount ?? 0)); ?>">
                                </div>

                                <h4>Are you sure you want to refund?</h4>
                                <div class="alert-btn">
                                    <button type="submit" class="btn btn-alert">Process</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?><?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/transactions/index.blade.php ENDPATH**/ ?>