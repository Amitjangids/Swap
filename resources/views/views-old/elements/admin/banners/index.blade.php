
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
<div class="panel-body marginzero">
    <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
    {{ Form::open(array('method' => 'post', 'id' => 'actionFrom')) }}
    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="topn_left">Banners List</div>
            <div class="topn_rightd ddpagingshorting" id="pagingLinks" align="right">
                <div class="panel-heading" style="align-items:center;">
                    {{$allrecords->appends(Request::except('_token'))->render()}}
                </div>
            </div>                
        </div>
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        <th style="width:5%">#</th>
                        <th class="sorting_paging">@sortablelink('banner_name', 'Banner Name')</th>
                        <th class="sorting_paging">@sortablelink('user_type', 'User Type')</th>
                        <th class="sorting_paging">@sortablelink('category', 'Category')</th>
                        <th class="sorting_paging">Banner Image</th>
                        <th class="sorting_paging">@sortablelink('status', 'Status')</th>
                        <th class="sorting_paging">@sortablelink('created_at', 'Date')</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php global $bannerType; ?>
                    @foreach($allrecords as $allrecord)
                    <tr>
                        <th style="width:5%"><input type="checkbox" onclick="javascript:isAllSelect(this.form);" name="chkRecordId[]" value="{{$allrecord->id}}" /></th>
                        <td data-title="Banner Name">{{$allrecord->banner_name}}</td>
                        <td data-title="User Type">{{$allrecord->user_type}}</td>
                        <td data-title="Category">{{$allrecord->category}}</td>
                        <td data-title="Banner Image">
                            <div class="showeditimage">{{HTML::image(BANNER_FULL_DISPLAY_PATH.$allrecord->banner_image, SITE_TITLE,['style'=>"max-width: 100px"])}}</div>
                        </td>
                        <td data-title="Status" id="status_{{$allrecord->slug}}">
                            @if($allrecord->status == 1)
                            Activated
                            @else
                            Deactivated
                            @endif
                        </td>
                        <td data-title="Date">{{$allrecord->created_at->format('M d, Y h:i A')}}</td>
                        <td data-title="Action">
                            <div id="loderstatus{{$allrecord->id}}" class="right_action_lo">{{HTML::image("public/img/loading.gif", '')}}</div>                            

                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                    <li class="right_acdc" id="status{{$allrecord->id}}">
                                        @if($allrecord->status == '1')
                                        <a href="{{ URL::to( 'admin/banners/deactivate/'.$allrecord->slug)}}" title="Deactivate" class="deactivate"><i class="fa fa-check"></i>Deactivate Banner</a>
                                        @else
                                        <a href="{{ URL::to( 'admin/banners/activate/'.$allrecord->slug)}}" title="Activate" class="activate"><i class="fa fa-ban"></i>Activate Banner</a>
                                        @endif
                                    </li>
                                    <li><a href="{{ URL::to( 'admin/banners/edit/'.$allrecord->slug)}}" title="Edit" class=""><i class="fa fa-pencil"></i>Edit Banner</a></li>
                                    <li><a href="{{ URL::to( 'admin/banners/delete/'.$allrecord->slug)}}" title="Delete" class="" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash-o"></i>Delete</a></li>

                                    <li><a href="#info{!! $allrecord->id !!}" title="View Banner Detail" class="" rel='facebox'><i class="fa fa-eye"></i>View Banner Detail</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="search_frm">
                <button type="button" name="chkRecordId" onclick="checkAll(true);"  class="btn btn-info">Select All</button>
                <button type="button" name="chkRecordId" onclick="checkAll(false);" class="btn btn-info">Unselect All</button>
                <?php
                $accountStatus = array(
                    'Activate' => "Activate Banner",
                    'Deactivate' => "Deactivate Banner",
                    'Delete' => "Delete",
                );
                ;
                ?>
                <div class="list_sel">{{Form::select('action', $accountStatus,null, ['class' => 'small form-control','placeholder' => 'Action for selected record', 'id' => 'action'])}}</div>
                <button type="submit" class="small btn btn-success btn-cons btn-info" onclick="return ajaxActionFunction();" id="submit_action">OK</button>
            </div>    
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
            <legend class="head_pop">{!! $allrecord->banner_name !!}</legend>
            <div class="drt">
                <div class="admin_pop"><span>Banner Name: </span>  <label>{!! $allrecord->banner_name !!}</label></div>
                <div class="admin_pop"><span>Category: </span>  <label>{!! $allrecord->category !!}</label></div>


                @if($allrecord->banner_image != '')
                <div class="admin_pop"><span>Banner Image</span> <label>{{HTML::image(BANNER_FULL_DISPLAY_PATH.$allrecord->banner_image, SITE_TITLE,['style'=>"max-width: 200px"])}}</label></div>
                @endif

        </fieldset>
    </div>
</div>
@endforeach
@endif