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
            <div class="topn_left">Drivers List</div>
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
                        <th class="sorting_paging">@sortablelink('name', 'Name')</th>
                        <th class="sorting_paging">@sortablelink('companyname', 'Company Name')</th>
                        <th class="sorting_paging">@sortablelink('email', 'Email Address')</th>
                        <th class="sorting_paging">@sortablelink('phone', 'Phone')</th>
                        <th class="sorting_paging">@sortablelink('status', 'Status')</th>
                        <th class="sorting_paging">@sortablelink('created_at', 'Date')</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allrecords as $allrecord)
                    <tr>
                        <th style="width:5%"><input type="checkbox" onclick="javascript:isAllSelect(this.form);"
                                name="chkRecordId[]" value="{{$allrecord->id}}" /></th>
                        <td data-title="User Name">{{$allrecord->name}}</td>
                        <td data-title="User Name">{{$allrecord->companyName ?? ""}}</td>
                        <td data-title="Email Address">{{$allrecord->email?$allrecord->email:'N/A'}}</td>
                        <td data-title="Email Address">{{$allrecord->phone?$allrecord->phone:'N/A'}}</td>
                        <td data-title="Status" id="verify_{{$allrecord->id}}">
                            @if($allrecord->status == 1)
                            Activated
                            @elseif($allrecord->status == 2)
                            Deleted
                            @else
                            Deactivated
                            @endif

                        </td>
                        <td data-title="Date">{{$allrecord->created_at->format('M d, Y h:i A')}}</td>
                        <td data-title="Action">
                            <div id="loderstatus{{$allrecord->id}}" class="right_action_lo">
                                {{HTML::image("public/img/loading.gif", '')}}</div>


                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                    @php
                                    $roles = AdminsController::getRoles(Session::get('adminid'));
                                    @endphp


                                    <?php $permissions = DB::table('permissions')->where('role_id', $roles)->pluck('permission_name')->toArray(); ?>
                                    @if(in_array('view-activation-card',$permissions))
                                    <li><a href="{{ URL::to( 'admin/drivers/view-activation-card/'.$allrecord->id)}}"
                                            title="View Activation Card" class=""><i class="fa fa-eye"></i>View Activation Card</a></li>
                                    @endif
                                    <li class="right_acdc" id="status{{$allrecord->id}}">

                                        @if($allrecord->status == '1')
                                        <a href="{{ URL::to( 'admin/driver/deactivate/'.$allrecord->id)}}"
                                            title="Deactivate" class="deactivate"><i
                                                class="fa fa-check"></i>Deactivate</a>
                                        @else
                                        <a href="{{ URL::to( 'admin/driver/activate/'.$allrecord->id)}}"
                                            title="Activate" class="activate"><i class="fa fa-ban"></i>Activate</a>
                                        @endif
                                    </li>
                                    
                                    @if(in_array('edit-driver',$permissions))
                                    <li><a href="{{ URL::to( 'admin/drivers/edit-driver/'.$allrecord->id)}}"
                                            title="Edit" class=""><i class="fa fa-pencil"></i>Edit Driver</a></li>
                                    @endif
                                    @if(in_array('delete-driver',$permissions))
                                    <li><a href="{{ URL::to( 'admin/drivers/delete-driver/'.$allrecord->id)}}"
                                            title="Delete" class=""
                                            onclick="return confirm('Are you sure you want to delete this record?')"><i
                                                class="fa fa-trash-o"></i>Delete</a></li>
                                    @endif

                                    <!-- <li><a href="#info{!! $allrecord->id !!}" title="View Driver Detail" class="" rel='facebox'><i class="fa fa-eye"></i>View Driver Detail</a></li> -->
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="search_frm">
                <button type="button" name="chkRecordId" onclick="checkAll(true);" class="btn btn-info">Select
                    All</button>
                <button type="button" name="chkRecordId" onclick="checkAll(false);" class="btn btn-info">Unselect
                    All</button>
                <?php
                $accountStatus = array(
                    'Activate' => "Activate",
                    'Deactivate' => "Deactivate",
                    'Delete' => "Delete",
                );
                ;
                ?>
                <div class="list_sel">{{Form::select('action', $accountStatus,null, ['class' => 'small
                    form-control','placeholder' => 'Action for selected record', 'id' => 'action'])}}</div>
                <button type="submit" class="small btn btn-success btn-cons btn-info"
                    onclick="return ajaxActionFunction();" id="submit_action">OK</button>
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
            <legend class="head_pop">Driver Details</legend>
            <div class="drt">
                <div class="admin_pop"><span>Name: </span> <label>{!! $allrecord->name !!}</label></div>
                <div class="admin_pop"><span>Email Address: </span>
                    <label>{{$allrecord->email?$allrecord->email:'N/A'}}</label></div>
                <div class="admin_pop"><span>Phone: </span> <label>{{$allrecord->phone?$allrecord->email:'N/A'}}</label>
                </div>
        </fieldset>
    </div>
</div>
@endforeach
@endif