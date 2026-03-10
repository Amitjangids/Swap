@extends('layouts.home')
@section('content')
<style>
   .approve-btn.disabled {
    /* Add styles to visually indicate that the button is disabled */
    opacity: 0.5; /* Example: reduce opacity */
    pointer-events: none; /* Disable pointer events to prevent further clicks */
    /* Add any other styles to indicate that the button is disabled */
}
</style>
<div id="loader-wrapper">
    <div id="loader-content">
        <div id="loader"></div>
        <div id="loader-text">{{__('message.Transaction In Progress')}}</div> 
    </div>
</div>
<div class="container">
       <?php if (session()->has('success_message')) { ?>
        <div class="alert alert-success" role="alert">
            {{Session::get('success_message')}}
        </div>
        <?php Session::forget('success_message'); } ?>

        <?php if (session()->has('error_message')) { ?>
            <div class="alert alert-danger" role="alert">
            {{Session::get('error_message')}}
            </div>
        <?php Session::forget('error_message');   } ?>

<section class="same-section table-history">
       <div class="container">
           <div class="transition-history-wrapper">
               <h2>{{__('message.Pending Approvals')}}</h2>
               <div class="table-responsive pending-section">
               <table id='exampleTablePending' class="table table-dark table-striped" width="100%">  
               <thead>
                   <tr>
                       <th>{{__('message.Txn. Reference number')}}</th>
                       <th>{{__('message.Purpose of payment')}}</th>
                       <!-- <th>File name</th> -->
                       <th>{{__('message.No. of transactions')}}</th>
                       <th>{{__('message.Initiation date')}}</th>
                       <th>{{__('message.Amount')}}</th>
                       <th>{{__('message.Fees')}}</th>
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


 var oTable = $('#exampleTablePending').DataTable({
            processing: false,
            bFilter: false,
            searching: false,
            serverSide: true,
            lengthChange: false,
            order: [[4, 'desc']],
            ajax: "{{HTTP_PATH}}/pending-get-excel-list",
            columns: [
                { data: 'reference_id'},
                { data: 'remarks' },
                // { data: 'excel'},
                { data: 'no_of_records'},
                { data: 'updated_at'},
                { data: 'totat_amount'},
                { data: 'total_fees'},
                { data: 'action'},
            ],
            columnDefs: [
                {
                    targets: [5], // column index (start from 0)
                    orderable: false, // set orderable false for selected columns
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

    function cancelRequestSubmitter(id)
    {
        $('#rejectformSbumitter')[0].reset();
        $('#rejectformSbumitter').attr('action','{{HTTP_PATH}}/reject-request/'+id); 
    }

    
    function cancelRequest(id)
    {
        $('#rejectform')[0].reset();
        $('#rejectform').attr('action','{{HTTP_PATH}}/reject-request/'+id); 
    }
    function approveRequest(id)
    {
        $('#loader-wrapper').show();
        var url = '{{HTTP_PATH}}/approve-excel/' + id;
        window.location.href = url;
    }
    

// function approveExcelLink(link) {
//     // Your function logic goes here
//     console.log("Function called");
// }




</script>
<script>
    // document.addEventListener('DOMContentLoaded', function() {
    //     document.querySelectorAll('.approve-excel').forEach(function(button) {
    //         button.addEventListener('click', function() {
    //             button.disabled = true; // Disable the button when clicked
    //         });
    //     });
    // });
</script>

<script>


    // // Assuming you have jQuery included

    // // Wait for the document to be ready
    // $(document).ready(function() {
    //     // Attach a click event listener to the anchor tag
    //     $('#approveExcelLink').click(function(e) {
    //         // Prevent the default action of the anchor tag
    //         e.preventDefault();
            
    //         // Remove the icon from the anchor tag
    //         $(this).find('i').remove();
    //     });
    // });


//     document.addEventListener("DOMContentLoaded", function() {
//     var approveButton = document.getElementById('approveExcelLink');
//     approveButton.addEventListener("click", function(event) {
    //     // Prevent the default action of the link (i.e., following the href)
//         event.preventDefault();

    //     // Perform any action you want to take when the button is clicked
    //     // For example, you can make an AJAX request here to approve the Excel

    //     // After the action is completed, you can either remove or disable the button
    //     // In this example, let's remove the button
//         approveButton.parentNode.removeChild(approveButton);

    //     // If you want to disable the button instead of removing it, you can use:
//          approveButton.classList.add("disabled");
//     });
//});

document.getElementById("approveExcelLink").addEventListener("click", function(event) {
    event.preventDefault();
    var button = this;
    button.disabled = true; // Disable the button

    document.getElementById("loader-wrapper").style.display = "block";

    // Simulate an async action (e.g., an AJAX request)
    setTimeout(function() {
        button.disabled = false; // Re-enable the button
    }, 3000); // Re-enable after 3 seconds (for demonstration)
});



</script>

<script>
    $(document).ready(function () {
        // Listen for the modal show event
        $('#transList').on('show.bs.modal', function (event) {
            // Get the link that triggered the modal
            var triggerLink = $(event.relatedTarget);
            // Get the URL from the link's href attribute
            var url = triggerLink.attr('href');
            var bdastatus = triggerLink.data('bdastatus');

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
            ajax: "{{HTTP_PATH}}/get-excel-record/"+url,
            columns: [
                { data: 'first_name'},
                { data: 'name' },
                { data: 'comment'},
                { data: 'country_name'},
                { data: 'wallet_name'},
                { data: 'tel_number'},
                { data: 'beneficiary'},
                { data: 'iban'},
                { data: 'amount'},
                { data: 'reason'},
                { data: 'submitted_by'},
                { data: 'approved_by'},
                { data: 'created_at'},
                { data: 'gimac_status'},
                { data: 'remarks'},
            ],
            columnDefs: [
                {
                    //targets: [7], // column index (start from 0)
                    orderable: false, // set orderable false for selected columns
                },
                {
                    targets: [0,1,2,3,4,5],
                    visible: bdastatus !== 'BDA',
                },
                {
                    targets: [6,7,9],
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






<!--Delete Modal -->
<div class="modal fade" id="rejectSbumitter" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">{{__('message.Are you sure you want to delete this request?')}} </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      {{ Form::open(array('method' => 'post', 'id' => 'rejectformSbumitter', 'class' => 'form form-signin')) }}
      <div class="single-modal-content">
      <div class="modal-body">
      </div>
      </div>
      <div class="modal-footer">
        <div class="logout-btn">
            <a href="javascript:void(0)" class="btn btn-primaryx default" type="button" data-bs-dismiss="modal">{{__('message.Cancel')}}</a> 
            <button type="submit" class="btn btn-primaryx">{{__('message.Delete')}}</button>
        </div>
      </div>
      {{ Form::close()}}

    </div>
  </div>
</div>

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
                <th>{{__('message.First Name')}}</th>
                <th>{{__('message.Last Name')}}</th>
                <th>{{__('message.Comment')}}</th>
                <th>{{__('message.Country')}}</th>
                <th>{{__('message.Wallet Manager')}}</th>
                <th>{{__('message.Phone Number')}}</th>
                <th>{{__('message.Beneficiary')}}</th>
                <th>{{__('message.IBAN')}}</th> 
                <th>{{__('message.Amount')}}</th>
                <th>{{__('message.Comment(Reason)')}} </th>
                <th>{{__('message.Submitted By')}}</th>
                <th>{{__('message.Approved/Rejected By')}}</th>
                <th>{{__('message.Operation Date')}}</th>
                <th>{{__('message.Status')}}</th>
                <th>{{__('message.Reason For Rejection')}}</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
    $(document).ready(function(){
      $('[data-toggle="tooltip"]').tooltip();   
    });
</script>

    <!-- <style>
        /* Tooltip styles */
        .tooltip-container {
            position: relative;
            display: inline-block;
        }

        .tooltip-container .tooltip-icon {
            font-size: 16px;
            color: #007bff;
            cursor: pointer;
        }

        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style> -->


    <!-- Modal -->
    <!-- <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p>First Name: Blank</p>
            <p>Last Name: Blank</p>
        </div>
    </div>

    <script>
        // JavaScript to handle modal visibility
        document.addEventListener('DOMContentLoaded', (event) => {
            const tooltipIcon = document.querySelector('.tooltip-icon');
            const modal = document.getElementById('modal');
            const closeBtn = document.querySelector('.close');

            if (tooltipIcon) {
                tooltipIcon.addEventListener('click', () => {
                    modal.style.display = 'block';
                });
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    modal.style.display = 'none';
                });
            }

            window.addEventListener('click', (event) => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
     </script> -->



@endsection