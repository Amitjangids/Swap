<header class="main-header">
    <a href="<?php echo e(URL::to( 'admin/admins/dashboard')); ?>" class="logo">
        <span class="logo-mini"><b><?php echo e(HTML::image(LOGO_PATH, SITE_TITLE,['style'=>"max-width: 20px"])); ?></b></span>
        <span class="logo-lg"><?php echo e(HTML::image(LOGO_PATH, SITE_TITLE)); ?></span>
    </a>
    <nav class="navbar navbar-static-top">
        <a href="javascript:void(0);" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
        </a>

        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
                <li class="">
                    <a href="<?php echo e(URL::to( 'admin/admins/dashboard')); ?>">
                        <span class="hidden-xs"><?php echo e(ucwords(Session::get('admin_username'))); ?></span>
                    </a>
                </li>
                <li><a href="<?php echo e(URL::to( 'admin/admins/logout')); ?>" class=""><i class="fa fa-sign-out fa-lg"></i> Logout</a></li>
            </ul>
        </div>
    </nav>
</header><?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/header.blade.php ENDPATH**/ ?>