{{ HTML::script('public/assets/js/jquery-2.1.0.min.js')}}
@if($status=='1')
<script>
    $(document).ready(function () {
        $('#verify_<?php echo $id;?>').html('Activated');
    });
</script>
@else
<script>
    $(document).ready(function () {
        $('#verify_<?php echo $id;?>').html('Deactivated');
    });
</script>
@endif


@if($status=='1')
    <a href="{{ URL::to($action)}}" title="Deactivate" class="deactivate"><i class="fa fa-check"></i>Deactivate</a>
@else
    <a href="{{ URL::to($action)}}" title="Activate" class="activate"><i class="fa fa-ban"></i>Activate</a>
@endif