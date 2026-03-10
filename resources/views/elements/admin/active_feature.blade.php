{{ HTML::script('public/assets/js/jquery-2.1.0.min.js')}}
@if($status=='1')
<script>
    $(document).ready(function () {
        $('#status_<?php echo $id;?>').html('Activated');
    });
</script>
@else
<script>
    $(document).ready(function () {
        $('#status_<?php echo $id;?>').html('Deactivated');
    });
</script>
@endif


@if($status=='1')
    <a href="{{ URL::to($action)}}" title="Deactivate" class="deactivate"><button class="btn btn-success btn-xs"><i class="fa fa-check"></i></button></a>
@else
<a href="{{ URL::to($action)}}" title="Activate" class="activate"><button class="btn btn-danger btn-xs"><i class="fa fa-ban"></i></button></a>
@endif