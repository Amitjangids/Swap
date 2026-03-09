{{ HTML::script('public/assets/js/facebox.js')}}
{{ HTML::style('public/assets/css/facebox.css')}}
<script type="text/javascript">
    $(document).ready(function ($) {
        $('.close_image').hide();
        $('a[rel*=facebox]').facebox({
            closeImage: '{!! HTTP_PATH !!}/public/img/close.png'
        });
    });
</script>
<div class="admin_loader" id="loaderID">{{HTML::image("public/img/website_load.svg", SITE_TITLE)}}</div>
@if(!$allrecords->isEmpty())
    <div class="panel-body marginzero">
        <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
        {{ Form::open(array('method' => 'post', 'id' => 'actionFrom')) }}
            <section id="no-more-tables" class="lstng-section">
                <div class="topn">
                    <div class="topn_left">Inquiries List</div>
                    <div class="topn_rightd ddpagingshorting" id="pagingLinks" align="right">
                        <div class="panel-heading" style="align-items:center;">
                            {{$allrecords->appends(Input::except('_token'))->render()}}
                        </div>
                    </div>                
                </div>
                <div class="tbl-resp-listing">
                <table class="table table-bordered table-striped table-condensed cf">
                    <thead class="cf ddpagingshorting">
                        <tr>
                            <th class="sorting_paging">@sortablelink('User.name', 'Username')</th>
                            <th class="sorting_paging">@sortablelink('User.phone', 'Phone')</th>
                            <th class="sorting_paging">@sortablelink('email', 'Email')</th>
                            <th class="sorting_paging">@sortablelink('subject', 'Subject')</th>
                            <th class="sorting_paging">@sortablelink('message', 'Message')</th>
                            <th class="sorting_paging">@sortablelink('created_at', 'Date')</th>
                            <th class="action_dvv"> Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allrecords as $allrecord)
                            <tr>
                                <td data-title="Username">{{$allrecord->User?$allrecord->User->name:''}}</td>
                                <td data-title="Phone">{{$allrecord->User?$allrecord->User->phone:''}}</td>
                                <td data-title="Email">{{$allrecord->email}}</td>
                                <td data-title="Subject">{{$allrecord->subject}}</td>
                                <td data-title="Message">{!! $allrecord->message !!}</td>
                                <td data-title="Date">{{$allrecord->created_at->format('M d, Y')}}</td>
                                <td data-title="Action">
                                    <a href="#info{!! $allrecord->id !!}" title="View" class="btn btn-primary btn-xs" rel='facebox'><i class="fa fa-eye"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        {{ Form::close()}}
        </div>         
    </div>
@else 
    <div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
    <div class="admin_no_record">No record found.</div>
@endif


@if(!$allrecords->isEmpty())
    @foreach($allrecords as $allrecord)
        <div id="info{!! $allrecord->id !!}" style="display: none;">
            <div class="nzwh-wrapper">
                <fieldset class="nzwh">
                     <legend class="head_pop">{!! $allrecord->subject !!}</legend>
                    <div class="drt">
                        <div class="admin_pop">{!! $allrecord->message !!}</div>  
                    </div>
                </fieldset>
            </div>
        </div>
    @endforeach
@endif