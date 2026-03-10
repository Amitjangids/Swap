<!DOCTYPE html>
<html>
    <head>
        <?php
        $cookie_name = "XSRF-TOKEN";
        $cookie_value = csrf_token();
        setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "Secure"); // 86400 = 1 day
        setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "HttpOnly"); // 86400 = 1 day
        ?>
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title><?php echo e($title.TITLE_FOR_LAYOUT); ?></title>
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <link rel="shortcut icon" href="<?php echo FAVICON_PATH; ?>" type="image/x-icon"/>
        <link rel="icon" href="<?php echo FAVICON_PATH; ?>" type="image/x-icon"/>
        <meta http-equiv="X-Frame-Options" content="SAMEORIGIN">
        <meta name="robots" content="noindex, nofollow">


        <?php echo e(HTML::style('public/assets/css/front/bootstrap.min.css')); ?>        

        <?php echo e(HTML::style('public/assets/css/front/main.css?ver=2.5')); ?>

        <?php echo e(HTML::style('public/assets/css/front/responsive.css')); ?>


        <?php echo e(HTML::script('public/assets/js/front/jquery.min.js')); ?>

        <?php echo e(HTML::script('public/assets/js/front/bootstrap.min.js')); ?>

        <?php echo e(HTML::script('public/assets/js/front/custom.js')); ?>

        <?php echo e(HTML::script('public/assets/js/front/jquery.validate_en.min.js')); ?>

        <?php echo e(HTML::script('public/assets/js/ajaxsoringpagging.js')); ?>

        <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    </head>
    <body class="">
        <?php echo $__env->make('elements.header_page', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->yieldContent('content'); ?> 

    </body>
</html><?php /**PATH /var/www/internal-swap-africa/resources/views/layouts/page.blade.php ENDPATH**/ ?>