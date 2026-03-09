<?php $__env->startSection('content'); ?>
<style>
@import  url("https://fonts.googleapis.com/css2?family=Fontdiner+Swanky&family=Roboto:wght@500&display=swap");
* {
box-sizing: 0;
margin: 0;
padding: 0;
}

.not_permitted_main {
display: flex;
align-items: center;
height: 100vh;
max-width: 1000px;
width: calc(100% - 4rem);
margin: 0 auto;
}
.not_permitted_main > * {
display: flex;
flex-flow: column;
align-items: center;
justify-content: center;
height: 100vh;
max-width: 500px;
width: 100%;
padding: 2.5rem;
}

.not_permitted_main aside {
background-image: url("/public/img/right-edges.png");
background-position: top right;
background-repeat: no-repeat;
background-size: 25px 100%;
}
.not_permitted_main aside img {
display: block;
height: auto;
width: 100%;
}

.not_permitted_main main {
text-align: center;
background: #383838;
}
.not_permitted_main main h1 {
font-family: "Fontdiner Swanky", cursive;
font-size: 4rem;
color: #c5dc50;
margin-bottom: 1rem;
}
.not_permitted_main main p {
margin-bottom: 2.5rem;
color:#FFF;
}
.not_permitted_main main p em {
font-style: italic;
color: #c5dc50;
}
.not_permitted_main main button {
font-family: "Fontdiner Swanky", cursive;
font-size: 1rem;
color: #383838;
border: none;
background-color: #f36a6f;
padding: 1rem 2.5rem;
transform: skew(-5deg);
transition: all 0.1s ease;
cursor: url("/public/img/cursors-eye.png"), auto;
}
.not_permitted_main main button:hover {
background-color: #c5dc50;
transform: scale(1.15);
}

@media (max-width: 700px) {

.not_permitted_main {
flex-flow: column;
}
.not_permitted_main > * {
max-width: 700px;
height: 100%;
}

.not_permitted_main aside {
background-image: none;
background-color: white;
}
.not_permitted_main aside img {
max-width: 300px;
}
}
</style>
<div class="content-wrapper">
<section class="content-header">
<h1>Not Permitted</h1>
<ol class="breadcrumb">
<li><a href="<?php echo e(URL::to('admin/admins/dashboard')); ?>"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
</ol>
</section>
<section class="content">
<div class="box box-info">
<div class="not_permitted_main">
<aside><img src="<?php echo e(HTTP_PATH); ?>/public/img/Mirror.png" alt="You are not permitted to view this page." />
</aside>
<main>
<h1>Sorry!</h1>
<p>You dont have permission to perform action on this page <em>. . . like your social life.</em></p>
<button onclick="location.href='/admin/admins/dashboard';">You can go now!</button>
</main>
</div>

</div>
</section>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/internal-swap-africa/resources/views/admin/admins/notPermitted.blade.php ENDPATH**/ ?>