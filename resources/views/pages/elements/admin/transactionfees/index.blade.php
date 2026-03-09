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
<div class="admin_loader" id="loaderID">{{HTML::image("public/img/website_load.svg", '')}}</div>
@if(!$allrecords->isEmpty())
<div class="panel-body marginzero">
    <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
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
                        <th class="sorting_paging">@sortablelink('user_charge', 'User Fee')</th>
                        <th class="sorting_paging">@sortablelink('agent_charge', 'Agent Fee')</th>
                        <th class="sorting_paging">@sortablelink('merchant_charge', 'Merchant Fee')</th>
                        <th class="sorting_paging">@sortablelink('Admin.username', 'Last Updated By')</th>
                        <!--<th class="sorting_paging">@sortablelink('status', 'Status')</th>-->
                        <th class="sorting_paging">@sortablelink('created_at', 'Date')</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allrecords as $allrecord) 
                    <tr>
                        <!--<th style="width:5%"><input type="checkbox" onclick="javascript:isAllSelect(this.form);" name="chkRecordId[]" value="{{$allrecord->id}}" /></th>-->
                                                <!--<td data-title="Transactionfee Id">{{$allrecord->id}}</td>-->
                        <td data-title="Transaction Type">{{isset($allrecord->transaction_type) ? ucfirst($allrecord->transaction_type):'N/A'}}</td>
                        <td data-title="User Fee">{{isset($allrecord->user_charge) ? ucfirst($allrecord->user_charge).'%':'0'}}</td>
                        <td data-title="Agent Fee">{{isset($allrecord->agent_charge) ? ucfirst($allrecord->agent_charge).'%':'0'}}</td>
                        <td data-title="Merchant Fee">{{isset($allrecord->merchant_charge) ? ucfirst($allrecord->merchant_charge).'%':'0'}}</td>
                        
<!--                        <td data-title="Status">
                            @if($allrecord->status == '1')
                                Activated
                            @else
                            Deactivated
                            @endif
                        </td>-->
                        <td data-title="Last Updated By">
                            {{$allrecord->Admin->username}} - {{$allrecord->Admin->id != 1?'Subadmin':'Admin'}}
                        </td>
                        <td data-title="Date">{{$allrecord->created_at->format('M d, Y h:i:s A')}}</td>
                        <td data-title="Action">
                            
                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                    <li><a href="{{ URL::to( 'admin/transactionfees/edit/'.$allrecord->slug)}}" title="Edit Transaction Fee" class=""><i class="fa fa-pencil"></i>Edit Transaction Fee</a></li>
                                    <!--<li><a href="{{ URL::to( 'admin/transactionfees/delete/'.$allrecord->id)}}" title="Delete" class="" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash-o"></i>Delete</a></li>-->

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

