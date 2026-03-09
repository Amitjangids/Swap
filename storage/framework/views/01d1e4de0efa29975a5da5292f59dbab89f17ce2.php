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
                    <div class="topn_left">Referral Earning List</div>

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
                                    $total->totalAmount
                                )), 0, '.', ' ')); ?></span>
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
                    var requestD = $('input[name=to]').val();

                    // Redirect to the export URL with query parameters
                    var url = '<?php echo e(route("exportexcelreferral")); ?>?sender=' + sender + '&sender_phone=' + sender_phone + '&receiver=' + receiver + '&receiver_phone=' + receiver_phone + '&to=' + requestD;
                    window.location.href = url;
                });

            </script>

            <div class="tbl-resp-listing">
                <table class="table table-bordered table-striped table-condensed cf">
                    <thead class="cf ddpagingshorting">
                        <tr>
                            <!--<th style="width:5%">#</th>-->
                            <!--<th style="width:5%">Trans Id</th>-->
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('User.name', 'Referrer Name'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('User.phone', 'Referrer Phone Number'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Receiver.name', 'Referred Name'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Receiver.phone', 'Referred Phone Number'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('payment_mode', 'Transaction For'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('amount', 'Amount'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('status', 'Status'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('created_at', 'Transaction Date'));?></th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allrecord): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td>
                                                <?php
                            echo $allrecord->sender_name ?? 'N/A';
                                            ?>
                                            </td>
                                            <td data-title="Sender Phone">
                                                <?php 
                                                    echo $allrecord->sender_phone ?? 'N/A'; ?>
                                            </td>
                                            <td data-title="Receiver Name">
                                                <?php
                            echo $allrecord->receiver_name;

                                                ?>
                                            </td>
                                            <td data-title="Receiver Phone">
                                                <?php
                            echo $allrecord->receiver_phone;
                                                ?>
                                            </td>

                                            <td data-title="Transaction For">
                                                <?php
                            echo $allrecord->payment_mode;
                                                ?>
                                            </td>
                                            <td data-title="Amount Paid"><?php echo e(CURR); ?><?php echo e($allrecord->amount); ?>

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
                                            <?php        //print_r($allrecord);  die;?>
                                            <td data-title="Transaction / Request Date"><?php echo e($allrecord->created_at); ?>

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
<?php endif; ?><?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/transactions/referral-listing.blade.php ENDPATH**/ ?>