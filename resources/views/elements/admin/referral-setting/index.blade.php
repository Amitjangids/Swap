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
            <div class="topn_left">Referral Fees List</div>
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
                        <th class="sorting_paging">@sortablelink('agent_charge', 'Fee Value (%)')</th>
                        <th class="sorting_paging">@sortablelink('Admin.username', 'Last Updated By')</th>
                        <!--<th class="sorting_paging">@sortablelink('status', 'Status')</th>-->
                        <th class="sorting_paging">@sortablelink('created_at', 'Date')</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allrecords as $allrecord) 
                    <tr>
                        <td data-title="Transaction Type">{{isset($allrecord->type) ? ucfirst($allrecord->type):'N/A'}}</td>
                        <td data-title="Fee Value">{{$allrecord->fee_value ? $allrecord->fee_value:'N/A'}}</td>
<!--                        <td data-title="Status">
                            @if($allrecord->is_active == '1')
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
                                    <li><a href="{{ URL::to( 'admin/referral-setting-edit/'.$allrecord->id)}}" title="Edit Transaction Fee" class=""><i class="fa fa-pencil"></i>Edit Referral Fee</a></li>

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

