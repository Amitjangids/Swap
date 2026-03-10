<?php $__env->startSection('content'); ?>
    <div class="content-wrapper">
        <section class="content-header">
            <h1>View Help Ticket</h1>
            <ol class="breadcrumb">
                <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i>
                        <span>Dashboard</span></a></li>
                <li><a href="<?php echo e(URL::to('admin/help-ticket')); ?>"><i class="fa fa-user-secret"></i> <span>Manage Help
                            Ticket</span></a></li>
                <li class="active"> View Help Ticket</li>
            </ol>
        </section>
        <section class="content">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">&nbsp;</h3>
                </div>
                <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
                <?php echo e(Form::model($recordInfo, ['method' => 'post', 'id' => 'adminForm', 'enctype' => "multipart/form-data"])); ?>

                <div class="form-horizontal">
                    <div class="box-body">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Username<span class="require"></span></label>
                            <div class="col-sm-10">
                                <?php echo e(Form::text('name', $recordInfo->User->name ?? '', ['class' => 'form-control', 'autocomplete' => 'off',$recordInfo->status == 'Resolved' ? 'disabled' : null])); ?>

                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Category<span class="require"></span></label>
                            <div class="col-sm-10">
                                <?php echo e(Form::text('categoryId', $recordInfo->HelpCat->name ?? '', ['class' => 'form-control', 'autocomplete' => 'off',$recordInfo->status == 'Resolved' ? 'disabled' : null])); ?>

                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Ticket ID<span class="require"></span></label>
                            <div class="col-sm-10">
                                <?php echo e(Form::text('ticketId', null, ['class' => 'form-control', 'placeholder' => 'TicketID', 'autocomplete' => 'off',$recordInfo->status == 'Resolved' ? 'disabled' : null])); ?>

                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Description<span class="require"></span></label>
                            <div class="col-sm-10">
                                <?php echo e(Form::text('description', null, ['class' => 'form-control', 'autocomplete' => 'off',$recordInfo->status == 'Resolved' ? 'disabled' : null])); ?>

                            </div>
                        </div>



                        <div class="form-group">
                            <label class="col-sm-2 control-label">Status<span class="require"></span></label>
                            <div class="col-sm-10">
                                <select name="status" class="form-control" <?php if(($recordInfo->status == 'Resolved')): ?> disabled
                                <?php endif; ?>>
                                    <option value="Pending" <?php echo isset($recordInfo->status) && $recordInfo->status == "Pending" ? "selected" : ""; ?>>Pending</option>
                                    <option value="Resolved" <?php echo isset($recordInfo->status) && $recordInfo->status == "Resolved" ? "selected" : ""; ?>>Resolved</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Comment<span class="require"></span></label>
                            <div class="col-sm-10">
                                <?php echo e(Form::textarea('comment', null, [
        'class' => 'form-control',
        'autocomplete' => 'off',
        $recordInfo->status == 'Resolved' ? 'disabled' : null
    ])); ?>


                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Image<span class="require"></span></label>
                            <div class="col-sm-10">
                                <?php if(!empty($recordInfo->imagePath)): ?>
                                    <?php
                                        $filePath = asset('public/uploads/help_tickets/' . $recordInfo->imagePath);
                                        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                                    ?>

                                    <?php if(in_array($extension, ['jpg', 'jpeg', 'png'])): ?>
                                        <img src="<?php echo e($filePath); ?>" alt="Uploaded Image" style="max-height: 150px;">
                                    <?php elseif(in_array($extension, ['pdf', 'doc', 'docx'])): ?>
                                        <a href="<?php echo e($filePath); ?>" target="_blank" class="btn btn-info">
                                            View Document
                                        </a>
                                    <?php else: ?>
                                        <p>No preview available</p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p>No file uploaded</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if($recordInfo->status != 'Resolved'): ?>
                            <div class="box-footer">
                                <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                                <?php echo e(Form::submit('Submit', ['class' => 'btn btn-info'])); ?>

                                <a href="<?php echo e(URL::to('admin/help-ticket')); ?>" title="Cancel"
                                    class="btn btn-default canlcel_le">Cancel</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php echo e(Form::close()); ?>

            </div>
        </section>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/helpticket/view.blade.php ENDPATH**/ ?>