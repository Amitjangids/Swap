<!-- header -->
<header id="header" class="">
    <div class="container">
        <div class="row">
            <nav class="navbar navbar-expand-lg">
                <div class="logo col-sm-2">
                    <a class="navbar-brand" href="{!! HTTP_PATH !!}">{{HTML::image(LOGO_PATH, SITE_TITLE)}}</a>
                </div>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarTogglerDemo01" aria-controls="navbarTogglerDemo01" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon">
                        {{HTML::image('public/img/front/bar.jpg', '')}}
                    </span>
                </button>
                <div class="collapse navbar-collapse col-sm-7" id="navbarTogglerDemo01">
                    <ul class="navbar-nav m-auto mt-2 mt-lg-0">
                        <li class="nav-item active"><a class="nav-link" href="#">Solutions</a></li>
                        <li class="nav-item"> <a class="nav-link" href="#">ManAdger</a></li>
                        <li class="nav-item"> <a class="nav-link" href="#">Resources</a></li>
                        <li class="nav-item"><a class="nav-link nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">About</a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="#">Hire a freelancer</a>
                            </div>
                        </li>
                        @if(session()->has('user_id'))
                        <li class="nav-item in-mob"><a class="nav-link" href="{{URL::to('logout')}}">Login</a></li>
                        @else 
                        <li class="nav-item in-mob"><a class="nav-link" href="{{URL::to('login')}}">Login</a></li>
                        <li class="nav-item in-mob"><a class="nav-link" href="{{URL::to('register')}}">Register</a></li>
                        @endif
                        
                    </ul>
                </div>
                <div class="col-sm-3">
                    <div class="log-rt">
                        @if(session()->has('user_id'))
                        <a href="{{URL::to('users/dashboard')}}">Dashboard / </a>
                        <a href="{{URL::to('logout')}}">Logout</a>
                        @else 
                        <a href="{{URL::to('login')}}">
                            {{HTML::image('public/img/front/login.png', 'login-icon')}}
                            Login /
                        </a>
                        <a href="{{URL::to('register')}}">Register</a>
                        @endif
                    </div>
                </div>
            </nav>
        </div>
    </div>
</header>

