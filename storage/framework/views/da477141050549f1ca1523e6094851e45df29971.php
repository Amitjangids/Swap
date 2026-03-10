<div class="admin_loader" id="loaderID"><?php echo e(HTML::image("public/img/website_load.svg", '')); ?></div>

<?php if(!$allrecords->isEmpty()): ?>
    <div class="panel-body marginzero">
        <div class="ersu_message"><?php echo $__env->make('elements.admin.errorSuccessMessage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></div>
        <?php echo e(Form::open(array('method' => 'post', 'id' => 'actionFrom'))); ?>

        <input type="hidden" name="page" value="<?php echo e($page); ?>">
        <section id="no-more-tables" class="lstng-section">
            <div class="topn">
                <div class="topn_left"> Travel Document List</div>
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
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('name', 'ID'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Passport', 'Passport'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Airline Ticket', 'Airline Ticket'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Stamp Visa Entry', 'Stamp Visa Entry'));?></th>
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('created_at', 'Date'));?></th> 
                            <th class="sorting_paging"><?php echo \Kyslik\ColumnSortable\SortableLink::render(array ('Action', 'Action'));?></th> 
                        </tr>
                    </thead>
                    <tbody>

                    <?php $__currentLoopData = $allrecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

<tr>
    <td><?php echo e($doc->id); ?></td>

    
    <td>
    <?php if($doc->passport): ?>
        <?php
            $ext = strtolower(pathinfo($doc->passport, PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['jpg','jpeg','png','webp']);
            $fileUrl = HTTP_PATH.'/'.PASSPORT_PATH.$doc->passport;
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

    
    <td>
    <?php if($doc->ticket): ?>
        <?php
            $ext = strtolower(pathinfo($doc->ticket, PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['jpg','jpeg','png','webp']);
            $fileUrl = HTTP_PATH.'/'.TICKET_PATH.$doc->ticket;
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


    
    <td>
    <?php if($doc->visa): ?>
        <?php
            $ext = strtolower(pathinfo($doc->visa, PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['jpg','jpeg','png','webp']);
            $fileUrl = HTTP_PATH.'/'.VISA_PATH.$doc->visa;
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

    <td><?php echo e($doc->created_at); ?></td>
    <td>
        <?php echo e($doc->status === 'approved' ? 'Approved' : ($doc->status === 'declined' ? 'Declined' : '')); ?>

        
        <?php if($doc->status == "pending"): ?>
                                        <a href="<?php echo e(URL::to('admin/users/approveTravel/' . $doc->id)); ?>" title="Approve"
                                            class="btn btn-info">Approve</a>
                                        <a href="<?php echo e(URL::to('admin/users/declineTravel/' . $doc->id)); ?>" title="Decline"
                                            class="btn btn-info">Decline</a>
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
<?php endif; ?> <?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/users/travel_document.blade.php ENDPATH**/ ?>