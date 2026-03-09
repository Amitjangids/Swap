<footer class="">
    <div class="container">
        <div class="row">
            <div class="col-sm-4">
                <div class="ftr-box">
                    {{HTML::image(LOGO_PATH, SITE_TITLE)}}
                    <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, </p>
                </div>
            </div>
            <div class="col-sm-2 offset-sm-1">
                <div class="ftr-box">
                    <h5>Links</h5>
                    <ul>
                        <li><a href="#">Hire a freelancer</a></li>
                        <li><a href="#">RadioSpot</a></li>
                        <li><a href="#">SpotPlan</a></li>
                        <li><a href="#">TV spot</a></li>
                        <li><a href="#">Website spot</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="ftr-box">
                    <h5>More</h5>
                    <ul>
                        <li><a href="#">Culture</a></li>
                        <li><a href="#">Our Story</a></li>
                        <li><a href="#">Team</a></li>
                        <li><a href="#">Newsroom</a></li>
                        <li><a href="#">Partners</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="ftr-box socialbox">
                    <h5>Social Links</h5>
                    <ul>
                        <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                        <li><a href="#"><i class="fa fa-instagram"></i></a></li>
                        <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                        <li><a href="#"><i class="fa fa-linkedin"></i></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-btm">
        <div class="container">
            <div class="row">
                <div class="col-sm-6">
                    <div class="copyrt">
                        <p><i class="fa fa-copyright"></i> {!! date('Y') !!} M3dja</p>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="f-rtbox">
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Use</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- js -->

{{ HTML::script('public/assets/js/bootstrap.min.js')}}
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/owl.carousel.min.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $("#review-slider").owlCarousel({
            items: 1,
            itemsDesktop: [1199, 1],
            itemsMobile: [600, 1],
            pagination: true,
            autoPlay: true
        });
    });
</script>

