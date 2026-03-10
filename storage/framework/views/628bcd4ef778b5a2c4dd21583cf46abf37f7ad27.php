<?php echo e(HTML::script('public/assets/js/facebox.js')); ?>

<?php echo e(HTML::style('public/assets/css/facebox.css')); ?>

<?php
use App\Http\Controllers\Admin\AdminsController;
?>
<?php
use App\Permission;
?>
<script type="text/javascript">
    $(document).ready(function ($) {
        $('.close_image').hide();
        $('a[rel*=facebox]').facebox({
            closeImage: '<?php echo HTTP_PATH; ?>/public/img/close.png'
        });
        
        $('.dropdown-menu a').on('click', function (event) { 
            $(this).parent().parent().parent().toggleClass('open');
        });
    });
</script>
<div class="admin_loader" id="loaderID"><?php echo e(HTML::image("public/img/website_load.svg", '')); ?></div>
<?php if(!$roles->isEmpty()): ?>
<div class="panel-body marginzero">
   <!-- <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div> -->
    <?php echo e(Form::open(array('method' => 'post', 'id' => 'actionFrom'))); ?>

    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="topn_left">Department List</div>
            <div class="topn_rightd ddpagingshorting" id="pagingLinks" align="right">
                <div class="panel-heading" style="align-items:center;">
                   
                </div>
            </div>                
        </div>
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        <th style="width:5%">Sno</th>
                        <th class="sorting_paging">Department name</th>
                        <th class="sorting_paging">Permission</th>
                        <th class="sorting_paging">Date</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
					<?php
					$permissions = getPermissionByRoleId($role->id);
					?>

                 
                    <tr>
                        <th style="width:3%"><?php echo e($key+1); ?></th>
                        <td style="width:20%" data-title="Department Name"><?php echo e($role->role_name); ?></td>
                        <td style="width:55%" data-title="Permission">
                            <?php
                            $formattedPermissions = implode(', ', array_map(function($permission) {
                                return ucwords(trim($permission));
                            }, explode(',', str_replace('-', ' ', $permissions))));
                            echo $formattedPermissions;
                            ?>
                        </td>
                        <td style="width:17%" data-title="Date">
						<?php
						 $date = date_create($role->created_at);
						 $createdAt = date_format($date,'M d, Y h:i A');
						?>
						<?php echo e($createdAt); ?>

						</td>
                        <td style="width:5%" data-title="Action">
                            <div id="loderstatus<?php echo e($role->id); ?>" class="right_action_lo"><?php echo e(HTML::image("public/img/loading.gif", '')); ?></div>
                            
                            <?php
                                $roles1 = AdminsController::getRoles(Session::get('adminid'));   
                            ?>
                        
                    
                            <?php $permissions = DB::table('permissions')->where('role_id',$roles1)->pluck('permission_name')->toArray();?>
                            <?php if(in_array('edit-department',$permissions)): ?>
                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                    <li><a href="<?php echo e(URL::to( 'admin/admins/edit-department/'.$role->id)); ?>" title="Edit Role" class=""><i class="fa fa-pencil"></i>Edit Department</a></li>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
                
        </div>
    </section>
    <?php echo e(Form::close()); ?>

</div>         
</div> 
<?php else: ?> 
<div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
<div class="admin_no_record">No record found.</div>
<?php endif; ?><?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/admins/roleList.blade.php ENDPATH**/ ?>