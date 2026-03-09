@if(!$requests->isEmpty()) 
@forelse($requests as $allrecord)
<div class="col-sm-4">
    <div class="agent-box">
        <div class="agent-detail-small">
            <a class="get-direction">{{__('message.Amount')}} - {{CURR}} {{$allrecord->amount}} </a>
            <div class="agent-pic">
                @if(isset($allrecord->User->profile_image) && !empty($allrecord->User->profile_image))
                            {{HTML::image(PROFILE_SMALL_DISPLAY_PATH.$allrecord->User->profile_image, SITE_TITLE, ['id'=> ''])}}
                            @else
                            {{HTML::image('public/img/no_user.png', SITE_TITLE, ['id'=> ''])}}
                            @endif
            </div>
            <div class="agent-namebox">
                <h5>{{$allrecord->User->name}}</h5>
                <h6>{{$allrecord->User->phone}}</h6>
            </div>
            <div class="requst_btn">
                <a href="javascript:void(0);" title="Reject Request" onclick="selectRequest('{{$allrecord->id}}', 'Reject','{{$allrecord->amount}}')"><span>{{__('message.Reject')}}</span></a>
                <a href="javascript:void(0);" title="Accept Request" onclick="selectRequest('{{$allrecord->id}}', 'Accept','{{$allrecord->amount}}')"><span>{{__('message.Accept')}}</span></a>
            </div>
        </div>
    </div>
</div>
@empty
<div class="col-sm-12"><div class="no_record">{{__('message.No request found.')}}</div></div>
@endforelse
@if(!$requests->isEmpty() && $requests->lastPage() > 1)
<div class="col-sm-12 head-left">
        <div class="shpagel">Showing records {{$requests->perPage()}} from total {{$requests->total()}} </div>
        <div class="topn_rightd ajaxpagee ddpagingshorting" id="pagingLinks" align="right" style="margin-left: auto !important;">
            <div class="panel-heading" style="align-items:center;">
                {{$requests->appends(Input::except('_token'))->render()}}
            </div>
        </div>
</div>
@endif
@else
<div class="col-sm-12"><div class="no_record">{{__('message.No request found.')}}</div></div>
@endif
