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
                <div class="topn_left">Transactions List</div>

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
                    <div class="payment_info">
                        <span class="pay_head">Total Earning</span>
                        <span class="pay_body">{{CURR}} {{number_format($total['fee'],2)}}</span>
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
                        <th class="sorting_paging">@sortablelink('User.name', 'Sender Name')</th>
                        <th class="sorting_paging">@sortablelink('User.phone', 'Sender Phone')</th>
                        <th class="sorting_paging">@sortablelink('Receiver.name', 'Receiver Name')</th>
                        <th class="sorting_paging">@sortablelink('Receiver.phone', 'Receiver Phone')</th>
                        <?php /* <th class="sorting_paging">@sortablelink('trans_type', 'Transaction Type')</th> */ ?>
                        <th class="sorting_paging">@sortablelink('payment_mode', 'Transaction For')</th>
                        <th class="sorting_paging">@sortablelink('company_name', 'Company Name')</th>
                        <th class="sorting_paging">@sortablelink('amount', 'Amount')</th>
                        <th class="sorting_paging">@sortablelink('transaction_amount', 'Transaction Fee')</th>
                        <th class="sorting_paging">@sortablelink('total_amount', 'Total Amount')</th>
                        <th class="sorting_paging">@sortablelink('refrence_id', 'Transaction ID')</th>
                        <th class="sorting_paging">@sortablelink('status', 'Status')</th>
                        <th class="sorting_paging">@sortablelink('created_at', 'Transaction / Request Date')</th>
                        <th class="sorting_paging">@sortablelink('updated_at', 'Transaction Process Date')</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allrecords as $allrecord) 
                    <tr>
                        <!--<th style="width:5%"><input type="checkbox" onclick="javascript:isAllSelect(this.form);" name="chkRecordId[]" value="{{$allrecord->id}}" /></th>-->
                                                <!--<td data-title="Transaction Id">{{$allrecord->id}}</td>-->
                        <td data-title="Sender Name">{{isset($allrecord->User->name) ? ucfirst($allrecord->User->name):'N/A'}}</td>
                        <td data-title="Sender Phone">{{isset($allrecord->User->phone) ? ucfirst($allrecord->User->phone):'N/A'}}</td>
                        <td data-title="Receiver Name">
                            @if(isset($allrecord->Receiver->name))
                            {{ucfirst($allrecord->Receiver->name)}}
                            @elseif(isset($allrecord->User->name))
                            {{ucfirst($allrecord->User->name)}}
                            @else
                            {{'N/A'}}
                            @endif
                        </td>
                        <td data-title="Receiver Phone">
                            @if(isset($allrecord->Receiver->phone))
                            {{ucfirst($allrecord->Receiver->phone)}}
                            @elseif(isset($allrecord->User->phone))
                            {{ucfirst($allrecord->User->phone)}}
                            @else
                            {{'N/A'}}
                            @endif
                        </td>
                        <?php /* <td data-title="Transaction Type">
                          @if($allrecord->trans_type == 1)
                          {{'Credit'}}
                          @elseif($allrecord->trans_type == 2)
                          {{'Debit'}}
                          @elseif($allrecord->trans_type == 3)
                          {{'Topup'}}
                          @elseif($allrecord->trans_type == 4)
                          {{'Request'}}
                          @endif
                          </td> */ ?>
                        <td data-title="Transaction For">
                            <?php
                            if ($allrecord->user_type == 'Agent') {
                                if ($allrecord->payment_mode == 'Withdraw') {
                                    echo 'Buy Balance';
                                } elseif ($allrecord->payment_mode == 'Agent Deposit') {
                                    echo 'Sell Balance';
                                } else {
                                    echo $allrecord->payment_mode;
                                }
                            } else {
                                echo $allrecord->payment_mode;
                            }
                            ?>
                            <!--{{$allrecord->payment_mode}}-->
                        </td>
                        <td data-title="Comapny Name">{{$allrecord->company_name?$allrecord->company_name:'N/A'}}</td>
                        <td data-title="Amount Paid">{{CURR}} {{$allrecord->amount}}</td>
                        <td data-title="Transaction Fee">{{CURR}} {{$allrecord->transaction_amount?$allrecord->transaction_amount:'0'}}</td>
                        <td data-title="Total Amount">{{CURR}} {{$allrecord->total_amount}}</td>
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
                        <td data-title="Transaction / Request Date">{{$allrecord->created_at->format('M d, Y h:i:s A')}}</td>
                        <td data-title="Transaction Process Date">{{$allrecord->updated_at->format('M d, Y h:i:s A')}}</td>
                        <td data-title="Action">
                            <a href="#info{!! $allrecord->id !!}" title="View Transaction Details" class="btn btn-primary btn-xs" rel='facebox'><i class="fa fa-eye"></i></a>
                                                        <!--<a href="{{ URL::to( 'admin/transactions/delete/'.$allrecord->id)}}" title="Delete" class="btn btn-danger btn-xs action-list delete-list" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash-o"></i></a>-->
                            <!--                            <div class="btn-group">
                                                            <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                                                <i class="fa fa-list"></i>
                                                                <span class="caret"></span>
                                                            </button>
                                                             <ul class="dropdown-menu pull-right">
                                                                <li><a href="{{ URL::to( 'admin/transactions/delete/'.$allrecord->id)}}" title="Delete" class="" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash-o"></i>Delete</a></li>
                                                                <li><a href="{{ URL::to( 'admin/transactions/delete/'.$allrecord->id)}}" title="Delete" class="" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash-o"></i>Delete</a></li>
                                                                
                                                            </ul> 
                                                        </div>-->
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <!--            <div class="search_frm">
                            <button type="button" name="chkRecordId" onclick="checkAll(true);"  class="btn btn-info">Select All</button>
                            <button type="button" name="chkRecordId" onclick="checkAll(false);" class="btn btn-info">Unselect All</button>
            <?php
            $accountStatus = array(
                'Delete' => "Delete",
            );
            ?>
                            <div class="list_sel">{{Form::select('action', $accountStatus,null, ['class' => 'small form-control','placeholder' => 'Action for selected record', 'id' => 'action'])}}</div>
                            <button type="submit" class="small btn btn-success btn-cons btn-info" onclick="return ajaxActionFunction();" id="submit_action">OK</button>
                        </div>    -->
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
                <div class="admin_pop"><span>Sender Name: </span>  <label>
                        {{isset($allrecord->User->name) ? ucfirst($allrecord->User->name):'N/A'}}
                    </label>
                </div>
                <div class="admin_pop"><span>Sender Phone: </span>  <label>
                        {{isset($allrecord->User->phone) ? ucfirst($allrecord->User->phone):'N/A'}}
                    </label>
                </div>
                <div class="admin_pop"><span>Receiver Name: </span>  <label>
                        @if(isset($allrecord->Receiver->name))
                        {{ucfirst($allrecord->Receiver->name)}}
                        @elseif(isset($allrecord->User->name))
                        {{ucfirst($allrecord->User->name)}}
                        @else
                        {{'N/A'}}
                        @endif
                    </label>
                </div>
                <div class="admin_pop"><span>Receiver Phone: </span>  <label>
                        @if(isset($allrecord->Receiver->phone))
                        {{ucfirst($allrecord->Receiver->phone)}}
                        @elseif(isset($allrecord->User->phone))
                        {{ucfirst($allrecord->User->phone)}}
                        @else
                        {{'N/A'}}
                        @endif
                    </label>
                </div>
                <?php /* <div class="admin_pop"><span>Transaction Type: </span>  <label>
                  @if($allrecord->trans_type == 1)
                  {{'Credit'}}
                  @elseif($allrecord->trans_type == 2)
                  {{'Debit'}}
                  @elseif($allrecord->trans_type == 3)
                  {{'Topup'}}
                  @elseif($allrecord->trans_type == 4)
                  {{'Request'}}
                  @endif
                  </label>
                  </div> */ ?>
                <div class="admin_pop"><span>Transaction For: </span>  <label>
                        <?php if ($allrecord->user_type == 'Agent') {
                                if ($allrecord->payment_mode == 'Withdraw') {
                                    echo 'Buy Balance';
                                } elseif ($allrecord->payment_mode == 'Agent Deposit') {
                                    echo 'Sell Balance';
                                } else {
                                    echo $allrecord->payment_mode;
                                }
                            } else {
                                if ($allrecord->payment_mode == 'Agent Deposit') {
                                    echo 'Deposit';
                                } else {
                                    echo $allrecord->payment_mode;
                                }
                            }
                            ?>
                    </label>
                </div>
                <div class="admin_pop"><span>Company Name: </span>  <label>
                        {{$allrecord->company_name?$allrecord->company_name:'N/A'}}
                    </label>
                </div>
                <div class="admin_pop"><span>Real Value: </span>  <label>
                        {{$allrecord->real_value?$allrecord->currency.' '.$allrecord->real_value:'N/A'}}
                    </label>
                </div>

                <div class="admin_pop"><span>Amount: </span>  <label>
                        {{CURR.' '.$allrecord->amount}}
                    </label>
                </div>


                <div class="admin_pop"><span>Transaction Fee: </span>  <label>
                        {{CURR.' '.$allrecord->transaction_amount}}
                    </label>
                </div>
                <div class="admin_pop"><span>Total Amount: </span>  <label>
                        {{CURR.' '.$allrecord->total_amount}}
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
                <div class="admin_pop"><span>Transaction/Request Date: </span>  <label>{{$allrecord->created_at->format('M d, Y h:i:s A')}}</label></div>
                <div class="admin_pop"><span>Transaction Process Date: </span>  <label>{{$allrecord->updated_at->format('M d, Y h:i:s A')}}</label></div>

        </fieldset>
    </div>
</div>
@endforeach
@endif
