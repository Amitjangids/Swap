{{ HTML::script('public/assets/js/facebox.js')}}
{{ HTML::style('public/assets/css/facebox.css')}}
@php
    use App\Http\Controllers\Admin\TransactionsController;
@endphp

<style>
    div#facebox .popup .alert-body form input[type="text"] {
        width: 100%;
        max-width: 150px;
    }

    .refund-value-box {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .refund-value-box span {
        margin-right: 20px;
        font-size: 16px;
    }

    div#facebox .popup .alert-body form .alert-btn button.btn.btn-alert {
        background: #4b2e74;
        padding: 7px 10px;
        min-width: 100px;
        color: #fff;
        font-size: 18px;
        border: 1px solid transparent;
        border-radius: 10px;
        text-align: center;
        transition: 0.4s;
        -webkit-transition: 0.4s;
    }

    div#facebox .popup .alert-body form .alert-btn button.btn.btn-alert:hover {
        background-color: transparent;
        border-color: #4b2e74;
        color: #4b2e74;
    }
</style>
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
                    <div class="topn_left">Referral Earning List</div>

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
                        <div class="payment-info-parent">
                            <div class="payment_info">
                                <span class="pay_head">Total Transaction</span>
                                <span class="pay_body">{{CURR }}&nbsp;
                                    {{ number_format(intval(str_replace(
                                    ',',
                                    '',
                                    $total->totalAmount
                                )), 0, '.', ' ') }}</span>
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
               /*  $('.export_excel').on('click', function () {
                    // alert('ok');
                    window.location.href = '{{HTTP_PATH}}/admin/transactions / export_excel';
                }); */


                $('.export_excel').on('click', function () {
                    var sender = $('input[name=sender]').val();
                    var sender_phone = $('input[name=sender_phone]').val();
                    var receiver = $('input[name=receiver]').val();
                    var receiver_phone = $('input[name=receiver_phone]').val();
                    var requestD = $('input[name=to]').val();

                    // Redirect to the export URL with query parameters
                    var url = '{{ route("exportexcelreferral") }}?sender=' + sender + '&sender_phone=' + sender_phone + '&receiver=' + receiver + '&receiver_phone=' + receiver_phone + '&to=' + requestD;
                    window.location.href = url;
                });

            </script>

            <div class="tbl-resp-listing">
                <table class="table table-bordered table-striped table-condensed cf">
                    <thead class="cf ddpagingshorting">
                        <tr>
                            <!--<th style="width:5%">#</th>-->
                            <!--<th style="width:5%">Trans Id</th>-->
                            <th class="sorting_paging">@sortablelink('User.name', 'Referrer Name')</th>
                            <th class="sorting_paging">@sortablelink('User.phone', 'Referrer Phone Number')</th>
                            <th class="sorting_paging">@sortablelink('Receiver.name', 'Referred Name')</th>
                            <th class="sorting_paging">@sortablelink('Receiver.phone', 'Referred Phone Number')</th>
                            <th class="sorting_paging">@sortablelink('payment_mode', 'Transaction For')</th>
                            <th class="sorting_paging">@sortablelink('amount', 'Amount')</th>
                            <th class="sorting_paging">@sortablelink('status', 'Status')</th>
                            <th class="sorting_paging">@sortablelink('created_at', 'Transaction Date')</th>
                            {{-- <th class="action_dvv"> Action</th> --}}
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allrecords as $allrecord)
                                        <tr>
                                            <td>
                                                <?php
                            echo $allrecord->sender_name ?? 'N/A';
                                            ?>
                                            </td>
                                            <td data-title="Sender Phone">
                                                <?php 
                                                    echo $allrecord->sender_phone ?? 'N/A'; ?>
                                            </td>
                                            <td data-title="Receiver Name">
                                                <?php
                            echo $allrecord->receiver_name;

                                                ?>
                                            </td>
                                            <td data-title="Receiver Phone">
                                                <?php
                            echo $allrecord->receiver_phone;
                                                ?>
                                            </td>

                                            <td data-title="Transaction For">
                                                <?php
                            echo $allrecord->payment_mode;
                                                ?>
                                            </td>
                                            <td data-title="Amount Paid">{{ CURR }}{{$allrecord->amount}}
                                            </td>
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
                                            <?php        //print_r($allrecord);  die;?>
                                            <td data-title="Transaction / Request Date">{{$allrecord->created_at}}
                                            </td>
                                            {{-- <td data-title="Action">
                                                <div id="loderstatus{{$allrecord->id}}" class="right_action_lo">
                                                    {{HTML::image("public/img/loading.gif", '')}}</div>
                                                <div class="btn-group">
                                                    <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                                        <i class="fa fa-list"></i>
                                                        <span class="caret"></span>
                                                    </button>
                                                </div>
                                            </td> --}}
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