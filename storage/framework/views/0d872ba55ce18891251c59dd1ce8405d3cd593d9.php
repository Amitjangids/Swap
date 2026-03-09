
<?php $__env->startSection('content'); ?>
    <script type="text/javascript">
        $(document).ready(function() {
            $("#adminForm").validate({
                rules: {
                    accountId: {
                        required: true,
                        digits: true,
                        minlength: 7,
                        maxlength: 15
                    },
                    last4Digits: {
                        required: true,
                        digits: true,
                        minlength: 4,
                        maxlength: 4
                    }
                },
                messages: {
                    accountId: {
                        required: "Please enter Account ID",
                        digits: "Only digits allowed",
                        minlength: "Account ID must be exactly 7 digits",
                        maxlength: "Account ID must be exactly 15 digits"
                    },
                    last4Digits: {
                        required: "Please enter last 4 digits",
                        digits: "Only digits allowed",
                        minlength: "Last 4 digits must be exactly 4 digits",
                        maxlength: "Last 4 digits must be exactly 4 digits"
                    }
                }
            });
        });
    </script>
    <div class="content-wrapper">
        <section class="content-header">
            <h1>Assign Card Request</h1>
            <ol class="breadcrumb">
                <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i>
                        <span>Dashboard</span></a></li>
                <li><a href="<?php echo e(URL::to('admin/card-request/list')); ?>"><i class="fa fa-user"></i> <span>Manage
                            Card Request</span></a></li>
                <li class="active"> Assign Card Request</li>
            </ol>
        </section>
        <section class="content">
            <div class="box box-info">
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
                                <?php global $documents; ?>
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
                                <?php if($userInfo->national_identity_type == ''): ?>
                                    <td data-title="Identity Type"></td>
                                <?php endif; ?>

                                <td data-title="Picture Selfie Image">
                                    <?php if($userInfo->selfie_image != ''): ?>
                                        <a href="<?php echo e($userInfo->selfie_image); ?>" title="View KYC Document"
                                            data-fancybox-group="gallery1" class="fancybox" target="_blank">
                                            <?php echo e(HTML::image($userInfo->selfie_image, SITE_TITLE, [
                                                'style' => 'max-width:50px;
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            max-height:50px;',
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
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            max-height:50px;',
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
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            max-height:50px;',
                                            ])); ?>

                                        </a>
                                    <?php endif; ?>

                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="box-header with-border">
                    <h3 class="box-title">&nbsp;</h3>
                </div>
                <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
                <?php if($recordInfo->status == 1): ?>
                    <div class="alert alert-success text-center" style="font-size:16px;">
                        <strong>This card request has already been approved.</strong>
                    </div>
                    <div class="form-horizontal">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Account Id</label>
                                <div class="col-sm-10">
                                    <input type="text" value="<?php echo e($userInfo->accountId); ?>" class="form-control" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label">Last 4 Digits</label>
                                <div class="col-sm-10">
                                    <input type="text" value="<?php echo e($userInfo->last4Digits); ?>" class="form-control"
                                        readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label">Card Type</label>
                                <div class="col-sm-10">
                                    <input type="text" value="<?php echo e($userInfo->cardType); ?>" class="form-control" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php echo e(Form::model($recordInfo, ['method' => 'post', 'id' => 'adminForm', 'enctype' => 'multipart/form-data'])); ?>

                    <div class="form-horizontal">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Account Id <span class="require">*</span></label>
                                <div class="col-sm-10">
                                    <?php echo e(Form::text('accountId', null, ['class' => 'form-control required', 'placeholder' => 'Account Id', 'autocomplete' => 'off', 'maxlength' => 15])); ?>

                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Last 4 digits <span class="require">*</span></label>
                                <div class="col-sm-10">
                                    <?php echo e(Form::text('last4Digits', null, ['class' => 'form-control required', 'placeholder' => 'Last 4 digits', 'autocomplete' => 'off', 'maxlength' => 4])); ?>

                                </div>
                            </div>
                            <div class="box-footer">
                                <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                                <?php echo e(Form::submit('Assign', ['class' => 'btn btn-info'])); ?>

                                <?php echo e(Form::reset('Reset', ['class' => 'btn btn-default canlcel_le'])); ?>

                            </div>
                        </div>
                    </div>
                    <?php echo e(Form::close()); ?>

                <?php endif; ?>
            </div>
        </section>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/card-request/card-assign.blade.php ENDPATH**/ ?>