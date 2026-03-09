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
<!-- <style>
    .text-right-nav {
    text-align: right;
}
</style> -->
<div class="admin_loader" id="loaderID">{{HTML::image("public/img/website_load.svg", '')}}</div>
@if(!$allrecords->isEmpty())
<div class="panel-body marginzero">
    <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
    {{ Form::open(array('method' => 'post', 'id' => 'actionFrom')) }}
    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="manage_sec">
                <div class="topn_left"> Gimac Transactions List</div>

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
                            <span class="pay_body">{{ CURR }}
                                {{  number_format((($totalAmount['total_amount'] - floor($totalAmount['total_amount'])) > 0.5 ? ceil($totalAmount['total_amount']) : floor($totalAmount['total_amount'])), 0, '', ' ') ?? 0 }}
                            </span>
                        </div>
                        <div class="payment_info">
                        <span class="pay_head">Total Earning</span>
                            <span class="pay_body">{{ CURR }} 
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
                    
                    var sender = $('input[name=sender]').val(); 
                    var receiver = $('input[name=receiver]').val();
                    var receiver_phone = $('input[name=receiver_phone]').val();
                    var type = $('select[name=type]').val(); 
                    var from = $('input[name=to]').val(); 
                    var to = $('input[name=to1]').val(); 
                    // Redirect to the export URL with query parameters
                    var url = '{{ route("exportexcelgimac") }}?sender=' + sender + '&receiver=' + receiver + '&receiver_phone=' + receiver_phone +'&type=' + type+'&to=' + from+'&to1=' + to;
                    window.location.href = url;
                });

            </script>


        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        
                        <th class="sorting_paging">@sortablelink('First Name', 'First Name')</th>
                        <th class="sorting_paging">@sortablelink('Name', 'Name')</th>
                        <th class="sorting_paging">@sortablelink('Country', 'Country')</th>
                        <th class="sorting_paging">@sortablelink('Wallet Manager', 'Wallet Manager ')</th>
                        <th class="sorting_paging">@sortablelink('Tel. No', 'Tel. No')</th>
                        <th class="sorting_paging">@sortablelink('Amount ', 'Amount ')</th>
                        <th class="sorting_paging">@sortablelink('Fee', 'Fee')</th>
                        <th class="sorting_paging">@sortablelink('Issuertrxref', 'Issuertrxref No')</th>
                        <th class="sorting_paging">@sortablelink('Status', 'Status')</th>
                        <th class="sorting_paging">@sortablelink('Created', 'Created')</th>
                        
                    </tr>
                </thead>
                <tbody>
                @foreach($allrecords as $allrecord) 
    <tr>
        
        <td data-title="First Name">{{ isset($allrecord->ExcelTransaction->first_name) ? ucfirst($allrecord->ExcelTransaction->first_name) : 'N/A' }}</td>
        <td data-title="Name">{{ isset($allrecord->ExcelTransaction->name) ? ucfirst($allrecord->ExcelTransaction->name) : 'N/A' }}</td>
        <td data-title="Country Name">{{ isset($allrecord->ExcelTransaction->country->name) ? ucfirst($allrecord->ExcelTransaction->country->name) : 'N/A' }}</td>
        <td data-title="Wallet Manager">{{ isset($allrecord->ExcelTransaction->walletManager->name) ? ucfirst($allrecord->ExcelTransaction->walletManager->name) : 'N/A' }}</td>
        <td data-title="Tel No ">{{ isset($allrecord->ExcelTransaction->tel_number) ? ucfirst($allrecord->ExcelTransaction->tel_number) : 'N/A' }}</td>
        <td data-title="Amount ">₣ 
            {{  number_format((($allrecord->amount_value - floor($allrecord->amount_value)) > 0.5 ? ceil($allrecord->amount_value) : floor($allrecord->amount_value)), 0, '', ' ') ?? 0 }}
        </td>
        <td data-title="Fee">₣ 
            {{  number_format((($allrecord->transaction_amount - floor($allrecord->transaction_amount)) > 0.5 ? ceil($allrecord->transaction_amount) : floor($allrecord->transaction_amount)), 0, '', ' ') ?? 0 }}
        </td>
        <td data-title="Issuertrxref No">{{$allrecord->issuertrxref}}</td>
        <td data-title="Status">
                @if($allrecord->is_verified_by_gimac == '0')
                    Not verified
                @elseif($allrecord->is_verified_by_gimac == '1')
                    Verified
                @endif
            </td>
        <td data-title="Transaction / Request Date">{{$allrecord->created_at->format('M d, Y ')}}</td>
        
    </tr>
@endforeach

                </tbody>
            </table>
          
        </div>
    </section>
    {{ Form::close()}}
    <!-- <div class="text-right-nav">
    {{ $allrecords->links() }}
    </div> -->
</div>         
</div> 
@else 
<div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
<div class="admin_no_record">No record found.</div>
@endif



