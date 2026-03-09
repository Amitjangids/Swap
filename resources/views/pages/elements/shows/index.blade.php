@if(!$allrecords->isEmpty()) 
<?php global $serviceDays; ?>
@foreach($allrecords as $allrecord)
<div class="col-sm-6 col-md-3">
    <div class="thumbnail">
        <div class="project-img">
            <?php
            $gigimgname = '';
            if ($allrecord->Image) {
                foreach ($allrecord->Image as $gigimage) {
                    if (isset($gigimage->name) && !empty($gigimage->name)) {
                        $path = GIG_FULL_UPLOAD_PATH . $gigimage->name;
                        if (file_exists($path) && !empty($gigimage->name)) {
                            $gigimgname = GIG_FULL_DISPLAY_PATH . $gigimage['name'];
                            break;
                        }
                    }
                }
            }
            if ($gigimgname == '' && $allrecord->youtube_image) {
                if (file_exists(GIG_FULL_UPLOAD_PATH . $allrecord->youtube_image)) {
                    $gigimgname = GIG_FULL_DISPLAY_PATH . $allrecord->youtube_image;
                }
            }
            if ($gigimgname == '') {
                $gigimgname = 'public/img/front/dummy.png';
            }
            ?>
            <a href="{{ URL::to( 'gig-details/'.$allrecord->slug)}}" class="">{{HTML::image($gigimgname, SITE_TITLE,['title'=>$allrecord->title])}}</a>
        </div>
        <div class="caption">
            <h3><a href="{{ URL::to( 'gig-details/'.$allrecord->slug)}}" class="">{{$allrecord->title}}</a></h3>
            <div class="pro-bottm">
                <div class="pro-bottm-left"><a id="maraction-{{$allrecord->id}}" class="maraction" href="javascript:void(0);" data-toggle="tooltip" data-placement="top" title="Gig Actions">{{HTML::image('public/img/front/ellipsis.png', SITE_TITLE)}}</a></div>
                <div class="pro-bottm-right">
                    <a href="#"><small>Starting at</small>{{CURR.$allrecord->basic_price}}</a>
                </div>
            </div>
        </div>
        <div id="offer-show-{{$allrecord->id}}" class="show-adwanv">
            <ul>
                <li>
                    <a href="{{ URL::to( 'gigs/edit/'.$allrecord->slug)}}" class=""><i class="fa fa-pencil" aria-hidden="true"></i><span>Edit</span></a>
                </li>
                <li>
                    <a href="{{ URL::to( 'gig-details/'.$allrecord->slug)}}" class=""><i class="fa fa-eye" aria-hidden="true"></i><span>View Detail</span></a>
                </li>
                <li>
                    <a href="{{ URL::to( 'gigs/delete/'.$allrecord->slug)}}" class="" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash" aria-hidden="true"></i><span>Delete</span></a>
                </li>

                <li class="advanced-setting">
                    <a href="javascript:void(0);" class="clsstng" id="close-{{$allrecord->id}}"><i class="fa fa-close" aria-hidden="true"></i><span>Close</span></a>
                </li>
            </ul>
        </div>
    </div>
</div>
@endforeach
@if($page == 1)
</div>
</div>
@endif
<script>$('#reqloaddiv').show();</script>
@else
@if($page == 1)
<script>$('#reqloaddiv').hide();</script>
<div class="col-md-12"><div class="management-full">No gigs found. </div></div>
@else
<script>$('#reqloaddiv').hide();</script>
@endif
@endif
