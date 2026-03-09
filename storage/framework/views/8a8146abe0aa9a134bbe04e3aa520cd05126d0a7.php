<?php use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
?>
<?php echo e(HTML::script('public/assets/js/jquery.fancybox.js?v=2.1.5')); ?>

<?php echo e(HTML::style('public/assets/css/jquery.fancybox.css?v=2.1.5')); ?>

<script type="text/javascript">

    $(document).ready(function () {
        $('.fancybox').fancybox();
    });
</script>

<?php if(!empty($userInfo->selfie_image) || !empty($userInfo->identity_front_image)): ?>
<div class="panel-body marginzero">
    <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
    <?php echo e(Form::open(array('method' => 'post', 'id' => 'actionFrom'))); ?>

    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="topn_left">KYC Details List</div>
            <div class="topn_rightd ddpagingshorting" id="pagingLinks" align="right">

            </div>                
        </div>
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                    <th class="sorting_paging">Id Type</th>
                        <th class="sorting_paging">Selfile Image</th>
                        <th class="sorting_paging">Front Image</th>
                        <?php if(!empty($userInfo->identity_back_image)): ?>
                        <th class="sorting_paging">Back Image</th>
                        <?php endif; ?>
                        <?php global $documents;?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if(is_array($value) && array_key_exists('id', $value)): ?>
                        <?php if($value['id'] == $userInfo->national_identity_type): ?>
                        <td data-title="Identity Type"><?php echo e($value['name']); ?></td>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php if($userInfo->national_identity_type ==""): ?>
                        <td data-title="Identity Type"></td>
                        <?php endif; ?>

                        <td data-title="Picture Selfie Image">
                            <?php if($userInfo->selfie_image != ''): ?>

                            <a href="<?php echo e($userInfo->selfie_image); ?>" title="View KYC Document"
                                data-fancybox-group="gallery1" class="fancybox" target="_blank">
                                <?php echo e(HTML::image($userInfo->selfie_image, SITE_TITLE,['style'=>'max-width:50px;
                                max-height:50px;'])); ?>

                            </a>

                            <?php else: ?>
                            No Image
                            <?php endif; ?>

                        </td>
                        <td data-title="Picture Front Image">
                            <?php if($userInfo->identity_front_image != ''): ?>
                            <a href="<?php echo e($userInfo->identity_front_image); ?>" title="View KYC Document"
                                data-fancybox-group="gallery1" class="fancybox" target="_blank">
                                <?php echo e(HTML::image($userInfo->identity_front_image, SITE_TITLE,['style' => 'max-width:50px;
                                max-height:50px;'])); ?>

                            </a>
                            <?php else: ?>
                            No Image
                            <?php endif; ?>

                        </td>
                        <td data-title="Picture Selfie Image">
                            <?php if($userInfo->identity_back_image != ''): ?>
                            <a href="<?php echo e($userInfo->identity_back_image); ?>" title="View KYC Document"
                                data-fancybox-group="gallery1" class="fancybox" target="_blank">
                                <?php echo e(HTML::image($userInfo->identity_back_image, SITE_TITLE,['style' => 'max-width:50px;
                                max-height:50px;'])); ?>

                            </a>
                            <?php endif; ?>

                        </td>


                        <!-- <td data-title="Status">
                            <?php if($userInfo->is_kyc_done == 1): ?>
                            Approved
                            <?php elseif($userInfo->is_kyc_done == 2): ?>
                            Declined
                            <?php else: ?>
                            Pending
                            <?php endif; ?>
                        </td>
                        <td data-title="Date"><?php echo e($userInfo->created_at->format('M d, Y h:i A')); ?></td> -->

                    </tr>
                </tbody>
            </table>
            <div class="search_frm"> 
                <!-- <?php if($userInfo->is_kyc_done == 4){
                    $userInfo->is_kyc_done = 0;
                }?>
                <?php if($userInfo->is_kyc_done == 0): ?>
                <a href="<?php echo e(URL::to( 'admin/agents/approvekyc/'.$userInfo->slug)); ?>" title="Approve KYC" class="btn btn-info">Approve KYC</a>
                <a href="<?php echo e(URL::to( 'admin/agents/declinekyc/'.$userInfo->slug)); ?>" title="Decline KYC" class="btn btn-info">Decline KYC</a>
                <?php elseif($userInfo->is_kyc_done == 2): ?>
                <a href="javascript:void();" title="Declined KYC" class="btn btn-info">Declined</a>
                <?php else: ?>
                <a href="javascript:void();" title="Approved KYC" class="btn btn-info">Approved</a>
                <?php endif; ?> -->
                <?php if($userInfo->kyc_status=="pending") {  ?>
                <a href="<?php echo e(URL::to('admin/agents/approvekyc/' . $userInfo->slug)); ?>" title="Approve" class="btn btn-info">Approve Kyc</a>
                <?php } ?>
                <a href="<?php echo e(URL::to( 'admin/agents')); ?>" title="Cancel" class="btn btn-default canlcel_le">Cancel</a>
            </div> 
        </div>
    </section>
    <?php echo e(Form::close()); ?>

</div>         
</div> 
<?php else: ?> 
<div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
<div class="admin_no_record">No record found.</div>
<?php endif; ?>
<?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/agents/kycdetail.blade.php ENDPATH**/ ?>