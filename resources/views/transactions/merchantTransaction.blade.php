@extends('layouts.inner')
@section('content')
<div class="page-heading">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>
                    {{__('message.Transaction History')}}
                </h2>
            </div>
        </div>
    </div>
</div>
<div class="container">
    {{ Form::open(array('method' => 'post', 'id' => 'searchform')) }}
    <div class="row" id="loadLists">
        @include('elements.transactions.merchantTransaction')
    </div>
    <input type="hidden" value="1" id="pageidd" name="page"> 
    {{ Form::close()}}
</div>

<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->

        <div class="modal-content">
            {{ Form::open(array('method' => 'post', 'id' => 'userform', 'class' => ' border-form')) }} 
            <div class="pin-box">
                {{HTML::image('public/img/front/ic_confirmation.png', SITE_TITLE,['width'=>'90px;'])}}
                <br>
                <br>
                <br>
                <h4 id="pop_message">

                </h4>
                <input type="hidden" id="transId" name="trans_id" value="">
                <br>
                <br>
            </div>

            <div class="d-flex btns-pop">
                <button type="button" class="close mod-cancel" data-dismiss="modal">{{__('message.Cancel')}}</button>
                <button type="button" class="btn-grad grad-two btn-one btnConff" onclick="submitRequest()">{{__('message.Confirm')}}</button>

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
            <div class="d-flex">
                <button type="button" class="btn-grad grad-two btn-one" onclick="complete()">{{__('message.Done')}}</button>
            </div>
        </div>
    </div>
    <!--  <div class="modal-content">
        
     </div> -->
</div>

<script type="text/javascript">
    function selectRequest(transId, value) {

        $.ajax({
            type: 'POST',
            url: "<?php echo HTTP_PATH; ?>/checkRefund",
            data: {'trans_id':transId, _token: '{{csrf_token()}}'},
            cache: false,
            beforeSend: function () {
                $('#loaderID').show();
            },
            success: function (data) {
                
                $('#pop_message').html(data);
                $('#transId').val(transId);
        
        $('#myModal').modal('show');
            }
        });
    }

    function submitRequest() {
    $('.btnConff').prop('disabled', true);
        $.ajax({
            type: 'POST',
            url: "<?php echo HTTP_PATH; ?>/refund-payment",
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
</script>
<script>
    $(document).ready(function () {
        $(document).on('click', '.ajaxpagee a', function () {

            var npage = $(this).html();
            if ($(this).html() == '»') {
                npage = $('.ajaxpagee .active').html() * 1 + 1;
            } else if ($(this).html() == '«') {
                npage = $('.ajaxpagee .active').html() * 1 - 1;
            }
            $('#pageidd').val(npage);
            updateresult();
            return false;
        });
    });

    function updateresult() {
        var thisHref = $(location).attr('href');
        $.ajax({
            url: thisHref,
            type: "POST",
            data: $('#searchform').serialize(),
            beforeSend: function () {
                $("#searchloader").show();
            },
            complete: function () {
                $("#searchloader").hide();
            },
            success: function (result) {
                $('#loadLists').html(result);
            }
        });
    }

    function clearfilter() {
        window.location.href = window.location.protocol;
    }

</script>
@endsection