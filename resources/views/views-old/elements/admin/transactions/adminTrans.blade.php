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
            <div class="manage_sec">
                <div class="topn_left">Admin Transactions List</div>

                <div class="topn_rightd ddpagingshorting paggng-txt" id="pagingLinks" align="right">
                    <div class="topn_righ">
                        Showing {{$allrecords->count()}} of {{ $allrecords->total() }} record(s).
                    </div>
                    <div class="panel-heading" style="align-items:center;">
                        {{$allrecords->appends(Request::except('_token'))->render()}}
                    </div>
                </div>   
            </div>   

            <div class="transaction_info">
                <div class="topn_left_btsec">
                    <div class="payment_info">
                        <span class="pay_head">Total Transaction</span>
                        <span class="pay_body">{{CURR}} {{number_format($total['amount'],2)}}</span>
                    </div>
                </div>
            </div>
        </div>


        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        <!--<th style="width:5%">#</th>-->
                                                <!--<th style="width:5%">Trans Id</th>-->
                        <th class="sorting_paging">@sortablelink('User.name', 'User Name')</th>
                        <th class="sorting_paging">@sortablelink('User.user_type', 'User Type')</th>
                        <th class="sorting_paging">@sortablelink('User.phone', 'User Phone')</th>
                        <th class="sorting_paging">@sortablelink('Admin.username', 'Admin Username')</th>
                        <?php /* <th class="sorting_paging">@sortablelink('trans_type', 'Transaction Type')</th> */ ?>
                        <th class="sorting_paging">@sortablelink('payment_mode', 'Transaction For')</th>
                        <th class="sorting_paging">@sortablelink('amount', 'Amount')</th>
                        <!--<th class="sorting_paging">@sortablelink('transaction_amount', 'Transaction Fee')</th>-->
                        <!--<th class="sorting_paging">@sortablelink('total_amount', 'Total Amount')</th>-->
                        <th class="sorting_paging">@sortablelink('refrence_id', 'Transaction ID')</th>
                        <th class="sorting_paging">@sortablelink('status', 'Status')</th>
                        <th class="sorting_paging">@sortablelink('created_at', 'Transaction Date')</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allrecords as $allrecord) 
                    <tr>
                        <!--<th style="width:5%"><input type="checkbox" onclick="javascript:isAllSelect(this.form);" name="chkRecordId[]" value="{{$allrecord->id}}" /></th>-->
                                                <!--<td data-title="Transaction Id">{{$allrecord->id}}</td>-->
                        <td data-title="User Name">{{isset($allrecord->User->name) ? ucfirst($allrecord->User->name):'N/A'}}</td>
                        <td data-title="User Type">{{isset($allrecord->User->user_type) ? ucfirst($allrecord->User->user_type):'N/A'}}</td>
                        <td data-title="User Phone">{{isset($allrecord->User->phone) ? ucfirst($allrecord->User->phone):'N/A'}}</td>
                        <td data-title="Admin Name">
                            @if(isset($allrecord->Admin->username))
                                {{ucfirst($allrecord->Admin->username)}} @if($allrecord->Admin->id == 1){{' (Admin)'}} @else {{' (Subadmin)'}} @endif
                            @elseif(isset($allrecord->User->name))
                                {{ucfirst($allrecord->User->name)}}
                            @else
                                {{'N/A'}}
                            @endif
                        </td>
                        <td data-title="Transaction For">
                            <?php
                            echo $allrecord->payment_mode;
                            ?>
                        </td>
                        <td data-title="Amount Paid">{{CURR}} {{$allrecord->amount}}</td>
                        <td data-title="Transaction ID">{{$allrecord->refrence_id}}</td>
                        <td data-title="Status">
                            @if($allrecord->status == '1')
                            Completed
                            @elseif($allrecord->status == '2')
                            Pending
                            @elseif($allrecord->status == '3')
                            Failed
                            @elseif($allrecord->status == '4')
                            Cancelled	
                            @endif
                        </td>
                        <td data-title="Transaction Date">{{$allrecord->created_at->format('M d, Y h:i:s A')}}</td>
                        <td data-title="Action">
                            <a href="#info{!! $allrecord->id !!}" title="View Transaction Details" class="btn btn-primary btn-xs" rel='facebox'><i class="fa fa-eye"></i></a>
                            
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

<?php

use App\Models\Transaction;
?>
@if(!$allrecords->isEmpty())
@foreach($allrecords as $allrecord)
<div id="info{!! $allrecord->id !!}" style="display: none;">
    <div class="nzwh-wrapper">
        <fieldset class="nzwh">
            <legend class="head_pop">#{!! $allrecord->id !!}</legend>
            <div class="drt">
                <div class="admin_pop"><span>User Name: </span>  <label>
                        {{isset($allrecord->User->name) ? ucfirst($allrecord->User->name):'N/A'}}
                    </label>
                </div>
                <div class="admin_pop"><span>User Type: </span>  <label>
                        {{isset($allrecord->User->user_type) ? ucfirst($allrecord->User->user_type):'N/A'}}
                    </label>
                </div>
                <div class="admin_pop"><span>User Phone: </span>  <label>
                        {{isset($allrecord->User->phone) ? ucfirst($allrecord->User->phone):'N/A'}}
                    </label>
                </div>
                <div class="admin_pop"><span>Admin Username: </span>  <label>
                        @if(isset($allrecord->Admin->username))
                        {{ucfirst($allrecord->Admin->username)}}
                        @elseif(isset($allrecord->User->name))
                        {{ucfirst($allrecord->User->name)}}
                        @else
                        {{'N/A'}}
                        @endif
                    </label>
                </div>
                <div class="admin_pop"><span>Transaction For: </span>  <label>
                        {{$allrecord->payment_mode}}
                    </label>
                </div>

                <div class="admin_pop"><span>Amount: </span>  <label>
                        {{CURR.' '.$allrecord->amount}}
                    </label>
                </div>


                @if($allrecord->payment_mode == 'Refund')

                <?php
                $transArr = explode('-', $allrecord->billing_description);
                if (isset($transArr[1])) {
                    $addrecord1 = DB::table('transactions')
                            ->where('id', $transArr[1])
                            ->first();
                    ?>
                    <div class="admin_pop"><span>Reference ID: </span>  <label>{{$addrecord1->refrence_id}}</label></div>
                <?php }
                ?>

                @endif

                <div class="admin_pop"><span>Transaction ID: </span>  <label>{{$allrecord->refrence_id}}</label></div>
                <div class="admin_pop"><span>Status: </span> 
                    <label> 
                        @if($allrecord->status == '1')
                        Completed
                        @elseif($allrecord->status == '2')
                        Pending
                        @elseif($allrecord->status == '3')
                        Failed
                        @elseif($allrecord->status == '4')
                        Cancelled	
                        @endif
                    </label>
                </div>
                <div class="admin_pop"><span>Transaction Date: </span>  <label>{{$allrecord->created_at->format('M d, Y h:i:s A')}}</label></div>

        </fieldset>
    </div>
</div>
@endforeach
@endif
