<?php use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
?>
{{ HTML::script('public/assets/js/facebox.js') }}
{{ HTML::style('public/assets/css/facebox.css') }}
@php
    use App\Http\Controllers\Admin\AdminsController;
@endphp
@php
    use App\Permission;
@endphp
<div class="admin_loader" id="loaderID">{{ HTML::image('public/img/website_load.svg', '') }}</div>
@if (!$allrecords->isEmpty())
    <div class="panel-body marginzero">
        <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
        {{ Form::open(['method' => 'post', 'id' => 'actionFrom']) }}
        <input type="hidden" name="page" value="{{ $page }}">
        <section id="no-more-tables" class="lstng-section">
            <div class="topn">
                <div class="topn_left">Card Request List</div>
                <div class="topn_rightd ddpagingshorting paggng-txt" id="pagingLinks" align="right">
                    <div class="topn_righ">
                        Showing {{ $allrecords->count() }} of {{ $allrecords->total() }} record(s).
                    </div>
                    <div class="panel-heading" style="align-items:center;">
                        {{ $allrecords->appends(Request::except('_token'))->render() }}
                    </div>
                </div>
            </div>
            <div class="tbl-resp-listing">
                <table class="table table-bordered table-striped table-condensed cf">
                    <thead class="cf ddpagingshorting">
                        <tr>
                            <!-- <th style="width:5%">#</th> -->
                            <th class="sorting_paging">ID</th>
                            <th class="sorting_paging">@sortablelink('user_id', 'Username')</th>
                            <th class="sorting_paging">Email</th>
                            <th class="sorting_paging">Phone</th>
                            <th class="sorting_paging">Account ID</th>
                            <th class="sorting_paging">@sortablelink('status', 'Status')</th>
                            <th class="sorting_paging">@sortablelink('created_at', 'Date')</th>
                            <th class="action_dvv"> Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($allrecords as $allrecord)
                        <?php //dd($allrecord->userCard->cardStatus); ?>
                            <tr>
                                <th>{{ $allrecord->id }}</th>
                                <td data-title="Full Name">{{ $allrecord->user->name }}
                                    {{ $allrecord->user->lastName }}</td>
                                <td data-title="Email Address">
                                    {{ $allrecord->user->email ? $allrecord->user->email : 'N/A' }}</td>
                                <td data-title="Contact Number">
                                    {{ $allrecord->user->phone ? $allrecord->user->phone : 'N/A' }}</td>
                                <td data-title="Account ID">
                                    <?php  
                                        if(isset($allrecord->userCard['cardType']) && $allrecord->userCard['cardType'] == "PHYSICAL"){
                                            echo $allrecord->userCard['accountId']; 
                                        }
                                    ?>
                                    <!-- {{ $allrecord->user->accountId ? $allrecord->user->accountId : 'N/A' }} -->
                                </td>
                                <td data-title="Status">
                                    @if ($allrecord->status == 0)
                                        Pending
                                    @elseif($allrecord->status == 1)
                                    @if(isset($allrecord->userCard->cardStatus) && $allrecord->userCard->cardStatus=="Active") 
                                        Activated
                                        @elseif(isset($allrecord->userCard->cardStatus) && $allrecord->userCard->cardStatus=="Inactive") 
                                        Inactive
                                        @else
                                        Assigned
                                    @endif
                                    @elseif($allrecord->status == 2)
                                        Rejected
                                    @else
                                        Unknown
                                    @endif
                                </td>
                                <td data-title="Date">{{ $allrecord->created_at->format('M d, Y h:i A') }}</td>
                                <td data-title="Action">
                                    <a href="{{URL::to('admin/card-assign/'.$allrecord->id)}}" title="View Card Request Details"
                                        class="btn btn-primary btn-xs">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        {{ Form::close() }}
    </div>
    </div>
@else
    <div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
    <div class="admin_no_record">No record found.</div>
@endif
