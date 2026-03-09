@extends('layouts.home')
@section('content')
<style type="text/css">
    .custom-daterangepicker {
        position: relative;
    }

    .custom-daterangepicker>span {
        position: absolute;
        top: 50%;
        right: 10px;
        transform: translateY(-50%);
        cursor: pointer;
        user-select: none;
        pointer-events: none;
    }

    .table-responsive div div#exampleTable_filter {
        display: none;
    }

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

    .text_a.custom-daterangepicker div#updateWalletManager span {
        display: none;
    }
</style>
<section class="tiles-section-wrapper customtiles custom-sucess-section">
    <div class="container">
    <h2>{{__('message.Number of Failure Transaction')}}<h2>
           <div class="row">
               <div class="col-lg-4">
                   <div class="small-box bg-green">
                        <div class="inner">
                            <h3 id='someElement'>0</h3>
                            <p>{{__('message.Number of  Failure')}}</p>
                        </div>
                    </div>
               </div>
           </div>

        <div class="row mb-4 custom-sucess-row">
            <div class="col-lg-3">
                <div class="search_box custom-daterangepicker">
                    <input type="text" class="form-control" id="CustomSearchTextField" placeholder="{{__('message.Search by keyword')}}">
                    <span class="fa fa-search"></span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="text_a custom-daterangepicker">
                    <!-- <input type="text" id="datepicker" placeholder="Choose Date" name="datefilter" readonly class="form-control"> -->
                    <input type="text" id="datefilter" name="datefilter" class="form-control" />
                    <span class="fa fa-calendar "></span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="text_a custom-daterangepicker">
                    <div class="sucess-dropdown-parent">

                        <select class="form-control" id="country_id">
                            <option value="">{{__('message.Select Country')}}</option>
                            <?php foreach ($country_list as $country) {  ?>
                                <option value="{{$country->id}}">{{$country->name}}</option>
                            <?php } ?>
                        </select>
                        <i class="fa fa-angle-down" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="text_a custom-daterangepicker">
                    <div class="sucess-dropdown-parent" id="updateWalletManager">
                        <select class="form-control required" id="wallet_manager_id">
                            <option value="">{{__('message.Select Wallet Manager')}}</option>
                            <?php foreach ($wallet_manager_list as $value) {  ?>
                                <option value="{{$value->id}}">{{$value->name}}</option>
                            <?php } ?>
                        </select>
                        <i class="fa fa-angle-down" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-1">
                <div class="text_a">
                    <button type="button" class="btn btn-danger reset">{{__('message.Reset')}}</button>
                </div>
            </div>
        </div>
    </div>
</section>
   <script>
           
            </script>

    <section class="same-section table-history">
       <div class="container">
           <div class="transition-history-wrapper">
           <div class="table-header-heading">
               <h2>{{__('message.Number of Failure Transaction')}}</h2>
               <div class="download_excel">
                    <a href="javascript:void(0)" class="btn btn-success export_excel">{{__('message.Download Excel')}} <i class="fa fa-file-excel-o" aria-hidden="true"></i></a>
                    </div>
        </div>    
               <div class="table-responsive">
               <table id='exampleTablePending' class="table table-dark table-striped" width="100%">  
               <thead>
                   <tr>
                        <th>{{__('message.First Name')}}</th>
                        <th>{{__('message.Last Name')}}</th>
                        <th>{{__('message.Comment')}}</th>
                        <th>{{__('message.Country')}}</th>
                        <th>{{__('message.Wallet Manager')}}</th>
                        <th>{{__('message.Phone Number')}}</th>
                        <th>{{__('message.Amount')}}</th>
                        <th>{{__('message.Submitted By')}}</th>
                        <th>{{__('message.Submitted Date')}}</th>
                        <th>{{__('message.Approved/Rejected By')}}</th>
                        <th>{{__('message.Approval 1/Rejected Date')}}</th>
                        <th>{{__('message.Merchant Approval/Rejected By')}}</th>
                        <th>{{__('message.Merchant Approval/Rejected Date')}}</th>
                        <th>{{__('message.Reason For Rejection')}}</th>
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
             order: [[3, 'asc']],
             ajax: {
                    url: "{{HTTP_PATH}}/failure-transaction-list",
                    type: "GET", // or "GET" depending on your server-side code
                    dataType: "json",
                    dataSrc: function(response) {
                        var iTotalRecords = response.iTotalRecords;
                        $('#someElement').html(iTotalRecords);
                        return response.aaData;
                    },
                    data: function (d) {
                    d.daterange = $('#datefilter').val();
                    d.search = $('#CustomSearchTextField').val();
                    d.country_id = $('#country_id').val();
                    d.wallet_manager_id = $('#wallet_manager_id').val();
                },
             },
             columns: [
                { data: 'first_name'},
                { data: 'name' },
                { data: 'comment'},
                { data: 'country_name'},
                { data: 'wallet_name'},
                { data: 'tel_number'},
                { data: 'amount'},
                { data: 'submitted_by'},
                { data: 'submitted_date'},
                { data: 'approved_by'},
                { data: 'approved_date'},
                { data: 'merchant_by'},
                { data: 'approved_merchant_date'},
                { data: 'remarks'},
             ],
            columnDefs: [ {
            targets: [5], // column index (start from 0)
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
        $('#country_id').change(function() {
            var selectedValue = $(this).val();
            $.ajax({
                url: '{{HTTP_PATH}}/getWalletmanagerList',
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    country_id: selectedValue
                },
                success: function(response) {
                    // Update wallet manager dropdown and reset the value
                    $('#wallet_manager_id').html(response).val(''); // Ensure dropdown is updated and reset

                    // Redraw DataTable based on the new country selection
                    oTable.draw();

                    // Re-attach the change event handler for wallet manager after it's dynamically updated
                    $('#wallet_manager_id').off('change').on('change', function() {
                        oTable.draw(); // Trigger redraw when wallet manager is changed
                    });
                }
            });
        });


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
        var country_id = $('#country_id').val();
        var wallet_manager_id = $('#wallet_manager_id').val();

        // Redirect to the export URL with query parameters
        var url = '{{ route("FailerTransactionExcel") }}?search=' + searchValue + '&daterange=' + dateRange+ '&country_id=' + country_id+ '&wallet_manager_id=' + wallet_manager_id;
        window.location.href = url;
       });
    
        $('.reset').on('click', function() {
            $('#CustomSearchTextField').val('');
            $('#datefilter').val('');

            $('input[name="datefilter"]').data('daterangepicker').setStartDate(moment().startOf('day'));
            $('input[name="datefilter"]').data('daterangepicker').setEndDate(moment().endOf('day'));
        
            $('input[name="datefilter"]').val('');  // Clears the input display

            $('#country_id').val('');
            $('#wallet_manager_id').val('');


            oTable.draw();
        });




        });

        $('.export_excel').on('click',function(){
                // alert('ok');
                window.location.href='{{HTTP_PATH}}/failer_export_excel';
            });
</script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
@endsection