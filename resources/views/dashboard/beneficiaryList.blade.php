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
    </style>

    <section class="tiles-section-wrapper customtiles">
        <div class="container">
            <h2>{{__('message.Beneficiary Accounts List')}}</h2>
            <div class="row mb-4 beneficiary-wrapper">
                <div class="col-lg-4 col-12 col-md-8 col-sm-8 col-xs-8">
                    <div class="search_box custom-daterangepicker">
                        <input type="text" class="form-control" id="CustomSearchTextField" placeholder="{{__('message.Search by keyword')}}">
                        <span class="fa fa-search"></span>
                    </div>
                </div>
                <div class="col-lg-3 col-12 col-md-6 col-sm-6 col-xs-6">
                    <div class="text_a custom-daterangepicker">
                        <input type="text" id="datefilter" name="datefilter" class="form-control" />
                        <span class="fa fa-calendar "></span>
                    </div>
                </div>
                <div class="col-lg-1 col-12 col-md-6 col-sm-6 col-xs-6">
                    <div class="text_a">
                        <button type="button" class="btn btn-danger reset">{{__('message.Reset')}}</button>
                    </div>
                </div>
                <div class="col-lg-4 col-12 col-md-8 col-sm-8 col-xs-8">
                    <div class="download_excel">
                        <a href="{{ HTTP_PATH }}/add-beneficiary" class="btn btn-primaryx beneficiary-btn">
                            <i class="fa fa-plus" aria-hidden="true"></i>{{__('message.Add Beneficiary')}}
                        </a>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="transition-history-wrapper">
                    <h2>{{__('message.Transaction History')}}</h2>
                    <div class="table-responsive">
                        <table id="exampleTable" class="table table-dark table-striped" width="100%">
                            <thead>
                                <tr>
                                    <th>{{__('message.First Name')}}</th>
                                    <th>{{__('message.Name')}}</th>
                                    <th>{{__('message.Country')}}</th>
                                    <th>{{__('message.CountryCode')}}</th>
                                    <th>{{__('message.Telephone')}}</th>
                                    <th>{{__('message.Wallet Manager')}}</th>
                                    <th>{{__('message.Created At')}}</th>
                                    <th>{{__('message.Action')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        $(document).ready(function() {


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
            // Initialize DataTable
            var table = $('#exampleTable').DataTable({
                processing: false,
                bFilter: false,
                searching: true,
                serverSide: true,
                lengthChange: false,
                order: [[0, 'desc']],
                ajax: {
                    url: "{{ HTTP_PATH }}/all-beneficiary-list",
                    type: 'GET',
                    dataType: "json",
                    dataSrc: function(response) {
                        return response.aaData;
                    },
                    data: function(d) {
                        d.daterange = $('#datefilter').val();
                        d.search = $('#CustomSearchTextField').val().trim();
                    }
                },
                columns: [
                    { data: 'first_name' },
                    { data: 'name' },
                    { data: 'country' },
                    { data: 'country_code' },
                    { data: 'telephone' },
                    { data: 'walletManager' },
                    { data: 'createdAt' },
                    { data: 'action' }
                ],
                columnDefs: [{
                    targets: [0],
                    orderable: false
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
                preDrawCallback: function(settings) {
                    $(".preloader").show();
                },
                drawCallback: function(settings) {
                    $(".preloader").hide();
                }
            });

            // Search functionality
            $("#CustomSearchTextField").keyup(function() {
                table.search($(this).val()).draw();
            });

            // Date range picker initialization
            $('input[name="datefilter"]').daterangepicker({
            autoUpdateInput: false,
            maxDate: moment(),
            locale: (language === 'fr') ? frenchLocale : englishLocale
        })


            // Event handler for applying date range selection
            $('input[name="datefilter"]').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
                table.draw();
            });

            $('input[name="datefilter"]').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                table.draw();
            });

            // Reset functionality
            $('.reset').on('click', function() {
                $('#CustomSearchTextField').val('');
                $('#datefilter').val('');
                $('input[name="datefilter"]').data('daterangepicker').setStartDate(moment().startOf('day'));
                $('input[name="datefilter"]').data('daterangepicker').setEndDate(moment().endOf('day'));
                $('input[name="datefilter"]').val('');
                table.draw();
            });

            // Toggle status function
            window.toggleStatus = function(obj) {
                let id = $(obj).data('item'),
                    status = Number($(obj).prop('checked'));

                $.ajax({
                    type: "POST",
                    url: `{{ HTTP_PATH }}/${id}/toggle-beneficiary-status/${status}`,
                    data: {_token: '{{ csrf_token() }}'},
                    success: function(response) {
                        if (response.status) {
                            table.draw();
                        }
                    }
                });
            };
        });
    </script>

    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

@endsection
