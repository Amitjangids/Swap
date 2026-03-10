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
<!-- <style>
    .text-right-nav {
    text-align: right;
}
</style> -->
<div class="admin_loader" id="loaderID"><?php echo e(HTML::image("public/img/website_load.svg", '')); ?></div>
<?php if(!$allrecords->isEmpty()): ?>
<div class="panel-body marginzero">
    <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
    <?php echo e(Form::open(array('method' => 'post', 'id' => 'actionFrom'))); ?>

    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="manage_sec">
                <div class="topn_left"> Gimac Transactions List</div>

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

                                <?php echo e(number_format((($totalAmount['total_amount'] - floor($totalAmount['total_amount'])) > 0.5 ? ceil($totalAmount['total_amount']) : floor($totalAmount['total_amount'])), 0, '', ' ') ?? 0); ?>

                            </span>
                        </div>
                        <div class="payment_info">
                        <span class="pay_head">Total Earning</span>
                            <span class="pay_body"><?php echo e(CURR); ?> 
                                <?php echo e(number_format((($totalAmount['total_fee'] - floor($totalAmount['total_fee'])) > 0.5 ? ceil($totalAmount['total_fee']) : floor($totalAmount['total_fee'])), 0, '', ' ') ?? 0); ?>

                            </span>
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
                $('.export_excel').on('click', function () {
                    
                    var sender = $('input[name=sender]').val(); 
                    var receiver = $('input[name=receiver]').val();
                    var receiver_phone = $('input[name=receiver_phone]').val();
                    var type = $('select[name=type]').val(); 
                    var from = $('input[name=to]').val(); 
                    var to = $('input[name=to1]').val(); 
                    // Redirect to the export URL with query parameters
                    var url = '<?php echo e(route("exportexcelgimac")); ?>?sender=' + sender + '&receiver=' + receiver + '&receiver_phone=' + receiver_phone +'&type=' + type+'&to=' + from+'&to1=' + to;
                    window.location.href = url;
                });

            </script>


        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('First Name', 'First Name'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Name', 'Name'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Country', 'Country'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Wallet Manager', 'Wallet Manager '));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Tel. No', 'Tel. No'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Amount ', 'Amount '));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Fee', 'Fee'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Issuertrxref', 'Issuertrxref No'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Status', 'Status'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Created', 'Created'));?></th>
                        
                    </tr>
                </thead>
                <tbody>
                <?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allrecord): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> 
    <tr>
        
        <td data-title="First Name"><?php echo e(isset($allrecord->ExcelTransaction->first_name) ? ucfirst($allrecord->ExcelTransaction->first_name) : 'N/A'); ?></td>
        <td data-title="Name"><?php echo e(isset($allrecord->ExcelTransaction->name) ? ucfirst($allrecord->ExcelTransaction->name) : 'N/A'); ?></td>
        <td data-title="Country Name"><?php echo e(isset($allrecord->ExcelTransaction->country->name) ? ucfirst($allrecord->ExcelTransaction->country->name) : 'N/A'); ?></td>
        <td data-title="Wallet Manager"><?php echo e(isset($allrecord->ExcelTransaction->walletManager->name) ? ucfirst($allrecord->ExcelTransaction->walletManager->name) : 'N/A'); ?></td>
        <td data-title="Tel No "><?php echo e(isset($allrecord->ExcelTransaction->tel_number) ? ucfirst($allrecord->ExcelTransaction->tel_number) : 'N/A'); ?></td>
        <td data-title="Amount ">₣ 
            <?php echo e(number_format((($allrecord->amount_value - floor($allrecord->amount_value)) > 0.5 ? ceil($allrecord->amount_value) : floor($allrecord->amount_value)), 0, '', ' ') ?? 0); ?>

        </td>
        <td data-title="Fee">₣ 
            <?php echo e(number_format((($allrecord->transaction_amount - floor($allrecord->transaction_amount)) > 0.5 ? ceil($allrecord->transaction_amount) : floor($allrecord->transaction_amount)), 0, '', ' ') ?? 0); ?>

        </td>
        <td data-title="Issuertrxref No"><?php echo e($allrecord->issuertrxref); ?></td>
        <td data-title="Status">
                <?php if($allrecord->is_verified_by_gimac == '0'): ?>
                    Not verified
                <?php elseif($allrecord->is_verified_by_gimac == '1'): ?>
                    Verified
                <?php endif; ?>
            </td>
        <td data-title="Transaction / Request Date"><?php echo e($allrecord->created_at->format('M d, Y ')); ?></td>
        
    </tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                </tbody>
            </table>
          
        </div>
    </section>
    <?php echo e(Form::close()); ?>

    <!-- <div class="text-right-nav">
    <?php echo e($allrecords->links()); ?>

    </div> -->
</div>         
</div> 
<?php else: ?> 
<div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
<div class="admin_no_record">No record found.</div>
<?php endif; ?>



<?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/transactions/gimac.blade.php ENDPATH**/ ?>