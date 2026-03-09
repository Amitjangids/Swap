<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8" name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="<?php echo e(PUBLIC_PATH); ?>/assets/front/images/swap-favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <?php if(Session::get('locale') == 'fr'): ?>

        <?php echo e(HTML::style('public/assets/css/front/responsive_fr.css')); ?>


        <?php else: ?>
    
        <?php echo e(HTML::style('public/assets/css/front/responsive.css')); ?>        
        <?php endif; ?> 
     
        <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <?php echo e(HTML::script('public/assets/js/front/jquery.min.js')); ?>

        <?php echo e(HTML::script('public/assets/js/front/bootstrap.min.js')); ?>

        <?php echo e(HTML::script('public/assets/js/front/custom.js')); ?>


        <?php if(Session::get('locale') == 'fr'): ?>
        <?php echo e(HTML::script('public/assets/js/front/jquery.validate_fr.min.js')); ?>     

        <?php else: ?>
        <?php echo e(HTML::script('public/assets/js/front/jquery.validate_en.min.js')); ?>         

        <?php endif; ?> 


    <?php echo e(HTML::style('public/assets/front/css/bootstrap.min.css')); ?>

    <?php echo e(HTML::style('public/assets/front/css/custom.css?v=9.1.0')); ?>

    <?php echo e(HTML::style('public/assets/front/css/media.css?v=9.1.0')); ?>

    <?php echo e(HTML::style('public/assets/front/css/owl.carousel.min.css')); ?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
    <title>Swap Wallet</title>
</head>
<?php $bdClas = '';
    if(Session::get('locale') != 'en'){
        $bdClas = 'bdclass';
    }
    
    ?>
<body class="<?php echo $bdClas;?> dashboard-driver-logout">
<?php if (session()->has('error_message')) { ?>
<div class="alert alert-danger" role="alert">
<?php echo e(Session::get('error_message')); ?>

</div>
<?php Session::forget('error_message');   } ?>  

<?php if (session()->has('success_message')) { ?>
        <div class="alert alert-success" role="alert">
            <?php echo e(Session::get('success_message')); ?>

        </div>
<?php Session::forget('success_message'); } ?>

<?php echo e(HTML::script('public/assets/front/js/jquery.min.js')); ?>

<?php echo e(HTML::script('public/assets/front/js/jquery.validate.js')); ?>

<?php echo e(HTML::script('public/assets/front/js/custom.js')); ?>

<?php echo e(HTML::script('public/assets/front/js/bootstrap.bundle.min.js')); ?>

<?php echo e(HTML::script('public/assets/front/js/owl.carousel.min.js')); ?>


<script>
    $(document).ready(function () {
        // Hide the success message after 5 seconds
        setTimeout(function () {
            $(".alert-success").fadeOut('slow');
        }, 5000);
    });
</script>

<script>
    $(document).ready(function () {
        // Hide the success message after 5 seconds
        setTimeout(function () {
            $(".alert-danger").fadeOut('slow');
        }, 5000);
    });
</script>
<div class="SelectBox ml-auto guestuser"><?php //echo Session::get('locale');?>
                    <?php 
                    if(!empty(Session::get('locale'))){
                        $lang = Session::get('locale');
                    } else{
                        $lang = 'en';
                    }
                    $langList = array('fr'=>'French','en'=>'English');?>
                    <?php echo e(Form::select('language', $langList,$lang, ['class' => '','onChange' => "changeLanguage(this.value)"])); ?>

                    <div class="chevron">
                        <?php echo e(HTML::image('public/img/front/drop-arrow.svg', SITE_TITLE)); ?>

                    </div>
                </div>


<script type="text/javascript">
    function changeLanguage(val) {
        var lang= val;
        //alert(lang);
        $.ajax({
            url: "<?php echo HTTP_PATH; ?>/lang/" + val,
            type: "GET",
            success: function (result) {
            
                location.reload();
            }

        });
    }
</script>
<?php echo $__env->yieldContent('content'); ?> 

</body>
</html><?php /**PATH /var/www/internal-swap-africa/resources/views/layouts/login.blade.php ENDPATH**/ ?>