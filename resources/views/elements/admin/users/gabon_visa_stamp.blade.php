<div class="admin_loader" id="loaderID">{{HTML::image("public/img/website_load.svg", '')}}</div>

@if($allrecords)
    <div class="panel-body marginzero">
        <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
        {{ Form::open(array('method' => 'post', 'id' => 'actionFrom')) }}
        <section id="no-more-tables" class="lstng-section">
            <div class="topn">
                <div class="topn_left"> Gabon Visa Stamp </div>
                <div class="topn_rightd ddpagingshorting paggng-txt" id="pagingLinks" align="right">
                    
                </div>
            </div>
            <div class="tbl-resp-listing">
                <table class="table table-bordered table-striped table-condensed cf">
                    <thead class="cf ddpagingshorting">
                        <tr>
                            <th class="sorting_paging">@sortablelink('id', 'ID')</th>
                            <th class="sorting_paging">@sortablelink('Gabon Visa Stamp', 'Gabon Visa Stamp')</th>
                            <th class="sorting_paging">@sortablelink('Action', 'Action')</th> 
                        </tr>
                    </thead>
                    <tbody> 
                        <tr>
                            <td>{{ $allrecords->id }}</td> 
                            <td>
                            @if($allrecords->gabonStampImg)
                                @php
                                    $ext = strtolower(pathinfo($allrecords->gabonStampImg, PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg','jpeg','png','webp']);
                                    $fileUrl = HTTP_PATH.'/'.GABON_VISA_STAMPED.$allrecords->gabonStampImg;
                                @endphp

                                @if($isImage)
                                    <a href="{{ $fileUrl }}" target="_blank">
                                        {{ HTML::image($fileUrl, '', ['style' => 'width:100px;height:100px;cursor:pointer']) }}
                                    </a>
                                @else
                                    <a href="{{ $fileUrl }}" target="_blank">View</a>
                                @endif
                            @endif
                        </td>
                            <td>{{ $allrecords->created_at }}</td>
                            <td>
                                {{ $allrecords->gabonStampStatus === 'approved' ? 'Approved' : ($allrecords->gabonStampStatus === 'declined' ? 'Declined' : '') }}
                                @if($allrecords->gabonStampStatus == "pending")
                                    <a href="{{ URL::to('admin/users/approveGabonStamp/' . $allrecords->id) }}" title="Approve"
                                        class="btn btn-info">Approve</a>
                                    <a href="{{ URL::to('admin/users/declineGabonStamp/' . $allrecords->id) }}" title="Decline"
                                        class="btn btn-info">Decline</a>
                                @endif
                            </td>
                        </tr> 

                        
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