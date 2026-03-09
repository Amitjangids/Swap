<?php if(count($errors) > 0 || Session::has('error_message') || isset($error_message)): ?>
    <div class="alert alert-block alert-danger fade in">
        <button data-dismiss="alert" class="close close-sm" type="button">
            <i class="fa fa-times"></i>
        </button>
        <?php if(isset($error_message)): ?> <?php echo e($error_message); ?> <?php endif; ?>
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
             <?php $errorArr = explode('\n', $error);
             if(count($errorArr) > 1){
                 for($i=0;$i<count($errorArr);$i++){
                     echo $errorArr[$i].'<br>';
                 }
             } else{
                 echo $error.'<br/>';
             }
             ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?> 
        <?php if(Session::has('error_message')): ?> <?php echo e(Session::get('error_message')); ?> <?php endif; ?>
    </div>
<?php endif; ?>

<?php if(Session::has('success_message')): ?> 
    <div class="alert alert-success fade in">
        <button data-dismiss="alert" class="close close-sm" type="button"><i class="fa fa-times"></i></button>
        <?php echo e(Session::get('success_message')); ?> 
        <?php echo e(Session::forget('success_message')); ?>

    </div>
<?php endif; ?><?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/errorSuccessMessage.blade.php ENDPATH**/ ?>