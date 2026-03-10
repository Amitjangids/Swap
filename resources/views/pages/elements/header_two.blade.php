<section class="header-one head-bg">
    <div class="container">
        <div class="row">
            <div class="col-sm-6">
                <div class="logo">
                    <a href="javascript:void(0);">{{HTML::image(LOGO_PATH, SITE_TITLE)}}</a>
                </div>
            </div>
<!--            <div class="col-sm-6">
                <div class="SelectBox ml-auto">
                    <?php 
                    if(!empty(Session::get('locale'))){
                        $lang = Session::get('locale');
                    } else{
                        $lang = 'ku';
                    }
                    $langList = array('ku'=>'کوردی','ar'=>'عربي','en'=>'English');?>
                    {{Form::select('language', $langList,$lang, ['class' => '','onChange' => "changeLanguage(this.value)"])}}
                    <div class="chevron">
                        {{HTML::image('public/img/front/drop-arrow.svg', SITE_TITLE)}}
                    </div>
                </div>
            </div>-->
        </div>
    </div>
</section>



