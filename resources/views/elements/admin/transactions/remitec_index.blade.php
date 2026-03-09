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
<div class="admin_loader" id="loaderID">{{HTML::image("public/img/website_load.svg", '')}}</div>
@if(!$allrecords->isEmpty())
<div class="panel-body marginzero">
    <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
    {{ Form::open(array('method' => 'post', 'id' => 'actionFrom')) }}
    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="manage_sec">
                <div class="topn_left">Remitec Transactions List</div>

                <div class="topn_rightd ddpagingshorting paggng-txt" id="pagingLinks" align="right">
                    <div class="topn_righ">
                        Showing {{$allrecords->count()}} of {{ $allrecords->total() }} record(s).
                    </div>
                    <div class="panel-heading" style="align-items:center;">
                        {{$allrecords->appends(Request::except('_token'))->render()}}
                    </div>
                </div>   
            </div>   
        
        </div>


        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        <!--<th style="width:5%">#</th>-->
                                                <!--<th style="width:5%">Trans Id</th>-->
                        <th class="sorting_paging">@sortablelink('firstName', 'Sender Name')</th>
                        <th class="sorting_paging">@sortablelink('senderPhoneNumber', 'Sender Phone')</th>
                        <th class="sorting_paging">@sortablelink('receiverFirstName', 'Receiver Name')</th>
                        <th class="sorting_paging">@sortablelink('transactionSourceAmount', 'Source Amount')</th>
                        <th class="sorting_paging">@sortablelink('transactionTargetAmount', 'Target Amount')</th>
                        <th class="sorting_paging">@sortablelink('transactionId', 'Transaction ID')</th>
                        <th class="sorting_paging">@sortablelink('type', 'Transaction Type')</th>
                        <th class="sorting_paging">@sortablelink('status', 'Status')</th>
                        <th class="sorting_paging">@sortablelink('created_at', 'Transaction Date')</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allrecords as $allrecord) 
                    <tr>
                        <td data-title="Sender Name">{{isset($allrecord->firstName) ? ucfirst($allrecord->firstName.' '.$allrecord->lastName):'N/A'}}</td>
                        <td data-title="Sender Phone">{{isset($allrecord->senderPhoneNumber) ? ucfirst($allrecord->senderPhoneNumber):'N/A'}}</td>
                        <td data-title="Receiver Name">@if(isset($allrecord->receiverFirstName)){{ucfirst($allrecord->receiverFirstName.' '.$allrecord->receiverLastName)}}@else{{'N/A'}}@endif</td>
                        <td data-title="Source Amount">{{ $allrecord->transactionSourceAmount.' '.$allrecord->sourceCurrency ?? 0 }}</td>
                        <td data-title="Source Amount">{{ $allrecord->transactionTargetAmount.' '.$allrecord->targetCurrency ?? 0 }}</td>
                        <td data-title="Transaction ID">{{$allrecord->transactionId}}</td>
                        <td data-title="Type">{{ ucwords(str_replace('_', ' ',$allrecord->type))}}</td>
                        <td data-title="Status">{{ $allrecord->status }}</td>
                        <td data-title="Transaction / Request Date">{{$allrecord->created_at->format('M d, Y ')}}</td>
                        <td data-title="Action">
                            <a href="#info{!! $allrecord->id !!}" title="View Transaction Details" class="btn btn-primary btn-xs" rel='facebox'><i class="fa fa-eye"></i></a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <!--            <div class="search_frm">
                            <button type="button" name="chkRecordId" onclick="checkAll(true);"  class="btn btn-info">Select All</button>
                            <button type="button" name="chkRecordId" onclick="checkAll(false);" class="btn btn-info">Unselect All</button>
            <?php
            $accountStatus = array(
                'Delete' => "Delete",
            );
            ?>
                            <div class="list_sel">{{Form::select('action', $accountStatus,null, ['class' => 'small form-control','placeholder' => 'Action for selected record', 'id' => 'action'])}}</div>
                            <button type="submit" class="small btn btn-success btn-cons btn-info" onclick="return ajaxActionFunction();" id="submit_action">OK</button>
                        </div>    -->
        </div>
    </section>
    {{ Form::close()}}
</div>         
</div> 
@else 
<div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
<div class="admin_no_record">No record found.</div>
@endif

<?php

use App\Models\Transaction;
?>
@if(!$allrecords->isEmpty())
@foreach($allrecords as $allrecord)
<div id="info{!! $allrecord->id !!}" style="display: none;">
    <div class="nzwh-wrapper">
        <fieldset class="nzwh">
          <legend class="head_pop">Remitec Transactions Details</legend>
            <div class="drt">
                <div class="admin_pop"><span>Sender Name: </span>  <label>
                        {{isset($allrecord->firstName) ? ucfirst($allrecord->firstName.' '.$allrecord->lastName):'N/A'}}
                    </label>
                </div>
                <div class="admin_pop"><span>Sender Phone: </span>  <label>
                        {{$allrecord->senderPhoneNumber}}
                    </label>
                </div>
                <div class="admin_pop"><span>Receiver Name: </span>  <label>
                @if(isset($allrecord->receiverFirstName)){{ucfirst($allrecord->receiverFirstName.' '.$allrecord->receiverLastName)}}@else{{'N/A'}}@endif
                    </label>
                </div>
                
               
                <div class="admin_pop"><span>Source Amount: </span>  <label>
                {{ $allrecord->transactionSourceAmount.' '.$allrecord->sourceCurrency ?? 0 }}
                    </label>
                </div>
                <div class="admin_pop"><span>Target Amount: </span>  <label>
                {{ $allrecord->transactionTargetAmount.' '.$allrecord->targetCurrency ?? 0 }}
                    </label>
                </div>
                <div class="admin_pop"><span>Transaction Type: </span>  <label>
                {{ ucwords(str_replace('_', ' ',$allrecord->type))}}
                    </label>
                </div>

                <div class="admin_pop"><span>Status: </span>  <label>
                {{ $allrecord->status }}
                    </label>
                </div>


              


               
                
                <div class="admin_pop"><span>Transaction/Request Date: </span>  <label>{{$allrecord->created_at->format('M d, Y ')}}</label></div>
                <!-- <div class="admin_pop"><span>Transaction Process Date: </span>  <label>{{$allrecord->updated_at->format('M d, Y h:i:s A')}}</label></div> -->

        </fieldset>
    </div>
</div>
@endforeach
@endif
