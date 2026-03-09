{{ HTML::script('public/assets/js/facebox.js')}}
{{ HTML::style('public/assets/css/facebox.css')}}
<script type="text/javascript">
    $(document).ready(function ($) {
        $('.close_image').hide();
        $('a[rel*=facebox]').facebox({
            closeImage: '{!! HTTP_PATH !!}/public/img/close.png'
        });

        $('.dropdown-menu a').on('click', function (event) {
            $(this).parent().parent().parent().toggleClass('open');
        });
    });


</script>
<div class="admin_loader" id="loaderID">{{HTML::image("public/img/website_load.svg", '')}}</div>
@if(!$allrecords->isEmpty())
<?php //echo '<pre>';print_r($allrecords->from());?>
<?php //echo '<pre>';print_r($allrecords);?>
<?php //echo '<pre>';print_r($allrecords->total());?>
<div class="panel-body marginzero">
    <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
    {{ Form::open(array('method' => 'post', 'id' => 'actionFrom')) }}
    <input type="hidden" name="page" value="{{$page}}">
    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="topn_left">Logged In Users List</div>

            <div class="topn_rightd ddpagingshorting paggng-txt" id="pagingLinks" align="right">
                <div class="topn_righ">
                    Showing {{$allrecords->count()}} of {{ $allrecords->total() }} record(s).
                </div>
                <div class="panel-heading" style="align-items:center;">          

                    {{$allrecords->appends( Request::except('_token'))->render()}}
                </div>
            </div>                
        </div>
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        <!--<th style="width:5%">#</th>-->
                        <th class="sorting_paging">@sortablelink('name', 'Name')</th>
                        <th class="sorting_paging">@sortablelink('email', 'Email Address')</th>
                        <th class="sorting_paging">@sortablelink('phone', 'Phone')</th>
                        <th class="sorting_paging">@sortablelink('user_type', 'User Type')</th>
                        <th class="sorting_paging">@sortablelink('device_type', 'Device')</th>
                        <th class="sorting_paging">@sortablelink('login_time', 'Last Login Date/Time')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allrecords as $allrecord)
                    <tr>
                        <!--<th style="width:5%"><input type="checkbox" onclick="javascript:isAllSelect(this.form);" name="chkRecordId[]" value="{{$allrecord->id}}" /></th>-->
                        <td data-title="Full Name">{{$allrecord->name}}</td>
                        <td data-title="Email Address">{{$allrecord->email?$allrecord->email:'N/A'}}</td>
                        <td data-title="Contact Number">{{$allrecord->phone}}</td>
                        <td data-title="User Type">{{$allrecord->user_type}}</td>
                        <td data-title="Device">{{$allrecord->device_type?$allrecord->device_type:'Web'}}</td>
                        <td data-title="Last Login Date/Time">
                            {{date('M d, Y h:i A',strtotime($allrecord->login_time))}}
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

