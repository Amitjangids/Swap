<?php $__env->startSection('content'); ?>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

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
        
        $("#user_type").change(function () {
            var catid = $("#user_type").val();
            $("#category").load('<?php echo HTTP_PATH . '/admin/banners/getcategorylist/' ?>' + catid);


        });
    });
</script>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Add Banner</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="<?php echo e(URL::to('admin/banners')); ?>"><i class="fa fa-picture-o"></i> <span>Manage Banners</span></a></li>
            <li class="active"> Add Banner</li>
        </ol>
    </section>
    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
            <?php echo e(Form::open(array('method' => 'post', 'id' => 'adminForm', 'enctype' => "multipart/form-data"))); ?>

            <div class="form-horizontal">
                <div class="box-body">

                    <?php  global $userType;?>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">User Type <span class="require">*</span></label>
                        <div class="col-sm-10">
                            <?php echo e(Form::select('user_type', $userType,null, ['class' => 'form-control required','placeholder' => 'Select User Type','id'=>'user_type'])); ?>

                        </div>
                    </div>
                    
                    <!--<div class="form-group">-->
                    <!--    <label class="col-sm-2 control-label">Category <span class="require">*</span></label>-->
                    <!--    <div class="col-sm-10" id="category">-->
                    <!--        <?php echo e(Form::select('category', array(),null, ['class' => 'form-control required','placeholder' => 'Select Category Type'])); ?>-->
                    <!--    </div>-->
                    <!--</div>-->
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Banner Name <span class="require">*</span></label>
                        <div class="col-sm-10">
                            <?php echo e(Form::text('banner_name', null, ['class'=>'form-control required', 'placeholder'=>'Banner Name', 'autocomplete' => 'off'])); ?>

                        </div>
                    </div>
                    


                    <div class="form-group">
                        <label class="col-sm-2 control-label">Banner Image <span class="require">*</span></label>
                        <div class="col-sm-10">
                            <?php echo e(Form::file('banner_image', ['id'=>'image','class'=>'form-control required', 'accept'=>IMAGE_EXT])); ?>

                            <span class="help-text"> Supported File Types: jpg, jpeg, png (Max. <?php echo e(MAX_IMAGE_UPLOAD_SIZE_DISPLAY); ?>). Upload 1400*600px files for better resolution in app and web both.</span>
                        </div>
                    </div>

                    <div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        <?php echo e(Form::submit('Submit', ['class' => 'btn btn-info'])); ?>

                        <?php echo e(Form::reset('Reset', ['class' => 'btn btn-default canlcel_le'])); ?>

                    </div>
                </div>
            </div>
            <?php echo e(Form::close()); ?>

        </div>
    </section>

</div>
    <script>
    
    function in_array(needle, haystack) {
        for (var i = 0, j = haystack.length; i < j; i++) {
            if (needle == haystack[i])
                return true;
        }
        return false;
    }

    function getExt(filename) {
        var dot_pos = filename.lastIndexOf(".");
        if (dot_pos == -1)
            return;
        return filename.substr(dot_pos + 1).toLowerCase();
    }
    
    function imageValidation() {
        
        var filename = document.getElementById("image").value;
        var filetype = ['jpg', 'jpeg', 'png'];
        if (filename != '') {
            var ext = getExt(filename);
            ext = ext.toLowerCase();
            var checktype = in_array(ext, filetype);
            if (!checktype) {
                alert(ext + " file not allowed for image.");
                document.getElementById("image").value = '';
                return false;
            } else {
                var fi = document.getElementById('image');
                var filesize = fi.files[0].size;//check uploaded file size
                if (filesize > 2097152) {
                    alert('Maximum 2MB file size allowed for image.');
                    document.getElementById("image").value = '';
                    return false;
                }
            }
        }        
    }

</script>
    <?php $__env->stopSection(); ?>





<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/banners/add.blade.php ENDPATH**/ ?>