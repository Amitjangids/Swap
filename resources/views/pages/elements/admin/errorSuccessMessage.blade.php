@if(count($errors) > 0 || Session::has('error_message') || isset($error_message))
    <div class="alert alert-block alert-danger fade in">
        <button data-dismiss="alert" class="close close-sm" type="button">
            <i class="fa fa-times"></i>
        </button>
        @if(isset($error_message)) {{$error_message}} @endif
        @foreach($errors->all() as $error)
             <?php $errorArr = explode('\n', $error);
             if(count($errorArr) > 1){
                 for($i=0;$i<count($errorArr);$i++){
                     echo $errorArr[$i].'<br>';
                 }
             } else{
                 echo $error.'<br/>';
             }
             ?>
        @endforeach 
        @if(Session::has('error_message')) {{Session::get('error_message')}} @endif
    </div>
@endif

@if(Session::has('success_message')) 
    <div class="alert alert-success fade in">
        <button data-dismiss="alert" class="close close-sm" type="button"><i class="fa fa-times"></i></button>
        {{Session::get('success_message')}} 
        {{Session::forget('success_message')}}
    </div>
@endif