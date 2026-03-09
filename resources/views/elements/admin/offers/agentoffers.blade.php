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
            <div class="topn_left">Agent Offers List</div>
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
                        <th class="sorting_paging">@sortablelink('type', 'Type')</th>
                        <th class="sorting_paging">@sortablelink('offer', 'Offer Value')</th>
                        <th class="sorting_paging">@sortablelink('status', 'Status')</th>
                        <th class="sorting_paging">@sortablelink('created_at', 'Date')</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allrecords as $allrecord) 
                    <tr>
                        <!--<th style="width:5%"><input type="checkbox" onclick="javascript:isAllSelect(this.form);" name="chkRecordId[]" value="{{$allrecord->id}}" /></th>-->
                        <td data-title="Type">{{isset($allrecord->type) ? ucfirst($allrecord->type):'N/A'}}</td>
                        <td data-title="Offer">{{isset($allrecord->offer) ? ucfirst($allrecord->offer).'%':'0'}}</td>

                        <td data-title="Status" id="status_{{$allrecord->id}}">
                            @if($allrecord->status == '1')
                            Activated
                            @else
                            Deactivated
                            @endif
                        </td>
                        <td data-title="Date">{{$allrecord->created_at->format('M d, Y h:i:s A')}}</td>
                        <td data-title="Action">

                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                    <li class="right_acdc" id="status{{$allrecord->id}}">
                                        @if($allrecord->status == '1')
                                        <a href="{{ URL::to( 'admin/offers/deactivateoffer/'.$allrecord->id)}}" title="Deactivate" class="deactivate"><i class="fa fa-check"></i>Deactivate</a>
                                        @else
                                        <a href="{{ URL::to( 'admin/offers/activateoffer/'.$allrecord->id)}}" title="Activate" class="activate"><i class="fa fa-ban"></i>Activate</a>
                                        @endif
                                    </li>
                                    <li><a href="{{ URL::to( 'admin/offers/editagentoffer/'.$userInfo->slug.'/'.$allrecord->id)}}" title="Edit Offer" class=""><i class="fa fa-pencil"></i>Edit Offer</a></li>
                                    <li><a href="{{ URL::to( 'admin/offers/deleteagentoffer/'.$userInfo->slug.'/'.$allrecord->id)}}" title="Delete" class="" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash-o"></i>Delete</a></li>

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

