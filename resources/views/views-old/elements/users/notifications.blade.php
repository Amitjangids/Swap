@if(!$allrecords->isEmpty()) 
@forelse($allrecords as $allrecord)
<div class="container">
    <div class="row">
        <div class="col-sm-12" id="noti_sec{{$allrecord->id}}">
            <div class="notification-box" onclick="changeStatus('{{$allrecord->id}}','{{$allrecord->is_seen}}');">
                <div class="col-sm-8 d-flex">
                    @if($allrecord->is_seen == 1)
                    {{HTML::image('public/img/front/envelope-open.png', SITE_TITLE)}}
                    @else
                    {{HTML::image('public/img/front/envelope-close.png', SITE_TITLE)}}
                    @endif
                    <p>
                        @if($allrecord->is_seen == 1)
                        {{$allrecord->notif_body}}
                        @else
                        <strong>{{$allrecord->notif_body}}</strong>
                        @endif
                    </p>
                </div>
                <div class="col-sm-4 text-right">
                    <span class="noti-date">{{$allrecord->created_at->format('d M Y')}} <br>
                        {{$allrecord->created_at->format('h:i A')}}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@empty
<div class="container mb-40"><div class="col-sm-12"><div class="no_record">No records found.</div></div></div>
@endforelse
@if(!$allrecords->isEmpty() && $allrecords->lastPage() > 1)
<div class="col-sm-12 head-left">
    <div class="shpagel">Showing records {{$allrecords->perPage()}} from total {{$allrecords->total()}} </div>
    <div class="topn_rightd ajaxpagee ddpagingshorting" id="pagingLinks" align="right" style="margin-left: auto !important;">
        <div class="panel-heading" style="align-items:center;">
            {{$allrecords->appends(Input::except('_token'))->render()}}
        </div>
    </div>
</div>
@endif
@else
<div class="container mb-40"><div class="col-sm-12"><div class="no_record">No records found.</div></div></div>
@endif
