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
                <div class="topn_left" style="font-size: 20px;">Earnings List</div>
                <div class="topn_rightd ddpagingshorting" id="pagingLinks" align="right">
                    <div class="panel-heading" style="align-items:center;">
                        {{$allrecords->appends(Request::except('_token'))->render()}}
                    </div>
                </div>                
            </div>   
            <div class="transaction_info">
                <div class="topn_left_btsec">
                    <div class="payment_info">
                        <span class="pay_head">Total Transaction</span>
                        <span class="pay_body"> 
                            {{CURR}} {{  number_format((($total['total'] - floor($total['total'])) > 0.5 ? ceil($total['total']) : floor($total['total'])), 0, '', ' ') ?? 0 }}

                        </span>
                    </div>
                    <div class="payment_info">
                        <span class="pay_head">Total Earning</span>
                        <span class="pay_body"> 
                            {{CURR}} {{  number_format((($total['total_fee'] - floor($total['total_fee'])) > 0.5 ? ceil($total['total_fee']) : floor($total['total_fee'])), 0, '', ' ') ?? 0 }}


                        </span>
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
                        <th class="sorting_paging">@sortablelink('sender', 'Sender Phone')</th>
                        <th class="sorting_paging">@sortablelink('receiver', 'Receiver Phone')</th>
                        <th class="sorting_paging">@sortablelink('id', 'Transaction ID')</th>
                        <th class="sorting_paging">@sortablelink('refrence_id', 'Reference ID')</th>
                        <th class="sorting_paging">@sortablelink('amount', 'Transaction Amount')</th>
                        <th class="sorting_paging">@sortablelink('transaction_amount', 'Transaction Fee')</th>
                        <th class="sorting_paging">@sortablelink('total_amount', 'Transaction Total')</th>
                        <th class="sorting_paging">@sortablelink('updated_at', 'Transaction Date')</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allrecords as $allrecord) 
                    <tr>
                        <!--<th style="width:5%"><input type="checkbox" onclick="javascript:isAllSelect(this.form);" name="chkRecordId[]" value="{{$allrecord->id}}" /></th>-->
                                                <!--<td data-title="Transaction Id">{{$allrecord->id}}</td>-->
                        <td data-title="SenderPhone">{{$allrecord->User->phone}}</td>
                        <td data-title="ReceiverPhone">{{(!empty($allrecord->Receiver->phone) ? $allrecord->Receiver->phone : $allrecord->receiver_mobile)}}</td>
                        <td data-title="Transaction ID">{{$allrecord->refrence_id}}</td>
                        <td data-title="Reference ID">{{$allrecord->id}}</td>
                        <td data-title="Transaction Amount">{{CURR}}
                            {{  number_format((($allrecord->amount - floor($allrecord->amount)) > 0.5 ? ceil($allrecord->amount) : floor($allrecord->amount)), 0, '', ' ') ?? 0 }}

                        </td>
                        <td data-title="Transaction Fee">
                            {{CURR}} {{  number_format((($allrecord->transaction_amount - floor($allrecord->transaction_amount)) > 0.5 ? ceil($allrecord->transaction_amount) : floor($allrecord->transaction_amount)), 0, '', ' ') ?? 0 }}

                        </td>
                        <td data-title="Transaction Total">
                            {{CURR}} {{  number_format((($allrecord->total_amount - floor($allrecord->total_amount)) > 0.5 ? ceil($allrecord->total_amount) : floor($allrecord->total_amount)), 0, '', ' ') ?? 0 }}

                        </td>

                        <td data-title="Transaction Date">{{$allrecord->updated_at->format('M d, Y h:i:s A')}}</td>
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
        <legend class="head_pop">Earnings Details</legend>
            <!-- <legend class="head_pop"></legend> -->
            <div class="drt">
                <div class="admin_pop"><span>Transaction ID: </span>  <label>{{$allrecord->refrence_id}}</label></div>
                <div class="admin_pop"><span>Reference ID: </span>  <label>{{$allrecord->id}}</label></div>

                <div class="admin_pop"><span>Sender Name: </span>  <label>{{$allrecord->User->name}}</label></div>
                @if($allrecord->Receiver)
                    @if($allrecord->Receiver->id == 0 && $allrecord->excel_trans_id == 0)
                        <div class="admin_pop"><span>Receiver Name:</span> <label>N/A</label></div>
                    @elseif($allrecord->Receiver->id == 0 && $allrecord->excel_trans_id != 0)
                      <?php $excel=DB::table('excel_transactions')->where('id',$allrecord->excel_trans_id)->first();?>
                        <div class="admin_pop"><span>Receiver Name:</span> <label>{{ $excel->first_name }}</label></div>
                    @else
                        <div class="admin_pop"><span>Receiver Name:</span> <label>{{ $allrecord->Receiver->name }}</label></div>
                    @endif
                @else
                    <div class="admin_pop"><span>Receiver Name:</span> <label>N/A</label></div>
                @endif

                <div class="admin_pop"><span>Payment For: </span>  <label>{{$allrecord->payment_mode}}</label></div>

                <div class="admin_pop"><span>Transaction Amount: </span>  <label>
                    {{CURR}} {{  number_format((($allrecord->amount - floor($allrecord->amount)) > 0.5 ? ceil($allrecord->amount) : floor($allrecord->amount)), 0, '', ' ') ?? 0 }}
                    </label></div>
                <div class="admin_pop"><span>Transaction Fee: </span>  <label>
                    {{CURR}} {{  number_format((($allrecord->transaction_amount - floor($allrecord->transaction_amount)) > 0.5 ? ceil($allrecord->transaction_amount) : floor($allrecord->transaction_amount)), 0, '', ' ') ?? 0 }}
                    </label></div>
                <div class="admin_pop"><span>Transaction Total: </span>  <label>
                    {{CURR}} {{  number_format((($allrecord->total_amount - floor($allrecord->total_amount)) > 0.5 ? ceil($allrecord->total_amount) : floor($allrecord->total_amount)), 0, '', ' ') ?? 0 }}
                    </label></div>

                <div class="admin_pop"><span>Transaction Date: </span>  <label>{{$allrecord->updated_at->format('M d, Y h:i:s A')}}</label></div>

        </fieldset>
    </div>
</div>
@endforeach
@endif
