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
            <div class="topn_left">Transaction Limit</div>
            <div class="topn_rightd ddpagingshorting" id="pagingLinks" align="right">
                <div class="panel-heading" style="align-items:center;">
                   
                </div>
            </div>                
        </div>
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        <th class="sorting_paging">@sortablelink('id', 'ID')</th>
                        <!-- <th class="sorting_paging">@sortablelink('account_category', 'Membership Name')</th> -->
                        <th class="sorting_paging">@sortablelink('category_for', 'Membership Type')</th>
                        <th class="sorting_paging">@sortablelink('daily_limit', 'Daily Limit')</th>
						<th class="sorting_paging">@sortablelink('week_limit', 'Week Limit')</th>
						<th class="sorting_paging">@sortablelink('month_limit', 'Month Limit')</th>
                        <th class="sorting_paging">@sortablelink('edited_by', 'Edited By')</th>
                        <th class="sorting_paging">@sortablelink('updated_at', 'Date')</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allrecords as $allrecord)
					@php
					$editedBy = getAdminNameById($allrecord->edited_by);
					@endphp
                    <tr>
                        <td data-title="ID">{{$allrecord->id}}</td>
						<!-- <td data-title="Membership Name">{{$allrecord->account_category}}</td> -->
                        <td data-title="Membership Type">
						@if($allrecord->category_for == 1)
						{{'Customer'}}
                        @elseif($allrecord->category_for == 2)
						{{'Merchant'}}
						@else
						{{'Agent'}}
						@endif
						</td>
                        <td data-title="Daily Limit">{{number_format((($allrecord->daily_limit - floor($allrecord->daily_limit)) > 0.5 ? ceil($allrecord->daily_limit) : floor($allrecord->daily_limit)), 0, '', ' ') ?? 0}}</td>
						<td data-title="Week Limit">{{number_format((($allrecord->week_limit - floor($allrecord->week_limit)) > 0.5 ? ceil($allrecord->week_limit) : floor($allrecord->week_limit)), 0, '', ' ') ?? 0}}</td>
                        <td data-title="Month Limit">{{number_format((($allrecord->month_limit - floor($allrecord->month_limit)) > 0.5 ? ceil($allrecord->month_limit) : floor($allrecord->month_limit)), 0, '', ' ') ?? 0}}</td>
                        <td data-title="Last Edited By">{{$editedBy}}</td>
                        <td data-title="Date">{{$allrecord->updated_at->format('M d, Y h:i A')}}</td>
                        <td data-title="Action">
                            <div id="loderstatus{{$allrecord->id}}" class="right_action_lo">{{HTML::image("public/img/loading.gif", '')}}</div>
                            
                            {{ Form::close()}}
                            @php
                                $roles = AdminsController::getRoles(Session::get('adminid'));   
                            @endphp
                        
                    
                            <?php $permissions = DB::table('permissions')->where('role_id',$roles)->pluck('permission_name')->toArray();?>
                            @if(in_array('edit-transaction-limit',$permissions))
                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
								<li><a href="{{URL::to('admin/users/edit-transaction-limit/'.$allrecord->id)}}" title="Edit Membership Limit" class=""><i class="fa fa-edit"></i>Edit Limit</a></li>
                                </ul>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
           <!-- <div class="search_frm">
                <button type="button" name="chkRecordId" onclick="checkAll(true);"  class="btn btn-info">Select All</button>
                <button type="button" name="chkRecordId" onclick="checkAll(false);" class="btn btn-info">Unselect All</button>
                <?php $accountStatus = array(
				'Verify' => "Approve Request"
				); ?>
                <div class="list_sel">{{Form::select('action', $accountStatus,null, ['class' => 'small form-control','placeholder' => 'Action for selected record', 'id' => 'action'])}}</div>
                <button type="submit" class="small btn btn-success btn-cons btn-info" onclick="return ajaxActionFunction();" id="submit_action">OK</button>
            </div> -->    
        </div>
    </section>
    {{ Form::close()}}
</div>         
</div> 
@else 
<div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
<div class="admin_no_record">No record found.</div>
@endif