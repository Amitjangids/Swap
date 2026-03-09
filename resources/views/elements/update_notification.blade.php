<div class="notification-box">
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