<title>{{$title.TITLE_FOR_LAYOUT}}</title>
<div class="active_vb">
    <div class="wrapper">
        <div class="modalv2 mpre"> 
            <div class="activate-popup alum-popup pricing">
                <div class="content">
                    <div class="paypal_process_t" style="text-align:center; padding-top:80px">
                        <div class="" >
                            <h1>Please wait and check app for notification for payment...</h1>
                            <div>Please do not refresh or click on back browser button</div>
                            <div class="loder_img cerntekej">
                                <span class="loading_img"></span>
                                <br/>
                                {{HTML::image(LOGO_PATH, SITE_TITLE)}}
                                <br/>
                                <br/>
                                {{HTML::image('public/img/website_load.svg', SITE_TITLE)}}
                                <div id="progressBar">
                                    <div></div>
                                    <span class="time_left"></span>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="invite-now"></div>
            </div>        
        </div>
    </div>
</div>   

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<style>
    #progressBar {
        width: 90%;
        margin: 10px auto;
        height: 22px;
        background-color: #fff;
    }

    #progressBar div {
        height: 100%;
        text-align: left;
        /*padding: 0 10px;*/
        line-height: 22px; /* same as #progressBar height if we want text middle aligned */
        width: 0;
        background-color: #d0a547;
        box-sizing: border-box;
    }
</style>
<script type="text/javascript" language="javascript">
function show_div() {
    $('.loader_img').show();
}
$(function () {
    setTimeout(function () {
        alert('Timeout');
    }, 181000);
});

$(function () {
    setInterval(function () {
        checkStatus();
    }, 1000);
});

progress(180, 180, $('#progressBar'));

function progress(timeleft, timetotal, $element) {
    var progressBarWidth = $element.width() - timeleft * $element.width() / timetotal;
    $element.find('div').animate({width: progressBarWidth}, 500);
    $element.find('span').animate(500).html(timeleft + ' seconds to go');
    if (timeleft > 0) {
        setTimeout(function () {
            progress(timeleft - 1, timetotal, $element);
        }, 1000);
    }
}

function checkStatus() {
    $.ajax({
        type: 'POST',
        url: "<?php echo HTTP_PATH; ?>/checkOrderStatus",
        data: {'id':'{{$orderId}}', _token: '{{csrf_token()}}'},
        cache: false,
        success: function (result) {
            if(result == 1){
            } else{
            }
        }
    });
}


</script>