@if(!$allrecords->isEmpty()) 
@forelse($allrecords as $allrecord)
<div class="col-sm-4">
    <div class="agent-box">
        <a href="{{ URL::to( 'shop-payment-by-detail/'.$allrecord->slug)}}" style="text-decoration: none; color: #000;">
        <div class="agent-detail-small">
            <div class="agent-pic">
                @if(isset($allrecord->profile_image) && !empty($allrecord->profile_image))
                {{HTML::image(PROFILE_SMALL_DISPLAY_PATH.$allrecord->profile_image, SITE_TITLE, ['id'=> ''])}}
                @else
                {{HTML::image('public/img/no_user.png', SITE_TITLE, ['id'=> ''])}}
                @endif
            </div>
            <div class="agent-namebox">
                <h5>{{$allrecord->business_name}}</h5>
                <h6>{{$allrecord->phone}}</h6>
            </div>
        </div>
        </a>
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
