@extends('layouts.inner')
@section('content')
<script type="text/javascript">
    function buyCard(cardId, message, card_value) {
        $('#cardId').val(cardId);
        $('#cardValue').val(card_value);
        $('#pop_message').html(message);
        $('#myModal').modal('show');
    }

    function submitCard() {
        $('.btnConff').prop('disabled', true);
        $.ajax({
            type: 'POST',
            url: "<?php echo HTTP_PATH; ?>/buy-cash-card",
            data: $('#userform').serialize(),
            cache: false,
            beforeSend: function () {
                $('#loaderID').show();
            },
            success: function (data) {
                $("#myModal").modal('hide');
                var obj = jQuery.parseJSON(data);
                is_error = obj.status;
                err_html = obj.reason;

                if (is_error == 'Error') {
//                    jQuery('.er_msg').append('<div class="alert alert-block alert-danger"><button data-dismiss="alert" class="close close-sm" type="button"><i class="fa fa-times"></i></button>' + err_html + '</div>');
                    jQuery('#error_message').html(err_html);

                    $('#errorModal').modal('show');
                } else {
                    jQuery('#success_message').html(err_html);
                    $('#successModal').modal('show');
                }

            }
        });
    }

    function complete() {
        window.location.href = "<?php echo HTTP_PATH; ?>/users/dashboard";
    }
    
    function copyToClipboard(element) {
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val($(element).text()).select();
        document.execCommand("copy");
        $temp.remove();
        alert("{{__('message.copied to Clipboard')}}: " + $(element).text());
    }
</script>
<div class="page-heading">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>
                    {{__('message.Select Card')}}
                </h2>
            </div>
        </div>
    </div>
</div>
<div class="container">
    <div class="row">
        <div class="col-sm-10 m-auto">
            <div class="main-option-thumb-box">
                <div class="row justify-content-center ">
                    <div class="recharge-comp-card">
                        <a href="javascript:void(0);">
                            {{HTML::image('public/img/front/SatPayBusiness.svg', SITE_TITLE, ['width'=> '179px'])}}
                        </a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <h6 class="sub-title">{{__('message.Select Card')}}</h6>
                    </div>
                    @if($allrecords)
                    @foreach($allrecords as $allrecord)
                    <div class="col-sm-3">
                        <div class="recharge-box">
                            <?php
                            $card_id = $allrecord['card_id'];
                            $card_value = $allrecord['card_value'];
                                $message = __('message.Get cash card of IQD '). number_format($allrecord['real_value'],2) . ' ' . __('message.by paying IQD') . ' ' . number_format($allrecord['card_value'],2);
                                $card_value = $allrecord['card_value'];
                            ?>
                            <a href="javascript:void(0);" onclick="buyCard('{{$card_id}}', '{{$message}}', '{{$card_value}}')">
                                <h4>{{number_format($allrecord['real_value'],2)}}</h4>
                                <small>{{CURR}}</small>
                            </a>
                        </div>
                    </div>
                    @endforeach
                    @else
                    <div class="container mb-40"><div class="col-sm-12"><div class="no_record">{{__('message.No records found.')}}</div></div></div>
                    @endif                    
                </div>
            </div>
        </div>
    </div>
</div>

<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->

        <div class="modal-content">
            {{ Form::open(array('method' => 'post', 'id' => 'userform', 'class' => ' border-form')) }} 
            <div class="pin-box">
                {{HTML::image('public/img/front/credit-card.png', SITE_TITLE)}}
                <br>
                <br>
                <br>
                <h4 id="pop_message">

                </h4>
                <input type="hidden" id="cardId" name="card_id" value="">
                <input type="hidden" id="cardValue" name="card_value" value="">
                <br>
                <br>
            </div>

            <div class="d-flex btns-pop">
                <button type="button" class="close mod-cancel" data-dismiss="modal">{{__('message.Cancel')}}</button>
                <button type="button" class="btn-grad grad-two btn-one btnConff" onclick="submitCard()">{{__('message.Confirm')}}</button>

            </div>
            {{ Form::close()}}
        </div>
    </div>
    <!--  <div class="modal-content">
        
     </div> -->
</div>

<div id="errorModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->

        <div class="modal-content">
            <div class="pin-box">
                <div id="img_dv">{{HTML::image('public/img/front/failed.svg', SITE_TITLE)}}</div>
                <h3 id="error_message">{{__('message.Success!')}}</h3>

            </div>
            <div class="d-flex">
                <button type="button" class="btn-grad grad-two btn-one" onclick="complete()">{{__('message.OK')}}</button>
            </div>
        </div>
    </div>
    <!--  <div class="modal-content">
        
     </div> -->
</div>
<div id="successModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->

        <div class="modal-content">
            <div class="pin-box">
                <div id="img_dv">{{HTML::image('public/img/front/success.png', SITE_TITLE)}}</div>
                <h3 id="success_message">{{__('message.Success!')}}</h3>

            </div>
            <div class="d-flex btns-pop">
                <button type="button" class="close mod-cancel" onclick="copyToClipboard('#copy_txt')">{{__('message.Copy')}}</button>
                <button type="button" class="btn-grad grad-two btn-one" onclick="complete()">{{__('message.Done')}}</button>
            </div>
        </div>
    </div>
    <!--  <div class="modal-content">
        
     </div> -->
</div>

@endsection