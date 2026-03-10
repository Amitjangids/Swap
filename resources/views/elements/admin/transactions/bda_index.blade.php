{{ HTML::script('public/assets/js/facebox.js') }}
{{ HTML::style('public/assets/css/facebox.css') }}
<script type="text/javascript">
    $(document).ready(function ($) {
        $('.close_image').hide();
        $('a[rel*=facebox]').facebox({
            closeImage: '{!! HTTP_PATH !!}/public/img/close.png'
        });
    });
</script>
<div class="admin_loader" id="loaderID">{{ HTML::image('public/img/website_load.svg', '') }}</div>
@if (!$allrecords->isEmpty())
    <div class="panel-body marginzero">
        <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
        {{ Form::open(['method' => 'post', 'id' => 'actionFrom']) }}
        <section id="no-more-tables" class="lstng-section">
            <div class="topn">
                <div class="manage_sec">
                    <div class="topn_left">BDA Transactions List</div>

                    <div class="topn_rightd ddpagingshorting paggng-txt" id="pagingLinks" align="right">
                        <div class="topn_righ">
                            Showing {{ $allrecords->count() }} of {{ $allrecords->total() }} record(s).
                        </div>
                        <div class="panel-heading" style="align-items:center;">
                            {{ $allrecords->appends(Request::except('_token'))->render() }}
                        </div>
                    </div>
                </div>
                <div class="transaction_info">
                    <div class="topn_left_btsec">
                        <div class="payment-info-parent">
                            <div class="payment_info">
                                <span class="pay_head">Total Transaction</span>
                                <span
                                    class="pay_body">{{ CURR }}
                                    {{  number_format((($totalAmount['total_amount'] - floor($totalAmount['total_amount'])) > 0.5 ? ceil($totalAmount['total_amount']) : floor($totalAmount['total_amount'])), 0, '', ' ') ?? 0 }}
                                </span>
                            </div>
                            <div class="payment_info">
                                <span class="pay_head">Total Earning</span>
                                <span
                                    class="pay_body">{{ CURR }}
                                    {{  number_format((($totalAmount['total_fee'] - floor($totalAmount['total_fee'])) > 0.5 ? ceil($totalAmount['total_fee']) : floor($totalAmount['total_fee'])), 0, '', ' ') ?? 0 }}
                                </span>
                            </div>
                        </div>
                        <div class="download_excel">
                            <a href="javascript:void(0)" class="btn btn-success export_excel">Download Excel <i
                                    class="fa fa-file-excel-o" aria-hidden="true"></i></a>
                        </div>
                    </div>
                </div>
            </div>


            <script>
                $('.export_excel').on('click', function () {

                    var beneficiary = $('input[name=beneficiary]').val();
                    var iban = $('input[name=iban]').val();
                    var refrence = $('input[name=refrence]').val();
                    var requestD = $('input[name=to]').val();
                    // Redirect to the export URL with query parameters
                    var url = '{{ route("exportexcelbda") }}?beneficiary=' + beneficiary + '&iban=' + iban + '&refrence=' + refrence + '&to=' + requestD;
                    window.location.href = url;
                });

            </script>


            <div class="tbl-resp-listing">
                <table class="table table-bordered table-striped table-condensed cf">
                    <thead class="cf ddpagingshorting">
                        <tr>
                            <!--<th style="width:5%">#</th>-->
                            <!--<th style="width:5%">Trans Id</th>-->
                            <th class="sorting_paging">@sortablelink('Beneficiary', 'Beneficiary')</th>
                            <th class="sorting_paging">@sortablelink('IBAN', 'IBAN')</th>
                            <th class="sorting_paging">@sortablelink('reason', 'Comment (Reason)')</th>
                            <th class="sorting_paging">@sortablelink('transactionSourceAmount', 'Amount')</th>
                            <th class="sorting_paging">@sortablelink('transactionTargetAmount', 'Fee')</th>
                            <th class="sorting_paging">@sortablelink('Total Amount', 'Total Amount')</th>
                            <th class="sorting_paging">@sortablelink('transactionId', 'Transaction ID')</th>
                            <th class="sorting_paging">@sortablelink('type', 'Transaction Type')</th>
                            <th class="sorting_paging">@sortablelink('status', 'Status')</th>
                            <th class="sorting_paging">@sortablelink('created_at', 'Transaction Date')</th>
                            <th class="action_dvv"> Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($allrecords as $allrecord)
                            <tr>
                                <td data-title="Beneficiary">
                                    {{ isset($allrecord->beneficiary) ? ucfirst($allrecord->beneficiary) : 'N/A' }}
                                </td>
                                <td data-title="IBAN">
                                    {{ isset($allrecord->iban) ? ucfirst($allrecord->iban) : 'N/A' }}
                                </td>
                                <td data-title="reason">
                                    @if (isset($allrecord->reason))
                                    {{ ucfirst($allrecord->reason) }}@else{{ 'N/A' }}
                                    @endif
                                </td>
                                <td data-title="Source Amount">
                                    {{ CURR }} {{  number_format((($allrecord->amount - floor($allrecord->amount)) > 0.5 ? ceil($allrecord->amount) : floor($allrecord->amount)), 0, '', ' ') ?? 0 }}
                                </td>
                                <td data-title="Source Amount">
                                    {{ CURR }} {{  number_format((($allrecord->transaction_amount - floor($allrecord->transaction_amount)) > 0.5 ? ceil($allrecord->transaction_amount) : floor($allrecord->transaction_amount)), 0, '', ' ') ?? 0 }}
                                </td>
                                <td data-title="Total Amount">

                                    {{ CURR }}{{ number_format((($v = $allrecord->amount + $allrecord->transaction_amount) - floor($v)) > 0.5 ? ceil($v) : floor($v), 0, '', ' ') }}

                                </td>
                                <td data-title="Transaction ID">{{ $allrecord->transactionId }}</td>
                                <td data-title="Type">{{ $allrecord->transactionType }}</td>
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
                                <td data-title="Transaction / Request Date">
                                    {{ $allrecord->created_at->format('M d, Y ') }}
                                </td>
                                <td data-title="Action">
                                    <a href="#info{!! $allrecord->id !!}" title="View Transaction Details"
                                        class="btn btn-primary btn-xs" rel='facebox'><i class="fa fa-eye"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <!--            <div class="search_frm">
                                                        <button type="button" name="chkRecordId" onclick="checkAll(true);"  class="btn btn-info">Select All</button>
                                                        <button type="button" name="chkRecordId" onclick="checkAll(false);" class="btn btn-info">Unselect All</button>
                                        <?php
        $accountStatus = [
            'Delete' => 'Delete',
        ];
                                        ?>
                                                        <div class="list_sel">{{ Form::select('action', $accountStatus, null, ['class' => 'small form-control', 'placeholder' => 'Action for selected record', 'id' => 'action']) }}</div>
                                                        <button type="submit" class="small btn btn-success btn-cons btn-info" onclick="return ajaxActionFunction();" id="submit_action">OK</button>
                                                    </div>    -->
            </div>
        </section>
        {{ Form::close() }}
    </div>
    </div>
@else
    <div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
    <div class="admin_no_record">No record found.</div>
@endif

<?php

use App\Models\Transaction;
?>
@if (!$allrecords->isEmpty())
    @foreach ($allrecords as $allrecord)
        <div id="info{!! $allrecord->id !!}" style="display: none;">
            <div class="nzwh-wrapper">
                <fieldset class="nzwh">
                    <legend class="head_pop">BDA Transactions Details</legend>
                    <div class="drt">
                        <div class="admin_pop"><span>Beneficiary: </span> <label>
                                {{ isset($allrecord->beneficiary) ? ucfirst($allrecord->beneficiary) : 'N/A' }}
                            </label>
                        </div>
                        <div class="admin_pop"><span>IBAN: </span> <label>
                                {{ $allrecord->iban }}
                            </label>
                        </div>
                        <div class="admin_pop"><span>Comment (Reason) : </span> <label>
                                {{ isset($allrecord->reason) ? ucfirst($allrecord->reason) : 'N/A' }}
                            </label>
                        </div>
                        <div class="admin_pop"><span>Amount: </span> <label>
                                {{CURR}} {{  number_format((($allrecord->amount - floor($allrecord->amount)) > 0.5 ? ceil($allrecord->amount) : floor($allrecord->amount)), 0, '', ' ') ?? 0 }}
                            </label>
                        </div>
                        <div class="admin_pop"><span>Fee: </span> <label>
                                {{CURR}} {{  number_format((($allrecord->transaction_amount - floor($allrecord->transaction_amount)) > 0.5 ? ceil($allrecord->transaction_amount) : floor($allrecord->transaction_amount)), 0, '', ' ') ?? 0 }}
                            </label>
                        </div>
                        <div class="admin_pop"><span>Transaction Type: </span> <label>
                                {{ $allrecord->transactionType }}
                            </label>
                        </div>

                        <div class="admin_pop"><span>Status: </span> <label>
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







                        <div class="admin_pop"><span>Transaction/Request Date: </span>
                            <label>{{ $allrecord->created_at->format('M d, Y ') }}</label>
                        </div>
                        <!-- <div class="admin_pop"><span>Transaction Process Date: </span>  <label>{{ $allrecord->updated_at->format('M d, Y h:i:s A') }}</label></div> -->

                </fieldset>
            </div>
        </div>
    @endforeach
@endif