{{ HTML::script('public/assets/js/jquery-2.1.0.min.js')}}
@if($status=='1')
<script>
    $(document).ready(function () {
        $('#apiverify_<?php echo $id;?>').html('Activated');
        $('#api_key_<?php echo $id;?>').html('{{$apikey}}');
    });
</script>
@else
<script>
    $(document).ready(function () {
        $('#apiverify_<?php echo $id;?>').html('Deactivated');
    });
</script>
@endif


@if($status=='1')
    <a href="{{ URL::to($action)}}" title="API Key Deactivate" class="apideactivate"><i class="fa fa-check"></i>Deactivate API Key</a>
@else
    <a href="{{ URL::to($action)}}" title="API Key Activate" class="apiactivate"><i class="fa fa-ban"></i>Activate API Key</a>
@endif