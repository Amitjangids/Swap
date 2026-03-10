<footer class="footer-small">
    <div class="footer-btm">
        <div class="container">
            <div class="row">
                <div class="col-sm-6 ml-auto">
                    <div class="copyrt">
                        <p> © {!! date('Y') !!} M3dja</p>
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


