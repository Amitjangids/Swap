
{{ HTML::script('public/assets/js/facebox.js')}}
{{ HTML::style('public/assets/css/facebox.css')}}
@php
use App\Http\Controllers\Admin\AdminsController;
@endphp
@php
use App\Permission;
@endphp
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
            <div class="topn_left">Card Content List</div>
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
                        <th class="sorting_paging">@sortablelink('cardType', 'Card Type')</th>
                        <th class="sorting_paging">@sortablelink('title', 'Title')</th>
                        <!-- <th class="sorting_paging">@sortablelink('status', 'Status')</th> -->
                        <th class="sorting_paging">@sortablelink('created_at', 'Date')</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>                    
                    @foreach($allrecords as $key => $allrecord)
                    <tr>
                        <th>{{$key+1}}</th>
                        <td data-title="User Type">{{$allrecord->cardType}}</td>
                        <td data-title="Title">{{$allrecord->title}}</td>
                        <!-- <td data-title="Status" id="status_{{$allrecord->slug}}">
                            @if($allrecord->status == 0)
                            Activated
                            @else
                            Deactivated
                            @endif
                        </td> -->
                        <td data-title="Date">{{$allrecord->created_at->format('M d, Y h:i A')}}</td>
                        <td data-title="Action">
                            <div id="loderstatus{{$allrecord->id}}" class="right_action_lo">{{HTML::image("public/img/loading.gif", '')}}</div>                            

                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                    <!-- <li class="right_acdc" id="status{{$allrecord->id}}">
                                        @if($allrecord->status == '1')
                                        <a href="{{ URL::to( 'admin/cardcontents/deactivate/'.$allrecord->id)}}" title="Deactivate" class="deactivate"><i class="fa fa-check"></i>Deactivate Card Content</a>
                                        @else
                                        <a href="{{ URL::to( 'admin/cardcontents/activate/'.$allrecord->id)}}" title="Activate" class="activate"><i class="fa fa-ban"></i>Activate Card Content</a>
                                        @endif
                                    </li> -->
                                    @php
                                        $roles = AdminsController::getRoles(Session::get('adminid'));   
                                    @endphp
                                
                            
                                    <?php $permissions = DB::table('permissions')->where('role_id',$roles)->pluck('permission_name')->toArray();?>
                                    @if(in_array('edit-card-content',$permissions))
                                    <li><a href="{{ URL::to( 'admin/cardcontents/edit-card-content/'.$allrecord->id)}}" title="Edit" class=""><i class="fa fa-pencil"></i>Edit</a></li>
                                    @endif
                                    <!-- @if(in_array('delete-card-content',$permissions))
                                    <li><a href="{{ URL::to( 'admin/cardcontents/delete-card-content/'.$allrecord->id)}}" title="Delete" class="" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash-o"></i>Delete</a></li>
                                    @endif -->

                                    <!-- <li><a href="#info{!! $allrecord->id !!}" title="View Card Content Detail" class="" rel='facebox'><i class="fa fa-eye"></i>View Card Detail</a></li> -->
                                </ul>
                            </div>
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
            <legend class="head_pop">Card Content Details</legend>
            <div class="drt">
                <div class="admin_pop"><span>Title: </span>  <label>{!! $allrecord->title !!}</label></div>

        </fieldset>
    </div>
</div>
@endforeach
@endif