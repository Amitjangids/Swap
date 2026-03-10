<?php use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
?>

<?php echo e(HTML::script('public/assets/js/jquery.fancybox.js?v=2.1.5')); ?>

<?php echo e(HTML::style('public/assets/css/jquery.fancybox.css?v=2.1.5')); ?>

<?php echo e(HTML::script('public/assets/js/facebox.js')); ?>

<?php echo e(HTML::style('public/assets/css/facebox.css')); ?>

<script type="text/javascript">

    $(document).ready(function () {
        $('.fancybox').fancybox();
    });
</script>
<style>
    span.text-danger {
        color: red !important;
    }
</style>
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
                            <?php    global $documents; ?>


                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <!-- <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php if(is_array($value) && array_key_exists('id', $value)): ?>
                                                        <?php if($value['id'] == $userInfo->national_identity_type): ?>
                                                            <td data-title="Identity Type"><?php echo e($value['name']); ?></td>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?> -->
                            <td data-title="Identity Type"><?php echo e($userInfo->national_identity_type); ?></td>
                            <?php if($userInfo->national_identity_type == ""): ?>
                                <td data-title="Identity Type"></td>
                            <?php endif; ?>

                            <td data-title="Picture Selfie Image">
                                <?php if($userInfo->selfie_image != ''): ?>

                                                        <a href="<?php echo e($userInfo->selfie_image); ?>" title="View KYC Document"
                                                            data-fancybox-group="gallery1" class="fancybox" target="_blank">
                                                            <?php echo e(HTML::image($userInfo->selfie_image, SITE_TITLE, [
                                        'style' => 'max-width:50px;
                                                                                                                                                                                                                                                                                                                        max-height:50px;'
                                    ])); ?>

                                                        </a>

                                <?php else: ?>
                                    No Image
                                <?php endif; ?>

                            </td>
                            <td data-title="Picture Front Image">
                                <?php if($userInfo->identity_front_image != ''): ?>
                                                        <a href="<?php echo e($userInfo->identity_front_image); ?>" title="View KYC Document"
                                                            data-fancybox-group="gallery1" class="fancybox" target="_blank">
                                                            <?php echo e(HTML::image($userInfo->identity_front_image, SITE_TITLE, [
                                        'style' => 'max-width:50px;
                                                                                                                                                                                                                                                                                                                        max-height:50px;'
                                    ])); ?>

                                                        </a>
                                <?php else: ?>
                                    No Image
                                <?php endif; ?>

                            </td>
                            <td data-title="Picture Selfie Image">
                                <?php if($userInfo->identity_back_image != ''): ?>
                                                        <a href="<?php echo e($userInfo->identity_back_image); ?>" title="View KYC Document"
                                                            data-fancybox-group="gallery1" class="fancybox" target="_blank">
                                                            <?php echo e(HTML::image($userInfo->identity_back_image, SITE_TITLE, [
                                        'style' => 'max-width:50px;
                                                                                                                                                                                                                                                                                                                        max-height:50px;'
                                    ])); ?>

                                                        </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="search_frm"> <?php    //echo $userInfo->is_kyc_done; ?>

                    <?php    if ($userInfo->kyc_status != "completed") { ?>
                    <a href="#" id="approveBtn" title="Approve" class="btn btn-info">Approve Kyc</a>

                    <!-- <a href="<?php echo e(URL::to('admin/users/approvekyc/' . $userInfo->slug)); ?>" title="Approve"
                                                        class="btn btn-info">Approve Kyc</a> -->
                    <?php    } ?>
                    <!-- <?php    if ($userInfo->kyc_status != "rejected" && $userInfo->kyc_status != "completed") { ?>
                                        <a href="<?php echo e(URL::to('admin/users/declinekyc/' . $userInfo->slug)); ?>" title="Reject"
                                            class="btn btn-info">Reject Kyc</a>
                                        <?php    } ?> -->

                    <a href="<?php echo e(URL::to('admin/users')); ?>" title="Back" class="btn btn-default canlcel_le">Back</a>
                </div>
            </div>
        </section>
        <?php echo e(Form::close()); ?>

    </div>
    </div>

    <div id="approveButton" style="display: none;">
        <div class="nzwh-wrapper">
            <fieldset class="nzwh">
                <legend class="head_pop">Add KYC Details</legend>
                <?php echo e(Form::model($userInfo, ['url' => url('admin/users/approvekyc/' . $userInfo->slug), 'method' => 'post', 'id' => 'adminForm', 'enctype' => 'multipart/form-data'])); ?>

                <div class="form-horizontal">
                    <div class="box-body">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Identity Type <span class="require">*</span></label>
                            <div class="col-sm-10">

                                <?php echo e(Form::select(
            'idType',
            [
                '' => 'Select ID Type',
                'PASSPORT' => 'PASSPORT',
                'IDENTITY_CARD' => 'IDENTITY_CARD',
            ],
            null,
            [
                'class' => 'form-control required',
                'autocomplete' => 'off'
            ]
        )); ?>


                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Identity Number <span class="require">*</span></label>
                            <div class="col-sm-10">
                                <?php echo e(Form::text('idNumber', null, ['class' => 'form-control required', 'id' => 'idNumber', 'placeholder' => 'Identity Number', 'autocomplete' => 'off', 'maxlength' => 20])); ?>

                            </div>
                        </div>
                        <div class="box-footer" style="margin: 0px 0px 0px 100px;">
                            <?php echo e(Form::submit('Submit', ['class' => 'btn btn-info', 'id' => 'approveSubmit'])); ?>

                        </div>
                    </div>
                </div>
                <?php echo e(Form::close()); ?>

            </fieldset>
        </div>
    </div>
<?php else: ?>
    <div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
    <div class="admin_no_record">No record found.</div>
<?php endif; ?>


<script type="text/javascript">
$(document).ready(function () {
    let isSubmitting = false;

    // 🔤 Allow only alphanumeric + hide error when valid
    $("#idNumber").on("input", function () {
        this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');

        // 🔄 Re-validate this field to hide error
        if ($("#adminForm").data("validator")) {
            $("#adminForm").validate().element("#idNumber");
        }
    });

    $("#approveBtn").on("click", function () {
        $("#approveButton").show();

        if (!$("#adminForm").data("validator")) {
            initAdminFormValidation();
        }
    });

    $("#adminForm").on("keydown", "input", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            $("#adminForm").submit();
        }
    });

    $("#adminForm").on("submit", function (e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }

        if (!$(this).valid()) {
            e.preventDefault();
            return false;
        }

        if (!confirm("Are you sure you want to approve this?")) {
            e.preventDefault();
            return false;
        }

        isSubmitting = true;
        $("#approveSubmit")
            .prop("disabled", true)
            .text("Processing...");
    });
});

function initAdminFormValidation() {
    // ✅ Add alphanumeric rule (required!)
    $.validator.addMethod("alphanumeric", function (value, element) {
        return this.optional(element) || /^[a-zA-Z0-9]+$/.test(value);
    }, "Only letters and numbers allowed");

    $("#adminForm").validate({
        ignore: [],
        rules: {
            idNumber: {
                required: true,
                alphanumeric: true
            }
        },
        messages: {
            idNumber: {
                required: "Identity Number is required",
                alphanumeric: "Only letters and numbers allowed"
            }
        },
        errorElement: "span",
        errorClass: "text-danger"
    });
}
</script>


<!-- <script>
document.getElementById('approveSubmit').addEventListener('click', function () {
    this.disabled = true;
    this.value = 'Processing...';
    this.form.submit();
});
</script> --><?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/users/kycdetail.blade.php ENDPATH**/ ?>