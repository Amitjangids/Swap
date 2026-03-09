<div class="admin_loader" id="loaderID"><?php echo e(HTML::image("public/img/website_load.svg", '')); ?></div>

<?php if($allrecords): ?>
    <div class="panel-body marginzero">
        <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
        <?php echo e(Form::open(array('method' => 'post', 'id' => 'actionFrom'))); ?>

        <section id="no-more-tables" class="lstng-section">
            <div class="topn">
                <div class="topn_left"> Gabon Visa Stamp </div>
                <div class="topn_rightd ddpagingshorting paggng-txt" id="pagingLinks" align="right">
                    
                </div>
            </div>
            <div class="tbl-resp-listing">
                <table class="table table-bordered table-striped table-condensed cf">
                    <thead class="cf ddpagingshorting">
                        <tr>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('id', 'ID'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Gabon Visa Stamp', 'Gabon Visa Stamp'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Action', 'Action'));?></th> 
                        </tr>
                    </thead>
                    <tbody> 
                        <tr>
                            <td><?php echo e($allrecords->id); ?></td> 
                            <td>
                            <?php if($allrecords->gabonStampImg): ?>
                                <?php
                                    $ext = strtolower(pathinfo($allrecords->gabonStampImg, PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg','jpeg','png','webp']);
                                    $fileUrl = HTTP_PATH.'/'.GABON_VISA_STAMPED.$allrecords->gabonStampImg;
                                ?>

                                <?php if($isImage): ?>
                                    <a href="<?php echo e($fileUrl); ?>" target="_blank">
                                        <?php echo e(HTML::image($fileUrl, '', ['style' => 'width:100px;height:100px;cursor:pointer'])); ?>

                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo e($fileUrl); ?>" target="_blank">View</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                            <td><?php echo e($allrecords->created_at); ?></td>
                            <td>
                                <?php echo e($allrecords->gabonStampStatus === 'approved' ? 'Approved' : ($allrecords->gabonStampStatus === 'declined' ? 'Declined' : '')); ?>

                                <?php if($allrecords->gabonStampStatus == "pending"): ?>
                                    <a href="<?php echo e(URL::to('admin/users/approveGabonStamp/' . $allrecords->id)); ?>" title="Approve"
                                        class="btn btn-info">Approve</a>
                                    <a href="<?php echo e(URL::to('admin/users/declineGabonStamp/' . $allrecords->id)); ?>" title="Decline"
                                        class="btn btn-info">Decline</a>
                                <?php endif; ?>
                            </td>
                        </tr> 

                        
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
<?php endif; ?> <?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/users/gabon_visa_stamp.blade.php ENDPATH**/ ?>