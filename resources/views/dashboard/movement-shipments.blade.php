@extends('layouts.home')
@section('content')
<section class="tiles-section-wrapper customtiles">
       <div class="container">
        <h2>Movement of Shipments</h2>
           <div class="row">
               <div class="col-lg-4">
                   <div class="small-box bg-green">
                        <div class="inner">
                            <h3 id='someElement'>{{ CURR }}0</h3>
                            <p>Movement of shipments</p>
                        </div>
                    </div>
               </div>
           </div>
       </div>
   </section>

   <section class="same-section table-history">
       <div class="container">
           <div class="transition-history-wrapper">
               <h2>Transaction History</h2>
               <div class="table-responsive">
               <table id='exampleTablePending' class="table table-dark table-striped" width="100%">  
               <thead>
                   <tr>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Comment</th>
                        <th>Country</th>
                        <th>Wallet Manager</th>
                        <th>Phone Number</th>
                        <th>Amount</th>
                        <th>Submitted By</th>
                        <th>Approved/Rejected By</th>
                        <th>Operation Date</th>
                        <th>Status</th>
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
                    url: "{{HTTP_PATH}}/gimac-transaction-list",
                    type: "GET", // or "GET" depending on your server-side code
                    dataType: "json",
                    dataSrc: function(response) {
                        var totalAmount = response.totalAmount;
                        $('#someElement').html('{{ CURR }}'+totalAmount);
                        return response.aaData;
                    }
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
                { data: 'approved_by'},
                { data: 'created_at'},
                { data: 'gimac_status'},
             ],
            columnDefs: [ {
            targets: [5], // column index (start from 0)
            orderable: false, // set orderable false for selected columns
            }],
          });
        });
</script>
   
@endsection