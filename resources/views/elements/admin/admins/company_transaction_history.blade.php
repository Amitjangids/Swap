<?php use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
?>
{{ HTML::script('public/assets/js/facebox.js')}}
{{ HTML::style('public/assets/css/facebox.css')}}
@php
use App\Http\Controllers\Admin\AdminsController;
@endphp
@php
use App\Permission;
@endphp
<div class="admin_loader" id="loaderID">{{HTML::image("public/img/website_load.svg", '')}}</div>
@if(!$allrecords->isEmpty())
<div class="panel-body marginzero">
    <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
    {{ Form::open(array('method' => 'post', 'id' => 'actionFrom')) }}
    <input type="hidden" name="page" value="{{$page}}">
    <section id="no-more-tables" class="lstng-section">

    <div class="transaction_info">
                <div class="topn_left_btsec">
                    <div class="payment-info-parent">
                        <div class="payment_info"> 
                            <span class="pay_head">Wallet Balance</span>
                            <span class="pay_body">{{$balance}}</span>
                        </div>
                    </div>
                </div>
            </div>

        <div class="topn">
            <div class="topn_left">Company Transactions History</div>
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
                        <th class="sorting_paging">@sortablelink('company_name', 'Company Name')</th>
                        <th class="sorting_paging">@sortablelink('company_code', 'Company Code')</th>
                        <th class="sorting_paging">@sortablelink('amount', 'Amount')</th>
                        <th class="sorting_paging">@sortablelink('payment_mode', 'Transaction For')</th>
                        <th class="sorting_paging">@sortablelink('refrence_id', 'Refrence ID')</th>
                        <th class="sorting_paging">@sortablelink('billing_description', 'Billing Description')</th>
                        <th class="sorting_paging">@sortablelink('receiver_id', 'Initiated By')</th>
                        <th class="sorting_paging">@sortablelink('created_at', 'Date')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allrecords as $allrecord)
                    <tr>
                    <td data-title="Full Name">{{$allrecord->User->company_name}}</td>
                    <td data-title="Email Address">{{$allrecord->User->company_code ? $allrecord->User->company_code : 'N/A'}}</td>
                    <td data-title="Full Name">{{$allrecord->amount}}</td>
                    <td data-title="Full Name">{{$allrecord->payment_mode}}</td>
                    <td data-title="Full Name">{{$allrecord->refrence_id}}</td>
                    <td data-title="Full Name"><?php 
                                $bllngDesc = str_replace("<br>","##",$allrecord->billing_description);
                                $descArr = explode("##",$bllngDesc); 
                                foreach($descArr as $key=>$val)
                                {
                                    echo $key==0 ? $val : $val.'<br>';
                                }   ?></td>
                    <td data-title="Full Name">{{$allrecord->Receiver->username}}</td>
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