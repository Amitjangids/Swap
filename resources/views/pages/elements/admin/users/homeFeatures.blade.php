<div class="admin_loader" id="loaderID">{{HTML::image("public/img/website_load.svg", SITE_TITLE)}}</div>
@if(!$allrecords->isEmpty())
<div class="panel-body marginzero">
    <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
    {{ Form::open(array('method' => 'post', 'id' => 'actionFrom')) }}
    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="topn_left">Enable/Disable Features List</div>
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
                        <th class="sorting_paging">@sortablelink('name', 'Feature Name')</th>
                        <th class="sorting_paging">@sortablelink('status', 'Status')</th>
                        <th class="sorting_paging">@sortablelink('Admin.username', 'Last Updated By')</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allrecords as $allrecord)
                    <tr>
                        <td data-title="Feature Name">{{$allrecord->name}}</td>
                        <td data-title="Status" id="status_{{$allrecord->slug}}">
                            @if($allrecord->status == 1)
                            Activated
                            @else
                            Deactivated
                            @endif
                        </td>
                        <td data-title="Last Updated By">
                            {{$allrecord->Admin->username}} - {{$allrecord->Admin->id != 1?'Subadmin':'Admin'}}
                        </td>
                        <td data-title="Action">
                            <div id="loderstatus{{$allrecord->id}}" class="right_action_lo">{{HTML::image("public/img/loading.gif", '')}}</div>

                            <span class="right_acdc" id="status{{$allrecord->id}}">
                                @if($allrecord->status == '1')
                                <a href="{{ URL::to( 'admin/users/deactivateFeature/'.$userInfo->slug.'/'.$allrecord->slug)}}" title="Deactivate" class="deactivate"><button class="btn btn-success btn-xs"><i class="fa fa-check"></i></button></a>
                                @else
                                <a href="{{ URL::to( 'admin/users/activateFeature/'.$userInfo->slug.'/'.$allrecord->slug)}}" title="Activate" class="activate"><button class="btn btn-danger btn-xs"><i class="fa fa-ban"></i></button></a>
                                @endif
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    {{ Form::close()}}
</div>  
@else 
<div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
<div class="admin_no_record">No record found.</div>
@endif