@if(!$allrecords->isEmpty()) 
@forelse($allrecords as $allrecord)
<div class="col-sm-4">
    <div class="agent-box">
        <div class="agent-detail-small">
            <?php 
            $origin = $userInfo->lat.','.$userInfo->lng;
            $destination = $allrecord->lat.','.$allrecord->lng;
            $url = 'https://www.google.com/maps/dir/?api=1&origin='.$origin.'&destination='.$destination; ?>
            <a class="get-direction" target="_blank" href="{{$url}}">{{HTML::image('public/img/front/direction.svg', SITE_TITLE)}} {{__('message.Get Direction')}} </a>
            <div class="agent-pic">
                @if(isset($allrecord->profile_image) && !empty($allrecord->profile_image))
                            {{HTML::image(PROFILE_SMALL_DISPLAY_PATH.$allrecord->profile_image, SITE_TITLE, ['id'=> ''])}}
                            @else
                            {{HTML::image('public/img/no_user.png', SITE_TITLE, ['id'=> ''])}}
                            @endif
            </div>
            <div class="agent-namebox">
                <h5>{{$allrecord->name}}</h5>
                <h6>{{$allrecord->phone}}</h6>
            </div>
            <span class="km">
                <?php 
                $meterTotal = $allrecord->distance*1000;
                
                $km  = floor($meterTotal / 1000);
                $meter  = $meterTotal % 1000;
                if($km > 0){
                    echo $km.' '.__('message.Km');
                } else if($meter > 0) {
                    echo $meter.' '.__('message.Meter');
                } else{
                    echo '0 '.__('message.Km');
                }
                
                ?>
                <!--{{number_format($allrecord->distance,0)}}Km-->
            </span>
        </div>
    </div>
</div>
@empty
<div class="col-sm-12"><div class="no_record">{{__('message.No records found.')}}</div></div>
@endforelse
@if(!$allrecords->isEmpty() && $allrecords->lastPage() > 1)
<div class="col-sm-12 head-left">
        <div class="shpagel">{{__('message.show_records',['pageRecord'=>$allrecords->perPage(),'totalRecord'=>$allrecords->total()])}} </div>
        <div class="topn_rightd ajaxpagee ddpagingshorting" id="pagingLinks" align="right" style="margin-left: auto !important;">
            <div class="panel-heading" style="align-items:center;">
                {{$allrecords->appends(Input::except('_token'))->render()}}
            </div>
        </div>
</div>
@endif
@endif
