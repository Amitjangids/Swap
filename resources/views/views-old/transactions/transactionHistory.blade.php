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
{{ Form::open(array('method' => 'post', 'id' => 'searchform')) }}
<div id="loadLists">
    @include('elements.transactions.transactionHistory')

</div>
<input type="hidden" value="1" id="pageidd" name="page"> 
{{ Form::close()}}

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