<?php use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
?>
<?php echo e(HTML::script('public/assets/js/facebox.js')); ?>

<?php echo e(HTML::style('public/assets/css/facebox.css')); ?>

<?php
use App\Http\Controllers\Admin\AdminsController;
?>
<?php
use App\Permission;
?>
<div class="admin_loader" id="loaderID"><?php echo e(HTML::image("public/img/website_load.svg", '')); ?></div>
<?php if(!$allrecords->isEmpty()): ?>
<div class="panel-body marginzero">
    <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
    <?php echo e(Form::open(array('method' => 'post', 'id' => 'actionFrom'))); ?>

    <input type="hidden" name="page" value="<?php echo e($page); ?>">
    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="topn_left">Companies List</div>
            <div class="topn_rightd ddpagingshorting paggng-txt" id="pagingLinks" align="right">
                <div class="topn_righ">
                    Showing <?php echo e($allrecords->count()); ?> of <?php echo e($allrecords->total()); ?> record(s).
                </div>
                <div class="panel-heading" style="align-items:center;">
                    <?php echo e($allrecords->appends(Request::except('_token'))->render()); ?>

                </div>
            </div>                
        </div>
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        <th style="width:5%">#</th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('company_name', 'Company Name'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('company_code', 'Company Code'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('username', 'Username'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('phone', 'phone'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('email', 'Company Email'));?></th>
                        <th class="sorting_paging">Company Address</th>
                        <th class="sorting_paging">Website</th>
                        <th class="sorting_paging">Profile</th>
                        <th class="sorting_paging">Wallet Balance</th>
                        <?php if(Session::get('admin_role')==1): ?>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('parent_id', 'Created By'));?></th>
                        <?php endif; ?>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('edited_by', 'Edited By'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('status', 'Status'));?></th>
                        <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('created_at', 'Date'));?></th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allrecord): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <th style="width:5%"><input type="checkbox" onclick="javascript:isAllSelect(this.form);" name="chkRecordId[]" value="<?php echo e($allrecord->id); ?>" /></th>
                        <td data-title="Full Name"><?php echo e($allrecord->company_name); ?></td>
                        <td data-title="Email Address"><?php echo e($allrecord->company_code ? $allrecord->company_code : 'N/A'); ?></td>
                        <td data-title="Email Address"><?php echo e($allrecord->username ? $allrecord->username : 'N/A'); ?></td>
                        <td data-title="Contact Number"><?php echo e($allrecord->phone ? $allrecord->phone : 'N/A'); ?></td>
                        <td data-title="Email Address"><?php echo e($allrecord->email  ? $allrecord->email:'N/A'); ?></td>
                        <td data-title="Email Address"><?php echo e($allrecord->company_address  ? $allrecord->company_address : 'N/A'); ?></td>
                        <td data-title="Email Address">
                        <?php if($allrecord->website): ?>
                            <a href="<?php echo e($allrecord->website); ?>" target="_blank"><?php echo e($allrecord->website); ?></a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                        </td>

                        <td data-title="Email Address">
                        <?php if($allrecord->profile): ?>
                            <img src="<?php echo e(HTTP_PATH.'/public/assets/company_logo/'.$allrecord->profile); ?>" height="50px" width="50px"/>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                        </td>
                        
                        <td data-title="Email Address"><?php echo e($allrecord->wallet_balance  ? $allrecord->wallet_balance : '0'); ?></td>

                        <?php if(Session::get('admin_role')==1): ?>
                        <td data-title="Email Address"><?php echo e($allrecord->createdBy->username); ?></td>
                        <?php endif; ?>
                        <td data-title="Email Address"><?php echo e($allrecord->editedBy->username); ?></td>
                        <td data-title="Email Address"><?php echo e($allrecord->status==1  ? 'Activated' : 'Deactivated'); ?></td>
                        <td data-title="Date"><?php echo e($allrecord->created_at->format('M d, Y h:i A')); ?></td>
                        <td data-title="Action">
                            <div id="loderstatus<?php echo e($allrecord->id); ?>" class="right_action_lo"><?php echo e(HTML::image("public/img/loading.gif", '')); ?></div>


                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                    <!-- <li class="" id="status<?php echo e($allrecord->id); ?>">
                                        <?php if($allrecord->status == '1'): ?>
                                        <a href="<?php echo e(URL::to( 'admin/updateCompanyStatus/'.$allrecord->slug.'/0')); ?>" title="Deactivate" class="deactivate"><i class="fa fa-ban"></i>Deactivate Company</a>
                                        <?php else: ?>
                                        <a href="<?php echo e(URL::to( 'admin/updateCompanyStatus/'.$allrecord->slug.'/1')); ?>" title="Activate" class="activate"><i class="fa fa-check"></i>Activate Company</a>
                                        <?php endif; ?>
                                    </li> -->
                                    <?php
                                        $roles = AdminsController::getRoles(Session::get('adminid'));   
                                    ?>
                                    <?php $permissions = DB::table('permissions')->where('role_id',$roles)->pluck('permission_name')->toArray();?>
                                  
                                    <?php if(in_array('edit-company',$permissions)): ?>
                                    <li><a href="<?php echo e(URL::to( 'admin/admins/edit-company/'.$allrecord->slug)); ?>" title="Edit" class=""><i class="fa fa-pencil"></i>Edit Company</a></li>
                                    <?php endif; ?>

                                    <?php if(in_array('pay-company',$permissions)): ?>
                                    <li><a href="<?php echo e(URL::to( 'admin/admins/pay-company/'.$allrecord->slug)); ?>" title="Pay Company" class=""><i class="fa fa-money"></i>Pay Company</a></li>
                                    <?php endif; ?>

                                    <?php if(in_array('company-transaction-history',$permissions)): ?>
                                    <li><a href="<?php echo e(URL::to( 'admin/admins/company-transaction-history/'.$allrecord->slug)); ?>" title="Transaction History" class=""><i class="fa fa-eye"></i> Transaction History</a></li>
                                    <?php endif; ?>

                                </ul>
                            </div>
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
<?php endif; ?>

<?php if(!$allrecords->isEmpty()): ?>
<?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allrecord): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div id="info<?php echo $allrecord->id; ?>" style="display: none;">
    <div class="nzwh-wrapper">
        <fieldset class="nzwh">
            <legend class="head_pop">Agent Details</legend>
            <div class="drt">
                <div class="admin_pop"><span>User Type: </span>  <label><?php if(isset($allrecord->user_type)): ?> <?php echo e($allrecord->user_type); ?> <?php endif; ?></label></div>
                <div class="admin_pop"><span>Full Name: </span>  <label><?php echo $allrecord->name; ?></label></div>
                <div class="admin_pop"><span>Wallet Balance: </span>  <label><?php echo CURR.' '.$allrecord->wallet_balance; ?></label></div>
                <div class="admin_pop"><span>Email Address: </span>  <label><?php echo e($allrecord->email?$allrecord->email:'N/A'); ?></label></div>
                <div class="admin_pop"><span>Phone Number: </span>  <label><?php echo $allrecord->phone; ?></label></div>
                <div class="admin_pop"><span>Date Of Birth: </span>  <label><?php echo $allrecord->dob; ?></label></div>
                <!-- <div class="admin_pop"><span>City: </span>  <label><?php echo $allrecord->City?$allrecord->City->name_en:'N/A'; ?></label></div>
                <div class="admin_pop"><span>Area: </span>  <label><?php echo e($allrecord->Area?$allrecord->Area->name:'N/A'); ?></label></div> -->
                <!--<div class="admin_pop"><span>Business registration number: </span>  <label><?php //echo $allrecord->national_identity_number?Crypt::decryptString($allrecord->national_identity_number):'';?></label></div>-->

                <?php if($allrecord->profile_image != ''): ?>
                <div class="admin_pop"><span>Profile Picture</span> <label><?php echo e(HTML::image(PROFILE_FULL_DISPLAY_PATH.$allrecord->profile_image, SITE_TITLE,['style'=>"max-width: 200px"])); ?></label></div>
                <?php endif; ?>

<!--                <?php if($allrecord->identity_image != ''): ?>
                <div class="admin_pop"><span>Picture national identity</span> <label><?php echo e(HTML::image(IDENTITY_FULL_DISPLAY_PATH.$allrecord->identity_image, SITE_TITLE,['style'=>"max-width: 200px"])); ?></label></div>
                <?php endif; ?>-->

        </fieldset>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?><?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/admins/companyList.blade.php ENDPATH**/ ?>