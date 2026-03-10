@extends('layouts.home')
@section('content')
<style type="text/css">
    .custom-daterangepicker{position: relative;}
    .custom-daterangepicker > span{position: absolute; top: 50%; right: 10px; transform: translateY(-50%); cursor: pointer; user-select: none; pointer-events: none;}
    .table-responsive div div#exampleTable_filter {display: none;}
    .table-header-heading h2 {
    margin: 0;
    }
        .table-header-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 0 0 20px;
    }
    .table-header-heading .download_excel {
        margin: 0;
    }
    .btn:focus {
            box-shadow: none !important;
            outline: none !important;
        }
</style>
<section class="tiles-section-wrapper customtiles">
   <div class="container">
    <h2>{{__('message.Customer Accounting')}}</h2>
    <div class="row">
       <div class="col-lg-4">
           <div class="small-box bg-green">
            <div class="inner">
                <h3 id='someElement'>{{ CURR }}{{ number_format(intval(str_replace(',', '', $wallet_balance)), 0, '.', ',') }}
                </h3>
                <p>{{__('message.Wallet Balance')}}</p>
            </div>
        </div>
    </div>
</div>
<div class="row mb-4">
   <div class="col-lg-4 col-12 col-md-8 col-sm-8 col-xs-8">
     <div class="search_box custom-daterangepicker">
       <input type="text" class="form-control" id="CustomSearchTextField" placeholder="{{__('message.Search by Amount , Transaction type, balance')}}">
       <span class="fa fa-search"></span>
   </div>
</div>
<div class="col-lg-3 col-12 col-md-6 col-sm-6 col-xs-6">
  <div class="text_a custom-daterangepicker">
      <!-- <input type="text" id="datepicker" placeholder="Choose Date" name="datefilter" readonly class="form-control"> -->
      <input type="text" id="datefilter" name="datefilter" class="form-control" />
      <span class="fa fa-calendar "></span>
  </div>
</div>
<div class="col-lg-1 col-12 col-md-6 col-sm-6 col-xs-6">
  <div class="text_a">
    <button type="button" class="btn btn-danger reset">{{__('message.Reset')}}</button>
</div>
</div>
</div>
</div>
</section>


<section class="same-section table-history">
    <div class="container">
        <div class="transition-history-wrapper">
        <div class="table-header-heading">
            <h2>{{__('message.Accounting History')}}</h2>
            <div class="download_excel">
                <a href="javascript:void(0)" class="btn btn-success export_excel">{{__('message.Download Excel')}} <i class="fa fa-file-excel-o" aria-hidden="true"></i></a>
            </div>
            </div>
            <div class="table-responsive accounting-section">
                <table id='exampleTablePending' class="table table-dark table-striped" width="100%">  
                    <thead>
                        <tr>
                            <th>{{__('message.Arise')}}</th>
                            <th>{{__('message.Date Of Transaction')}}</th>
                            <th>{{__('message.Amount')}}</th>
                            <th>{{__('message.Transaction Type')}}</th>
                            <th>{{__('message.Balance')}}</th>
                            <th>{{__('message.Action')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>       
                </table>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
    $(document).ready(function(){

        var language = "{{ Session::get('locale') }}";

// Locale settings
var frenchLocale = {
    format: 'DD/MM/YYYY',
    cancelLabel: 'Effacer',
    applyLabel: 'Appliquer',
    fromLabel: 'De',
    toLabel: 'À',
    customRangeLabel: 'Plage personnalisée',
    daysOfWeek: ['Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa'],
    monthNames: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
};

var englishLocale = {
    format: 'DD/MM/YYYY',
    cancelLabel: 'Clear',
    applyLabel: 'Apply',
    fromLabel: 'From',
    toLabel: 'To',
    customRangeLabel: 'Custom Range',
    daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
    monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
};
          // DataTable
        var oTable = $('#exampleTablePending').DataTable({
            processing: false,
            bFilter:false,
            searching: false, 
            serverSide: true,
            lengthChange: false,

            //pageLength: 1,
            order: [[2, 'asc']],
            ajax: {
                url: "{{HTTP_PATH}}/get-deposit-list",
                type: "GET", // or "GET" depending on your server-side code
                dataType: "json",
                dataSrc: function(response) {
                    return response.aaData;
                },
                data: function (d) {
                    d.daterange = $('#datefilter').val();
                    d.search = $('#CustomSearchTextField').val();
                }, 
            },
            columns: [
                { data: 'name' },
                { data: 'created_at' },
                { data: 'actual_amount'},
                { data: 'type'},
                { data: 'balance'},
                { data: 'action'},
                ],
            columnDefs: [ {
            orderable: false, // set orderable false for selected columns
        }],
        language: {
                paginate: {
                    previous: "{{ __('message.previous') }}",
                    next: "{{ __('message.next') }}"
                },
            
                info: "{{ __('message.showing') }} _START_ {{ __('message.to') }} _END_ {{ __('message.of') }} _TOTAL_ {{ __('message.entries') }}",
                infoEmpty: "{{ __('message.showing') }} _START_ {{ __('message.to') }} _END_ {{ __('message.of') }} _TOTAL_ {{ __('message.entries') }}", // Custom text for empty tables
                // lengthMenu: "{{ __('message.show') }} _MENU_ {{ __('message.entries') }}", // "Show X entries" text
                // search: "{{ __('message.search') }}", // Label for the search box
                zeroRecords: "{{ __('message.No data available in table') }}" // Custom text for no matching records
            },
        });


        $('#CustomSearchTextField').keyup(function(){
            oTable.search($(this).val()).draw() ;
        })


        $('input[name="datefilter"]').daterangepicker({
            autoUpdateInput: false,
            maxDate: moment(),
            locale: (language === 'fr') ? frenchLocale : englishLocale
        });

        $('input[name="datefilter"]').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            oTable.draw();
        });

        $('input[name="datefilter"]').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            $('input[name="datefilter"]').data('daterangepicker').setStartDate(moment().startOf('day'));
            $('input[name="datefilter"]').data('daterangepicker').setEndDate(moment().endOf('day'));
            oTable.draw();
        });
      

        $('.export_excel').on('click', function() {
        var searchValue = $('#CustomSearchTextField').val();
        var dateRange = $('#datefilter').val();

        // Redirect to the export URL with query parameters
        var url = '{{ route("CustomerExportExcel") }}?search=' + searchValue + '&daterange=' + dateRange;
        window.location.href = url;
       });

        $('.reset').on('click', function() {
            $('#CustomSearchTextField').val('');
            $('#datefilter').val('');

            $('input[name="datefilter"]').data('daterangepicker').setStartDate(moment().startOf('day'));
            $('input[name="datefilter"]').data('daterangepicker').setEndDate(moment().endOf('day'));
        
            $('input[name="datefilter"]').val('');  // Clears the input display

            oTable.draw();
        });



    });
    function cancelRequest(id)
    {
        $('#rejectform')[0].reset();
        $('#rejectform').attr('action','{{HTTP_PATH}}/reject-request/'+id); 
    }

    $('.export_excel').on('click',function(){
                // alert('ok');
        window.location.href='{{HTTP_PATH}}/customer_export_excel';
    });
</script>

<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>



<script>
    $(document).ready(function () {
        // Listen for the modal show event
        $('#transList').on('show.bs.modal', function (event) {
            // Get the link that triggered the modal
            var triggerLink = $(event.relatedTarget);
            // Get the URL from the link's href attribute
            var url = triggerLink.attr('href');
            var user_id = triggerLink.data('user_id');
            var bdastatus = triggerLink.data('bdastatus');
            var typeText = triggerLink.data('typestatus');
            console.log(typeText);
            if ($.fn.DataTable.isDataTable('#exampleTableExpend')) {
                    // If yes, destroy the existing DataTable instance
                $('#exampleTableExpend').DataTable().destroy();
            }
            var oTable = $('#exampleTableExpend').DataTable({
                processing: false,
                bFilter: false,
                searching: false,
                serverSide: true,
                lengthChange: false,
                order: [[0, 'desc']],
                ajax: "{{HTTP_PATH}}/get-transaction-details/"+url+"/"+user_id+"?type="+typeText,
                columns: [
                    { data: 'sender'},
                    { data: 'sender_phone' },
                    { data: 'receiver'},
                    { data: 'receiver_phone'},
                    { data: 'trans_type'},
                    { data: 'beneficiary'},
                    { data: 'iban'},
                    { data: 'received_amount'},
                    { data: 'reason'},
                    { data: 'transaction_fees'},
                    { data: 'trans_amount_value'},
                    { data: 'trans_id'},
                    { data: 'trans_status'},
                    { data: 'trans_date'},
                    ],
                columnDefs: [
                {
                    //targets: [7], // column index (start from 0)
                    orderable: false, // set orderable false for selected columns
                },
                {
                    targets: [0,1,2,3,4],
                    visible: bdastatus !== 'BDA',
                },
                {
                    targets: [5,6,8],
                    visible: bdastatus !== 'ONAFRIQ',
                }
                ],
                language: {
                paginate: {
                    previous: "{{ __('message.previous') }}",
                    next: "{{ __('message.next') }}"
                },
            
                info: "{{ __('message.showing') }} _START_ {{ __('message.to') }} _END_ {{ __('message.of') }} _TOTAL_ {{ __('message.entries') }}",
                infoEmpty: "{{ __('message.showing') }} _START_ {{ __('message.to') }} _END_ {{ __('message.of') }} _TOTAL_ {{ __('message.entries') }}", // Custom text for empty tables
                // lengthMenu: "{{ __('message.show') }} _MENU_ {{ __('message.entries') }}", // "Show X entries" text
                // search: "{{ __('message.search') }}", // Label for the search box
                zeroRecords: "{{ __('message.No data available in table') }}" // Custom text for no matching records
            },
            });
        });
    });
</script>


<div class="modal fade upload-modal" id="transList" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered excel_trnas_list_model">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="modal-header">
        {{__('message.Transaction History')}}
        </div>
    </div>

    <div class="modal-body">
      <table id="exampleTableExpend">
        <thead>
            <tr>
                <th>{{__('message.Sender Name')}}</th>
                <th>{{__('message.Sender Phone')}}</th>
                <th>{{__('message.Receiver Name')}}</th>
                <th>{{__('message.Receiver Phone')}}</th>
                <th>{{__('message.Transaction For')}}</th>

                <th>{{__('message.Beneficiary')}}</th>
                <th>{{__('message.IBAN')}}</th> 
                <th>{{__('message.Amount')}}</th>
                <th>{{__('message.Transaction For(Reason)')}} </th>


                <th>{{__('message.Transaction Fee')}}</th>
                <th>{{__('message.Total Amount')}}</th>
                <th>{{__('message.Transaction ID')}}</th>
                <th>{{__('message.Transaction Status')}}</th>
                <th>{{__('message.Transaction Process Date')}}</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
</div>
</div>
</div>




@endsection