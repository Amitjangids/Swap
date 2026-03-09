<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{PUBLIC_PATH}}/assets/front/images/swap-favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    {{ HTML::style('public/assets/front/css/bootstrap.min.css')}}
    {{ HTML::style('public/assets/front/css/custom.css?v=4.0')}}
    {{ HTML::style('public/assets/front/css/media.css?v=4.0')}}
    {{ HTML::style('public/assets/front/css/owl.carousel.min.css')}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <title>Swap Wallet</title>
</head>
<body>
{{ HTML::script('public/assets/front/js/jquery.min.js') }}
{{ HTML::script('public/assets/front/js/jquery.validate.js') }}
{{ HTML::script('public/assets/front/js/custom.js') }}
{{ HTML::script('public/assets/front/js/bootstrap.bundle.min.js') }}
{{ HTML::script('public/assets/front/js/owl.carousel.min.js') }}
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>

<div class="black-layer"></div>
<?php 
$user_type = Auth::user()->user_type; 
$currentUrl = url()->current();
$currentUrl = rtrim($currentUrl, '/');
$lastSegment = basename($currentUrl);
?>




<div class="sidebar">
    <ul class="nav-links">
      <li class="<?php if($lastSegment=='dashboard'  || $lastSegment=='approver-dashboard'  || $lastSegment=='submitter-dashboard') { echo "active"; } ?>">
         <?php if($user_type=="Merchant") {  ?>
         <a class="<?php if($lastSegment=='dashboard') { echo "active"; } ?>" href="{{HTTP_PATH}}/dashboard">
        <?php }elseif($user_type=="Approver"){ ?>
          <a class="<?php if($lastSegment=='approver-dashboard') { echo "active"; } ?>"  href="{{HTTP_PATH}}/approver-dashboard">
        <?php }else{ ?>
          <a class="<?php if($lastSegment=='submitter-dashboard') { echo "active"; } ?>"  href="{{HTTP_PATH}}/submitter-dashboard">
        <?php } ?>

          <img src="{{PUBLIC_PATH}}/assets/front/images/dashboard-img.png" alt="image">
          <span class="link_name">Dashboard</span>
        </a>
      </li>

      <li class="show-list <?php if($lastSegment=='pending-approvals' || $lastSegment=='transition-history' || $lastSegment=='single-transfer' || $lastSegment=='bulk-transfer'){ echo "active"; } ?>">
        <div class="icon-link">
          <a href="javascript:void(0)">
            <img src="{{PUBLIC_PATH}}/assets/front/images/transaction-img.png" alt="image">
            <span class="link_name">Transaction</span>
          </a>
          <i class="fa fa-angle-down arrow" aria-hidden="true"></i>
        </div>
        <ul class="sub-menu">
          <?php if($user_type=="Submitter") {  ?>
          <li class='<?php if($lastSegment=='single-transfer') { echo "active"; } ?>'><a href="{{HTTP_PATH}}/single-transfer">Single Transaction</a></li>
          <li class='<?php if($lastSegment=='bulk-transfer') { echo "active"; } ?>'><a href="{{HTTP_PATH}}/bulk-transfer">Bulk Transactions</a></li>
          <?php } ?>

          <li class='<?php if($lastSegment=='pending-approvals') { echo "active"; } ?>'><a href="{{HTTP_PATH}}/pending-approvals">Pending Approvals</a></li>
          <li class='<?php if($lastSegment=='transition-history') { echo "active"; } ?>'><a href="{{HTTP_PATH}}/transition-history">Transactions History</a></li>
        </ul>
      </li>

      <li class="<?php if($lastSegment=='beneficiary-list') { echo "active"; } ?>">
        <a class="<?php if($lastSegment=='beneficiary-list') { echo "active"; } ?>" href="{{HTTP_PATH}}/beneficiary-list">
          <img src="{{PUBLIC_PATH}}/assets/front/images/beneficiary-icon.png" alt="image">
          <span class="link_name">Beneficiary Accounts List</span>
        </a>
      </li>

      <li class="<?php if($lastSegment=='operations-month') { echo "active"; } ?>">
        <a class="<?php if($lastSegment=='operations-month') { echo "active"; } ?>" href="{{HTTP_PATH}}/operations-month">
          <img src="{{PUBLIC_PATH}}/assets/front/images/number-of-operations.png" alt="image">
          <span class="link_name">Operations of the Month</span>
        </a>
      </li>

      <li class="<?php if($lastSegment=='number_success') { echo "active"; } ?>">
        <a class="<?php if($lastSegment=='number_success') { echo "active"; } ?>" href="{{HTTP_PATH}}/number_success">
          <img src="{{PUBLIC_PATH}}/assets/front/images/success-transition.png" alt="image">
          <span class="link_name">Number of Success</span>
        </a>
      </li>


      <li class="<?php if($lastSegment=='failure-transaction') { echo "active"; } ?>">
        <a class="<?php if($lastSegment=='failure-transaction') { echo "active"; } ?>" href="{{HTTP_PATH}}/failure-transaction">
          <img src="{{PUBLIC_PATH}}/assets/front/images/failed-transition.png" alt="image">
          <span class="link_name">Failure Transaction</span>
        </a>
      </li>


      <li class="<?php if($lastSegment=='customer-deposits') { echo "active"; } ?>">
        <a class="<?php if($lastSegment=='customer-deposits') { echo "active"; } ?>" href="{{HTTP_PATH}}/customer-deposits">
          <img src="{{PUBLIC_PATH}}/assets/front/images/customer-deposits.png" alt="image">
          <span class="link_name">Customer Accounting</span>
        </a>
      </li>


      <!-- <li class="<?php if($lastSegment=='movement-shipments') { echo "active"; } ?>">
        <a class="<?php if($lastSegment=='movement-shipments') { echo "active"; } ?>" href="{{HTTP_PATH}}/movement-shipments">
          <img src="{{PUBLIC_PATH}}/assets/front/images/movement-of-shipment.png" alt="image">
          <span class="link_name">Movement of shipments</span>
        </a>
      </li> -->

      <!-- <li class="<?php if($lastSegment=='movement-shipments') { echo "active"; } ?>">
        <a class="<?php if($lastSegment=='movement-shipments') { echo "active"; } ?>" href="{{HTTP_PATH}}/single-gimac">
          <img src="{{PUBLIC_PATH}}/assets/front/images/movement-of-shipment.png" alt="image">
          <span class="link_name">Single Gimac Transaction</span>
        </a>
      </li> -->

      <li class="show-list d-lg-none mobile-profile-fields <?php if($lastSegment=='pending-approvals' || $lastSegment=='transition-history') { echo "active"; } ?>">
        <div class="icon-link">
          <a href="javascript:void(0)">
            <img src="{{PUBLIC_PATH}}/assets/front/images/user-icon.svg" alt="image">
            <span class="link_name">Profile</span>
          </a>
          <i class="fa fa-angle-down arrow" aria-hidden="true"></i>
        </div>
        <ul class="sub-menu">
          <li class='<?php if($lastSegment=='pending-approvals') { echo "active"; } ?>'><a href="https://nimbleappgenie.live/swap-local-v2/create-user">Manage Role</a></li>
        </ul>
      </li>

      <li>
        <div class="profile-details">
          <div class="profile-content">
            <img src="https://www.shutterstock.com/image-vector/user-profile-icon-vector-avatar-600nw-2247726673.jpg" alt="profileImg">
          </div>
          <div class="name-job">
            <div class="profile_name">{{auth()->user()->name}}</div>
            <div class="job">{{$user_type}}</div>
          </div>
          <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#logout">
            <i class="fa fa-sign-out" aria-hidden="true"></i>
          </a>
        </div>
      </li>
    </ul>
</div> 


<div class="home-section">
   <header>
       <div class="container">
        <div class="row">
        <?php if($user_type=="Merchant") {  ?>
          <div class="col logo">
            <div class="logo-details">
              <a href="javascript:void(0)"><i class="fa fa-bars bx bx-menu" aria-hidden="true"></i></a>
              <a href="{{HTTP_PATH}}"><img src="{{PUBLIC_PATH}}/assets/front/images/logo.svg" alt="image"></a></div>
            </div> 
           <?php }else{ ?>
            <div class="col logo">
              <div class="logo-details">
              <a href="javascript:void(0)">
                <i class="fa fa-bars bx bx-menu" aria-hidden="true"></i>
              </a>
              <a href="{{HTTP_PATH}}">
                <img src="{{PUBLIC_PATH}}/assets/front/images/logo.svg" alt="image">         
              </a>
              </div>
     </div>
          
           <?php } ?>
           <div class="col menu">
            <div class="header-menu">
               <div class="notification-icon">
                   <ul>
                        <?php if($user_type=="Merchant") {  ?>
                       <li><a href="notifications"><img src="{{PUBLIC_PATH}}/assets/front/images/notification-icon.png" alt="image"></a></li>
                       <?php } ?>
                       <!-- <li><a href="#"><img src="{{PUBLIC_PATH}}/assets/front/images/helping-icon.png" alt="image"></a></li> -->
                   </ul>
               </div>

             <div class="user-field">
                    <ul>
                        <li>
                          <a href="#">
                            <span><img src="{{PUBLIC_PATH}}/assets/front/images/user-icon.svg" alt="image"></span>
                            <h4>{{auth()->user()->name}}</h4>  
                          </a>
                          <?php if($user_type=="Merchant") {  ?>
                            <span class="dropdown-parent">
                              <a href="{{HTTP_PATH}}/create-user">Manage Role</a>
                              <a href="{{HTTP_PATH}}/change-password">Change Password</a>
                            </span>
                            <?php }else {  ?>
                            <span class="dropdown-parent">
                              <a href="{{HTTP_PATH}}/change-password">Change Password</a>
                            </span>
                          <?php  } ?>    
                        </li>
                    </ul>  
               </div>

               <div class="logout-field">
                   <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#logout" class="btn btn-primaryx">Logout</a>
               </div>
               <div class="header-mobile-toggle d-sm-none">
                   <a href="javascript:void(0)" class="toggle-menu"><i class="fa fa-bars" aria-hidden="true"></i></a>
               </div>
            </div>
           </div>
        </div>
       </div>
   </header>
</div>

<script type="text/javascript">
    $(document).ready(function () {
     let arrow = document.querySelectorAll(".arrow");
      for (var i = 0; i < arrow.length; i++) {
        arrow[i].addEventListener("click", (e)=>{
       let arrowParent = e.target.parentElement.parentElement;//selecting main parent of arrow
       arrowParent.classList.toggle("active");
        });
      }
    });
    $(document).ready(function () {
      let sidebar = document.querySelector(".sidebar");
      let sidebarBtn = document.querySelector(".bx-menu");
      console.log(sidebarBtn);
      sidebarBtn.addEventListener("click", ()=>{
        sidebar.classList.toggle("close");
      });
    });
</script>

<script>
  $(document).ready(function () {
      $(window).on("resize", function (e) {
          checkScreenSize();
      });
      checkScreenSize();
      function checkScreenSize(){
          var newWindowWidth = $(window).width();
          if (newWindowWidth < 991) {
            $('.show-list').click(function(){
              $(".sub-menu").toggleClass('active');
            )};
          }
          else
          {
            $('.show-list').click(function(){
              $(".sub-menu").removeClass('active');
            )};
          }
      }
  });
</script>

<script>
  $(document).ready(function(){
    $(".bx-menu").click(function(){
      $(".black-layer").addClass('active');
      $('body').addClass('active');
      $('html').addClass('active');
    });
    $(".black-layer").click(function(){
      $(".sidebar").removeClass('close');
      $('body').removeClass('active');
      $('html').removeClass('active');
    });
  });
</script>
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

<div class="home-section">

<?php if (session()->has('success_message')) { ?>
  <div class="alert alert-success" role="alert">
  {{Session::get('success_message')}}
  </div>
  <?php Session::forget('success_message'); } ?>

  <?php if (session()->has('error_message')) { ?>
<div class="alert alert-danger" role="alert">
{{Session::get('error_message')}}
</div>
<?php Session::forget('error_message');   } ?>  

@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@yield('content') 
 </div>
 
<!-- Modal -->
<div class="modal fade" id="logout" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Logout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to logout?</p>
      </div>
      <div class="modal-footer">
        <div class="logout-btn">
            <a href="javascript:void(0)" class="btn btn-primaryx default" type="button" data-bs-dismiss="modal">Cancel</a> 
            <a href="{{HTTP_PATH}}/logout" class="btn btn-primaryx">Logout</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="rejectRequest" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Are you sure you want to reject this request? </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      {{ Form::open(array('method' => 'post', 'id' => 'rejectform', 'class' => 'form form-signin')) }}

      <div class="single-modal-content">
      <div class="modal-body">
      <div class="form-group">
      <label>Reason</label>
      <div class="custom-date-box">
      <textarea class="form-control" name="remarks" required></textarea>
      </div>
      </div>
      </div>

      </div>
     
      <div class="modal-footer">
        <div class="logout-btn">
            <a href="javascript:void(0)" class="btn btn-primaryx default" type="button" data-bs-dismiss="modal">Cancel</a> 
            <button type="submit" class="btn btn-primaryx">Reject</button>
        </div>
      </div>
      {{ Form::close()}}

    </div>
  </div>
</div>

</body>
</html>