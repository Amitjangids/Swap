
<?php if(Session::has('user_id')){ ?> 
<div class="side_menu">
    <div class="logo">
        <a href="{{ URL::to( 'users/dashboard')}}">{{HTML::image(LOGO_PATH, SITE_TITLE)}}</a>
    </div>
    <div class="">

        <ul class="list_load">
            <li class="list_item"><a href="{{ URL::to( 'users/dashboard')}}">{{HTML::image('public/img/front/home.png', SITE_TITLE)}} {{__('message.Home')}} </a></li>
            <li class="list_item"><a href="{{ URL::to( 'settings')}}">{{HTML::image('public/img/front/settings.png', SITE_TITLE)}} {{__('message.Settings')}} </a></li>
            <li class="list_item"><a href="{{ URL::to( 'notifications')}}">{{HTML::image('public/img/front/notification.png', SITE_TITLE)}} {{__('message.Notification')}} </a></li>
            <li class="list_item"><a href="{{ URL::to( 'help')}}">{{HTML::image('public/img/front/help.png', SITE_TITLE)}} {{__('message.Help')}} </a></li>
            <li class="list_item"><a href="{{ URL::to( 'users/nearByAgent')}}">{{HTML::image('public/img/front/nearest-agent.png', SITE_TITLE)}} {{__('message.Nearest Agents')}} </a></li>
            <li class="list_item"><a href="{{ URL::to( 'users/nearByMerchant')}}">{{HTML::image('public/img/front/nearest-merchant.png', SITE_TITLE)}} {{__('message.Nearest Merchants')}} </a></li>
            <!--<li class="list_item"><a href="{{ URL::to( 'users/nearByReseller')}}">{{HTML::image('public/img/front/nearest-reseller.png', SITE_TITLE)}} Nearest Reseller </a></li>-->
            <li class="list_item"><a href="{{ URL::to( 'about-us')}}">{{HTML::image('public/img/front/about-us.png', SITE_TITLE)}} {{__('message.About us')}} </a></li>
            <li class="list_item"><a href="{{ URL::to( 'logout')}}" onclick="return confirm('<?php echo __('message.Are you sure you want to logout?')?>')">{{HTML::image('public/img/front/log-out.png', SITE_TITLE)}} {{__('message.Log Out')}} </a></li>

        </ul>

    </div>
</div>
<?php } ?>


<section class="header-one head-bg">
    <div class="container">
        <div class="row">
            <div class="col-sm-6 head-left">
                <?php if(Session::has('user_id')){ ?> 
                <div class="burger_box">
                    <div class="menu-icon-container">
                        <a href="javascript:void(0);" class="menu-icon js-menu_toggle closed">
                            <span class="menu-icon_box">
                                <span class="menu-icon_line menu-icon_line--1"></span>
                                <span class="menu-icon_line menu-icon_line--2"></span>
                                <span class="menu-icon_line menu-icon_line--3"></span>
                            </span>
                        </a>
                    </div>
                </div>
            <?php } ?>
                <div class="logo">
                    <a href="{{ URL::to( 'users/dashboard')}}">{{HTML::image(LOGO_PATH, SITE_TITLE)}}</a>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="SelectBox ml-auto">
                    <?php
                    if (!empty(Session::get('locale'))) {
                        $lang = Session::get('locale');
                    } else {
                        $lang = 'ku';
                    }
                    $langList = array('ku' => 'کوردی', 'ar' => 'عربي', 'en' => 'English');
                    ?>
                    {{Form::select('language', $langList,$lang, ['class' => '','onChange' => "changeLanguage(this.value)"])}}
                    <div class="chevron">
                        {{HTML::image('public/img/front/drop-arrow.svg', SITE_TITLE)}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
    function changeLanguage(val) {
        $.ajax({
            url: "{!! HTTP_PATH !!}/lang/" + val,
            type: "GET",
            success: function (result) {
                location.reload();
            }

        });
    }
</script>

