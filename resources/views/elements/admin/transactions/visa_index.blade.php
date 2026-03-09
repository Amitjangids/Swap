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
                    <div class="topn_left">VISA Transactions List</div>

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
                                    class="pay_body">{{ CURR }}{{ intval(str_replace(',', '', $totalAmount['total_amount'])) }}</span>
                            </div>
                            <div class="payment_info">
                                <span class="pay_head">Total Earning</span>
                                <span
                                    class="pay_body">{{ CURR }}{{intval(str_replace(',', '', $totalAmount['total_fee'])) }}</span>
                            </div>
                        </div>
                        <div class="download_excel">
                            <a href="javascript:void(0)" class="btn btn-success export_excel">
                                Download Excel
                                <i class="fa fa-file-excel-o" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>


            <script>
                $('.export_excel').on('click', function () {

                    var name = $('input[name=name]').val();
                    var phone = $('input[name=phone]').val();
                    var refrence = $('input[name=refrence]').val();
                    var requestD = $('input[name=to]').val();
                    var accountId = $('input[name=accountId]').val();
                    var url = '{{ route("exportexcelvisa") }}?name=' + name + '&phone=' + phone + '&refrence=' + refrence + '&to=' + requestD + '&accountId=' + accountId;
                    window.location.href = url;
                });

            </script>


            <div class="tbl-resp-listing">
                <table class="table table-bordered table-striped table-condensed cf">
                    <thead class="cf ddpagingshorting">
                        <tr>
                            <!--<th style="width:5%">#</th>-->
                            <!--<th style="width:5%">Trans Id</th>-->
                            <th class="sorting_paging">@sortablelink('Name', 'Name')</th>
                            <th class="sorting_paging">@sortablelink('Phone', 'Phone')</th>
                            <th class="sorting_paging">@sortablelink('Account ID', 'Account ID')</th>
                            <th class="sorting_paging">@sortablelink('transactionSourceAmount', 'Amount')</th>
                            <th class="sorting_paging">@sortablelink('transactionTargetAmount', 'Transaction Fee')</th>
                            <th class="sorting_paging">@sortablelink('Total Amount', 'Total Amount')</th>
                            <th class="sorting_paging">@sortablelink('Balance', 'Balance')</th>
                            <th class="sorting_paging">@sortablelink('payment_mode', 'Transaction For')</th>
                            <th class="sorting_paging">@sortablelink('transactionId', 'Transaction ID')</th>
                            <!-- <th class="sorting_paging">@sortablelink('type', 'Transaction Type')</th> -->
                            <th class="sorting_paging">@sortablelink('status', 'Status')</th>
                            <th class="sorting_paging">@sortablelink('created_at', 'Transaction /Request Date')</th>
                            <th class="action_dvv"> Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($allrecords as $allrecord)
                                                <tr>
                                                    <td data-title="Name">
                                                        {{ isset($allrecord->name) ? ucfirst($allrecord->name) : 'N/A' }}
                                                    </td>
                                                    <td data-title="Phone">
                                                        {{ isset($allrecord->phone) ? ucfirst($allrecord->phone) : 'N/A' }}
                                                    </td>
                                                    <td data-title="Account ID">
                                                        {{ isset($allrecord->accountId) ? $allrecord->accountId : 'N/A' }}
                                                    </td>


                                                    <td data-title="Amount Paid">
                                                        <!-- {{ CURR }}{{$allrecord->amount}} -->

                                                        <?php
                                    $rawAmount = (
                                        $allrecord->payment_mode === "TRANSAFEROUT" ||
                                        $allrecord->payment_mode === "CARDPAYMENT" ||
                                        $allrecord->description === "Card Issuance \ Activation Fee" ||
                                        $allrecord->description === "Denial - ATM Withdrawal"
                                    )
                                        ? (
                                            in_array($allrecord->description, [
                                                "Denial - POS Purchase",
                                                "Manual PIN Change",
                                                "Card Issuance \ Activation Fee",
                                                "Denial - ATM Withdrawal"
                                            ])
                                            ? $allrecord->transaction_amount
                                            : $allrecord->amount
                                        )
                                        : $allrecord->amount;

                                    // custom rounding: .5 stays same, > .5 goes up
                                    $value = (($rawAmount - floor($rawAmount)) > 0.5)
                                        ? ceil($rawAmount)
                                        : floor($rawAmount);

                                    echo CURR . number_format($value, 0, '', ' ');
                                                                                                                                                                            ?>
                                                    </td>
                                                    <td data-title="Source Amount">
                                                        @if($allrecord->description === "Card Load")
                                                            {{ '-' . $allrecord->transaction_amount ?? 0 }} {{ CURR }}
                                                        @else
                                                            {{ CURR }}
                                                            {{ number_format((($allrecord->transaction_amount - floor($allrecord->transaction_amount)) > 0.5 ? ceil($allrecord->transaction_amount) : floor($allrecord->transaction_amount)), 0, '', ' ') ?? 0 }}
                                                        @endif
                                                    </td>
                                                    <td data-title="Total Amount">
                                                        @if($allrecord->description === "Card Load")
                                                                        {{ CURR }}
                                                                        {{ number_format((($allrecord->amount - $allrecord->transaction_amount) - floor($allrecord->amount - $allrecord->transaction_amount)) > 0.5
                                                            ? ceil($allrecord->amount - $allrecord->transaction_amount)
                                                            : floor($allrecord->amount - $allrecord->transaction_amount), 0, '', ' ') }}

                                                        @else
                                                            {{ CURR }}{{ number_format((($v = $allrecord->amount + $allrecord->transaction_amount) - floor($v)) > 0.5 ? ceil($v) : floor($v), 0, '', ' ') }}

                                                        @endif

                                                    </td>
                                                    <td data-title="Balance">
                                                        <?php
                                    $val = isset($allrecord->runningBalance) ? $allrecord->runningBalance : $allrecord->runningBalance;
                                    $val = (($val - floor($val)) > 0.5) ? ceil($val) : floor($val);

                                    echo CURR . number_format($val, 0, '', ' ');
                                                                                                                ?>
                                                    </td>
                                                    <td data-title="Transaction For">
                                                        <?php 
                                                                                                                                                    echo $allrecord->description == "Denial - POS Purchase" ? "POS Denial" : $allrecord->description;
                                    /* if ($allrecord->payment_mode == "TRANSAFEROUT") {
                                        if ($allrecord->trans_type == 2) {
                                            echo "Transfer Out";
                            } else if ($allrecord->trans_type == 1) {
                            echo "Money Received from Card";
                            }
                            } else if ($allrecord->payment_mode == "CARDPAYMENT") {
                            if ($allrecord->trans_type == 2) {
                            echo "Card Recharge";
                            } else if ($allrecord->trans_type == 1) {
                            echo "Transfer IN";
                            }
                            } else {
                            echo $allrecord->payment_mode;
                            }  */
                                                                                                                                                                                                                                            ?>
                                                    </td>
                                                    <td data-title="Transaction ID">
                                                        {{  $allrecord->transactionId == 0 ? $allrecord->id : $allrecord->transactionId }}
                                                    </td>
                                                    <!-- <td data-title="Type">
                                                                                                                                                                                                                                {{ $allrecord->payment_mode }}
                                                                                                                                                                                                                            </td> -->
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
                                                        {{$allrecord->created_at->format('M d, Y h:i:s A')}}
                                                    </td>
                                                    <td data-title="Action">
                                                        <a href="#info{!! $allrecord->id !!}" title="View Transaction Details"
                                                            class="btn btn-primary btn-xs" rel='facebox'><i class="fa fa-eye"></i></a>
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

<?php

use App\Models\Transaction;
?>
@if (!$allrecords->isEmpty())
    @foreach ($allrecords as $allrecord)
        <div id="info{!! $allrecord->id !!}" style="display: none;">
            <div class="nzwh-wrapper">
                <fieldset class="nzwh">
                    <legend class="head_pop">VISA Transactions Details</legend>
                    <div class="drt">
                        <div class="admin_pop"><span>Name : </span> <label>
                                {{ isset($allrecord->name) ? ucfirst($allrecord->name) : 'N/A' }}
                            </label>
                        </div>
                        <div class="admin_pop"><span>Phone : </span> <label>
                                {{ isset($allrecord->phone) ? ucfirst($allrecord->phone) : 'N/A' }}
                            </label>
                        </div>
                        <div class="admin_pop"><span>Amount: </span> <label>
                                <td data-title="Amount Paid">
                                    <!-- {{ CURR }}{{$allrecord->amount}} -->

                                    <?php
                $rawAmount = (
                    $allrecord->payment_mode === "TRANSAFEROUT" ||
                    $allrecord->payment_mode === "CARDPAYMENT" ||
                    $allrecord->description === "Card Issuance \ Activation Fee" ||
                    $allrecord->description === "Denial - ATM Withdrawal"
                )
                    ? (
                        in_array($allrecord->description, [
                            "Denial - POS Purchase",
                            "Manual PIN Change",
                            "Card Issuance \ Activation Fee",
                            "Denial - ATM Withdrawal"
                        ])
                        ? $allrecord->transaction_amount
                        : $allrecord->amount
                    )
                    : $allrecord->amount;

                // custom rounding: .5 stays same, > .5 goes up
                $value = (($rawAmount - floor($rawAmount)) > 0.5)
                    ? ceil($rawAmount)
                    : floor($rawAmount);

                echo CURR . number_format($value, 0, '', ' ');
                                                            ?>
                                </td>
                            </label>
                        </div>
                        <div class="admin_pop"><span>Transaction Fee: </span> <label>
                                @if($allrecord->description === "Card Load")
                                                            {{ '-' . $allrecord->transaction_amount ?? 0 }} {{ CURR }}
                                                        @else
                                                            {{ CURR }}
                                                            {{  number_format((($allrecord->transaction_amount - floor($allrecord->transaction_amount)) > 0.5 ? ceil($allrecord->transaction_amount) : floor($allrecord->transaction_amount)), 0, '', ' ') ?? 0 }}
                                                        @endif
                            </label>
                        </div>
                        <div class="admin_pop"><span>Total Amount: </span> <label>

                                @if($allrecord->description === "Card Load")
                                            {{ CURR }}
                                            {{ number_format((($allrecord->amount - $allrecord->transaction_amount) - floor($allrecord->amount - $allrecord->transaction_amount)) > 0.5
                                    ? ceil($allrecord->amount - $allrecord->transaction_amount)
                                    : floor($allrecord->amount - $allrecord->transaction_amount), 0, '', ' ') }}

                                @else
                                    {{ CURR }}{{ number_format((($v = $allrecord->amount + $allrecord->transaction_amount) - floor($v)) > 0.5 ? ceil($v) : floor($v), 0, '', ' ') }}

                                @endif
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

                        @if($allrecord->payment_mode == 'CARDPAYMENT' || $allrecord->payment_mode == 'TRANSAFEROUT')
                            <div class="admin_pop"><span>Transaction ID: </span>
                                <label>{{  $allrecord->transactionId == 0 ? $allrecord->id : $allrecord->transactionId }}</label>
                            </div>
                            <div class="admin_pop"><span>Account ID: </span>
                                <label>{{$allrecord->accountId}}</label>
                            </div>
                            @if($allrecord->transactionDate)
                                <div class="admin_pop"><span>Transaction Date: </span>
                                    <label>{{$allrecord->transactionDate}}</label>
                                </div>
                            @endif
                            @if($allrecord->transactionTime)
                                <div class="admin_pop"><span>Transaction Time: </span>
                                    <label>{{$allrecord->created_at->format('h:i:s')}}</label>
                                </div>
                            @endif
                            @if($allrecord->merchantName)
                                <div class="admin_pop"><span>Merchant Name: </span>
                                    <label>{{$allrecord->merchantName}}</label>
                                </div>
                            @endif
                            @if($allrecord->merchantCountry)
                                <div class="admin_pop"><span>Merchant Country: </span>
                                    <label>{{$allrecord->merchantCountry}}</label>
                                </div>
                            @endif
                        @else
                            <div class="admin_pop"><span>Transaction/Request Date: </span>
                                <label>{{$allrecord->created_at->format('M d, Y h:i:s A')}}</label>
                            </div>
                        @endif
                        <!-- <div class="admin_pop"><span>Transaction Process Date: </span>  <label>{{ $allrecord->updated_at->format('M d, Y h:i:s A') }}</label></div> -->

                </fieldset>
            </div>
        </div>
    @endforeach
@endif