<?php $__env->startSection('content'); ?>
<?php
use App\Http\Controllers\Admin\AdminsController;
?>
<?php
use App\Permission;
?>
<div class="content-wrapper">
    <section class="content-header">
        <h1>Manage Department</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li class="active"> Manage Department</li>
        </ol>
    </section>

    <section class="content">
	<div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
        <div class="box box-info">
           <div class="admin_search">
                <?php
                    $roles1 = AdminsController::getRoles(Session::get('adminid'));   
                ?>
                <?php $permissions = DB::table('permissions')->where('role_id',$roles1)->pluck('permission_name')->toArray();?>
                <?php if(in_array('add-department',$permissions)): ?>
                <div class="add_new_record" style="10px;"><a href="<?php echo e(URL::to('admin/admins/add-department')); ?>" class="btn btn-default"><i class="fa fa-plus"></i> Add Department</a></div>
                <?php endif; ?>
    </div>            
            <div class="m_content" id="listID">
                <?php echo $__env->make('elements.admin.admins.roleList', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
        </div>
    </section>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/admins/listRole.blade.php ENDPATH**/ ?>