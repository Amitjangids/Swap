<script type="text/javascript">
 var oTable = $('#exampleTableExpend').DataTable({
            processing: false,
            bFilter: false,
            searching: false,
            serverSide: true,
            lengthChange: false,
            order: [[0, 'desc']],
            ajax: "{{HTTP_PATH}}/get-excel-record/{{$id}}",
            columns: [
                { data: 'first_name'},
                { data: 'name' },
                { data: 'comment'},
                { data: 'country'},
                { data: 'wallet'},
                { data: 'tel_number'},
                { data: 'amount'},
            ],
            columnDefs: [
                {
                    //targets: [7], // column index (start from 0)
                    orderable: false, // set orderable false for selected columns
                }
            ],
        });
</script>

<tr class="remove_all expandview{{$id}}" style="display:none;">
    <td colspan="8">
        <table id="exampleTableExpend">
            <thead>
            <tr>
                <th>{{__('message.First Name')}}</th>
                <th>{{__('message.Last Name')}}</th>
                <th>{{__('message.Comment')}}</th>
                <th>{{__('message.Country')}}</th>
                <th>{{__('message.Wallet Manager')}}</th>
                <th>{{__('message.Phone Number')}}</th>
                <th>{{__('message.Amount')}}</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </td>
</tr>