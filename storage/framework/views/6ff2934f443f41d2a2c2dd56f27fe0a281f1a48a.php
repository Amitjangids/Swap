<?php $__env->startSection('content'); ?>
    <script type="text/javascript">
        $(document).ready(function () {
            $.validator.addMethod("alphanumeric", function (value, element) {
                return this.optional(element) || /^[\w.]+$/i.test(value);
            }, "Only letters, numbers and underscore allowed.");
            $.validator.addMethod("passworreq", function (input) {
                var reg = /[0-9]/; //at least one number
                var reg2 = /[a-z]/; //at least one small character
                var reg3 = /[A-Z]/; //at least one capital character
                //var reg4 = /[\W_]/; //at least one special character
                return reg.test(input) && reg2.test(input) && reg3.test(input);
            }, "Password must be a combination of Numbers, Uppercase & Lowercase Letters.");
            $("#adminForm").validate();

        });
    </script>

    <div class="content-wrapper">
        <section class="content-header">
            <h1>Edit Card Content</h1>
            <ol class="breadcrumb">
                <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i>
                        <span>Dashboard</span></a></li>
                <li><a href="<?php echo e(URL::to('admin/banners')); ?>"><i class="fa fa-picture-o"></i> <span>Manage Card
                            Content</span></a></li>
                <li class="active"> Edit Card Content</li>
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
                            <label class="col-sm-2 control-label">Title <span class="require">*</span></label>
                            <div class="col-sm-10">
                                <?php echo e(Form::text('title', null, ['class' => 'form-control required', 'placeholder' => 'Title', 'autocomplete' => 'off'])); ?>

                            </div>
                        </div> 
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Description <span class="require">*</span></label>
                            <div class="col-sm-10">
                                <div id="listInputs">
                                    <?php $__currentLoopData = $recordInfo->description; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="row removeDes">
                                        <div class="col-sm-11">
                                            <input name="description[]" type="text" class="form-control" value="<?php echo e($item); ?>"
                                            placeholder="Enter content">
                                        </div>
                                        <div class="col-sm-1">
                                            <button type="button" class="btn btn-danger removeBtn"><i class="fa fa-minus"></i></button>
                                        </div>
                                    </div></br>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                                <button type="button" class="btn btn-success mt-2" onclick="addMore()" style="background:green"><i class="fa fa-plus"></i></button>

                            </div>
                        </div>

                        <div class="box-footer">
                            <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                            <?php echo e(Form::submit('Submit', ['class' => 'btn btn-info'])); ?>

                            <a href="<?php echo e(URL::to('admin/cardcontents')); ?>" title="Cancel"
                                class="btn btn-default canlcel_le">Cancel</a>
                        </div>
                    </div>
                </div>
                <?php echo e(Form::close()); ?>

            </div>
        </section>
    </div>
<?php $__env->stopSection(); ?>
<script>
function addMore() {
    const html = `<div class="row removeDes">
            <div class="col-sm-11">
                <input name="description[]" type="text" class="form-control" placeholder="Enter content">
            </div>
            <div class="col-sm-1">
            <button type="button" class="btn btn-danger removeBtn"><i class="fa fa-minus"></i></button>
            </div></div></br>`;
    document.getElementById('listInputs').insertAdjacentHTML('beforeend', html);
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('removeBtn')) {
        e.target.closest('.removeDes').remove();
    }
});
</script>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/card_content/edit.blade.php ENDPATH**/ ?>