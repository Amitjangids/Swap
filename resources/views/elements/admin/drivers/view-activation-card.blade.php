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
            <div class="topn_left">Activation Cards</div>
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
                        <th class="sorting_paging">@sortablelink('drivername', 'Driver Name')</th>
                        <th class="sorting_paging">@sortablelink('username', 'Fullname')</th>
                        <th class="sorting_paging">@sortablelink('accountId', 'Account ID')</th>
                        <th class="sorting_paging">@sortablelink('cardtype', 'Card Type')</th>
                        <th class="sorting_paging">@sortablelink('created_at', 'Date')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allrecords as $allrecord) 
                    <tr>
                        <td data-title="ID">{{$allrecord->id}}</td>
                        <td data-title="DriverName">{{$allrecord->driverName}}</td>
                        <td data-title="UserName">{{$allrecord->firstName.' '.$allrecord->lastName ?? ""}}</td>
                        <td data-title="AccountID">{{$allrecord->accountId ?? 'N/A'}}</td>
                        <td data-title="cardType">{{$allrecord->cardType ?? 'N/A'}}</td>
                        <td data-title="Date">{{$allrecord->created_at->format('M d, Y h:i A')}}</td>
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