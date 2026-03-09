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
    });
</script>

@php
                        $roles = AdminsController::getRoles(Session::get('adminid'));   
                    @endphp
                
            
                    <?php $permissions = DB::table('permissions')->where('role_id',$roles)->pluck('permission_name')->toArray();?>
                     
<div class="admin_loader" id="loaderID">{{HTML::image("public/img/website_load.svg", '')}}</div>
@if(!$allrecords->isEmpty())
<div class="panel-body marginzero">
    
    {{ Form::open(array('method' => 'post', 'id' => 'actionFrom')) }}
    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="topn_left">Transaction Fees List</div>
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
                        <!--<th style="width:5%">#</th>-->
                                                <!--<th style="width:5%">Trans Id</th>-->
                        <th class="sorting_paging">@sortablelink('transaction_type', 'Transaction Type')</th>
                        <th class="sorting_paging">@sortablelink('user_charge', 'Min Amount')</th>
                        <th class="sorting_paging">@sortablelink('agent_charge', 'Max Amount')</th>
                        <th class="sorting_paging">@sortablelink('merchant_charge', 'Fee Amount')</th>
                        <th class="sorting_paging">@sortablelink('merchant_charge', 'Fee Type')</th>
                        <th class="sorting_paging">@sortablelink('Admin.username', 'Last Updated By')</th>
                        <!--<th class="sorting_paging">@sortablelink('status', 'Status')</th>-->
                        <th class="sorting_paging">@sortablelink('created_at', 'Date')</th>
                        @if(in_array('edit-transactionfees',$permissions))    
                        <th class="action_dvv"> Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
        @foreach($allrecords as $allrecord) 
        <tr>
            <td data-title="Transaction Type">{{ isset($allrecord->transaction_type) ? ucfirst($allrecord->transaction_type) : 'N/A' }}</td>
            <td data-title="User Fee">{{ CURR . (isset($allrecord->min_amount) ? number_format((($allrecord->min_amount - floor($allrecord->min_amount)) > 0.5 ? ceil($allrecord->min_amount) : floor($allrecord->min_amount)), 0, '', ' ') ?? 0 : 'N/A') }}</td>
            <td data-title="Agent Fee">{{ CURR . (isset($allrecord->max_amount) ? number_format((($allrecord->max_amount - floor($allrecord->max_amount)) > 0.5 ? ceil($allrecord->max_amount) : floor($allrecord->max_amount)), 0, '', ' ') ?? 0 : 'N/A') }}</td>

            <td data-title="Merchant Fee">{{ isset($allrecord->fee_amount) ? $allrecord->fee_amount : 'N/A' }}</td>
            <td data-title="Status">
                @if($allrecord->fee_type == 0)
                    Percentage
                @else
                    Flat Rate
                @endif
            </td>
            <td data-title="Last Updated By">
                {{ $allrecord->admin->username }} - {{ $allrecord->admin->id != 1 ? 'Subadmin' : 'Admin' }}
            </td>
            <td data-title="Date">
                @if ($allrecord->created_at)
                    {{ $allrecord->created_at->format('M d, Y') }}
                @else
                    No date available
                @endif
            </td>
            @if(in_array('edit-transactionfees',$permissions))   
            <td data-title="Action">
                <div class="btn-group">
                    <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fa fa-list"></i>
                        <span class="caret"></span>
                    </button>
                         
                    <ul class="dropdown-menu pull-right">
                  
                        <li><a href="{{ URL::to('admin/transactionfees/edit-transactionfees/'.$allrecord->slug) }}" title="Edit Transaction Fee"><i class="fa fa-pencil"></i> Edit Transaction Fee</a></li>
                       
                        <!-- <li><a href="{{ URL::to('admin/transactionfees/delete/'.$allrecord->id) }}" title="Delete" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash-o"></i> Delete</a></li> -->
                    </ul>  
                </div>
            </td>
            @endif
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

