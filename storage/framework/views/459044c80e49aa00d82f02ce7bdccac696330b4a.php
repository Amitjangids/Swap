<?php echo e(HTML::script('public/assets/js/jquery-2.1.0.min.js')); ?>

<?php if($status=='1'): ?>
<script>
    $(document).ready(function () {
        $('#status_<?php echo $id;?>').html('Activated');
    });
</script>
<?php else: ?>
<script>
    $(document).ready(function () {
        $('#status_<?php echo $id;?>').html('Deactivated');
    });
</script>
<?php endif; ?>


<?php if($status=='1'): ?>
    <a href="<?php echo e(URL::to($action)); ?>" title="Deactivate" class="deactivate"><i class="fa fa-check"></i>Deactivate</a>
<?php else: ?>
    <a href="<?php echo e(URL::to($action)); ?>" title="Activate" class="activate"><i class="fa fa-ban"></i>Activate</a>
<?php endif; ?><?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/active_status.blade.php ENDPATH**/ ?>