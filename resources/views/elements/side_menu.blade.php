<ul class="nav nav-pills flex-column" id="myTab" role="tablist">
    <li class="nav-item">
        <a class="nav-link @if(isset($actdashboard)){{'active'}}@endif" href="{{URL::to('users/dashboard')}}">Dashboard</a>
    </li>
    <?php $userHInfo = DB::table('users')->where('id', session()->get('user_id'))->first(); ?>
    <?php if ($userHInfo->user_type == 'Radio and Tv Station') { ?>
        <li class="nav-item">
            <a class="nav-link @if(isset($actshow)){{'active'}}@endif" href="{{URL::to('shows')}}">Shows</a>
        </li>
        <li class="nav-item">
            <a class="nav-link @if(isset($actbooking)){{'active'}}@endif" href="#" >Booking</a>
        </li>
    <?php } ?>

    <li class="nav-item">
        <a class="nav-link" href="{{URL::to('logout')}}">Logout</a>
    </li>

</ul>