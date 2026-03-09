<div class="admin_loader" id="loaderID">{{HTML::image("public/img/website_load.svg", '')}}</div>

@if(!$allrecords->isEmpty())
    <div class="panel-body marginzero">
        <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
        {{ Form::open(array('method' => 'post', 'id' => 'actionFrom')) }}
        <input type="hidden" name="page" value="{{$page}}">
        <section id="no-more-tables" class="lstng-section">
            <div class="topn">
                <div class="topn_left"> Travel Document List</div>
                <div class="topn_rightd ddpagingshorting paggng-txt" id="pagingLinks" align="right">
                    <div class="topn_righ">
                        Showing {{$allrecords->count()}} of {{ $allrecords->total() }} record(s).
                    </div>
                    <div class="panel-heading" style="align-items:center;">
                        {{$allrecords->appends(Request::except('_token'))->render()}}
                    </div>
                </div>
            </div>
            <div class="tbl-resp-listing">
                <table class="table table-bordered table-striped table-condensed cf">
                    <thead class="cf ddpagingshorting">
                        <tr>
                            <th class="sorting_paging">@sortablelink('name', 'ID')</th>
                            <th class="sorting_paging">@sortablelink('Passport', 'Passport')</th>
                            <th class="sorting_paging">@sortablelink('Airline Ticket', 'Airline Ticket')</th>
                            <th class="sorting_paging">@sortablelink('Stamp Visa Entry', 'Stamp Visa Entry')</th>
                            <th class="sorting_paging">@sortablelink('created_at', 'Date')</th> 
                            <th class="sorting_paging">@sortablelink('Action', 'Action')</th> 
                        </tr>
                    </thead>
                    <tbody>

                    @foreach($allrecords as $doc)

<tr>
    <td>{{ $doc->id }}</td>

    {{-- PASSPORT --}}
    <td>
    @if($doc->passport)
        @php
            $ext = strtolower(pathinfo($doc->passport, PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['jpg','jpeg','png','webp']);
            $fileUrl = HTTP_PATH.'/'.PASSPORT_PATH.$doc->passport;
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

    {{-- TICKET --}}
    <td>
    @if($doc->ticket)
        @php
            $ext = strtolower(pathinfo($doc->ticket, PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['jpg','jpeg','png','webp']);
            $fileUrl = HTTP_PATH.'/'.TICKET_PATH.$doc->ticket;
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


    {{-- VISA --}}
    <td>
    @if($doc->visa)
        @php
            $ext = strtolower(pathinfo($doc->visa, PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['jpg','jpeg','png','webp']);
            $fileUrl = HTTP_PATH.'/'.VISA_PATH.$doc->visa;
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

    <td>{{ $doc->created_at }}</td>
    <td>
        {{ $doc->status === 'approved' ? 'Approved' : ($doc->status === 'declined' ? 'Declined' : '') }}
        
        @if($doc->status == "pending")
                                        <a href="{{ URL::to('admin/users/approveTravel/' . $doc->id) }}" title="Approve"
                                            class="btn btn-info">Approve</a>
                                        <a href="{{ URL::to('admin/users/declineTravel/' . $doc->id) }}" title="Decline"
                                            class="btn btn-info">Decline</a>
                                    @endif
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