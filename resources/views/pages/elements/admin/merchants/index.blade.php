<?php use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
?>
{{ HTML::script('public/assets/js/facebox.js')}}
{{ HTML::style('public/assets/css/facebox.css')}}
<script type="text/javascript">
    $(document).ready(function ($) {
        $('.close_image').hide();
        $('a[rel*=facebox]').facebox({
            closeImage: '{!! HTTP_PATH !!}/public/img/close.png'
        });
        
        $('.dropdown-menu a').on('click', function (event) { 
            $(this).parent().parent().parent().toggleClass('open');
        });
    });
</script>
<div class="admin_loader" id="loaderID">{{HTML::image("public/img/website_load.svg", '')}}</div>
@if(!$allrecords->isEmpty())
<div class="panel-body marginzero">
    <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
    {{ Form::open(array('method' => 'post', 'id' => 'actionFrom')) }}
    <input type="hidden" name="page" value="{{$page}}">
    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="topn_left">Merchant Users List</div>
            <div class="topn_rightd ddpagingshorting paggng-txt" id="pagingLinks" align="right">
                <div class="topn_righ">
                    Showing {{$allrecords->count()}} of {{ $allrecords->total() }} record(s).
                </div>
                <div class="panel-heading" style="align-items:center;">
                    {{$allrecords->appends(Request::except('_token'))->render()}}
                </div>
            </div>                
        </div>
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        <th style="width:5%">#</th>
                        <th class="sorting_paging">@sortablelink('business_name', 'Business Name')</th>
                        <th class="sorting_paging">@sortablelink('name', 'Business Owner Name')</th>
                        <th class="sorting_paging">@sortablelink('email', 'Email Address')</th>
                        <th class="sorting_paging">@sortablelink('phone', 'Phone')</th>
                        <th class="sorting_paging">@sortablelink('wallet_balance', 'Wallet Balance')</th>
                        <th class="sorting_paging">API Key</th>
                        <th class="sorting_paging">@sortablelink('api_enable', 'API Status')</th>
                        <th class="sorting_paging">@sortablelink('is_verify', 'Status')</th>
                        <th class="sorting_paging">@sortablelink('is_kyc_done', 'KYC Status')</th>
                        <th class="sorting_paging">@sortablelink('created_at', 'Date')</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allrecords as $allrecord)
                    <tr>
                        <th style="width:5%"><input type="checkbox" onclick="javascript:isAllSelect(this.form);" name="chkRecordId[]" value="{{$allrecord->id}}" /></th>
                        <td data-title="Business Name">{{$allrecord->business_name}}</td>
                        <td data-title="Business Owner Name">{{$allrecord->name}}</td>
                        <td data-title="Email Address">{{$allrecord->email?$allrecord->email:'N/A'}}</td>
                        <td data-title="Contact Number">{{$allrecord->phone}}</td>
                        <td data-title="Wallet Balance">{{CURR}} {{$allrecord->wallet_balance}}</td>
                        <td data-title="API Key" id="api_key_{{$allrecord->slug}}">{{$allrecord->api_key}}</td>
                        <td data-title="API Status" id="apiverify_{{$allrecord->slug}}">
                            @if($allrecord->api_enable == 'Y')
                                Activated
                            @else
                                Deactivated
                            @endif
                        </td>
                        <td data-title="Status" id="verify_{{$allrecord->slug}}">
                            @if($allrecord->is_verify == 1)
                                Activated
                            @else
                                Deactivated
                            @endif
                        </td>
                        <td data-title="KYC Status">
                            @if($allrecord->is_kyc_done == 1)
                            Approved
                            @elseif($allrecord->is_kyc_done == 2)
                            Declined
                            @elseif($allrecord->is_kyc_done == 3)
                            Not Submitted
                            @else
                            Pending
                            @endif
                        </td>
                        <td data-title="Date">{{$allrecord->created_at->format('M d, Y h:i A')}}</td>
                        <td data-title="Action">
                            <div id="loderstatus{{$allrecord->id}}" class="right_action_lo">{{HTML::image("public/img/loading.gif", '')}}</div>
                            
                            
                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                    <li class="right_acdc" id="status{{$allrecord->id}}">


                                        @if($allrecord->is_verify == '1')
                                        <a href="{{ URL::to( 'admin/merchants/deactivate/'.$allrecord->slug)}}" title="Deactivate User" class="deactivate"><i class="fa fa-check"></i>Deactivate User</a>
                                        @else
                                        <a href="{{ URL::to( 'admin/merchants/activate/'.$allrecord->slug)}}" title="Activate User" class="activate"><i class="fa fa-ban"></i>Activate User</a>
                                        @endif
                                    </li>
                                    <li><a href="{{ URL::to( 'admin/merchants/edit/'.$allrecord->slug)}}" title="Edit" class=""><i class="fa fa-pencil"></i>Edit User</a></li>
                                    <!-- <li><a href="{{ URL::to( 'admin/merchants/delete/'.$allrecord->slug)}}" title="Delete" class="" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash-o"></i>Delete</a></li> -->
                                    
                                    <li><a href="#info{!! $allrecord->id !!}" title="View User Detail" class="" rel='facebox'><i class="fa fa-eye"></i>View User Detail</a></li>
                                    <li><a href="{{ URL::to( 'admin/merchants/kycdetail/'.$allrecord->slug)}}" title="View KYC Details" class=""><i class="fa fa-file"></i>View KYC Details</a></li>
                                    <!-- <li><a href="{{ URL::to( 'admin/transactionfees/transactionfee/'.$allrecord->slug)}}" title="Manage Transaction Fees" class=""><i class="fa fa-file-text"></i>Transaction Fees</a></li> -->
                                    <li><a href="{{ URL::to( 'admin/transactions/transactionHistory/'.$allrecord->slug)}}" title="Manage Transaction History" class=""><i class="fa fa-money"></i>Transaction History</a></li>
                                    <li><a href="{{ URL::to( 'admin/users/homeFeatures/'.$allrecord->slug)}}" title="Manage Home Features" class=""><i class="fa fa-home"></i>Manage Home Features</a></li>
                                    <li><a href="{{ URL::to( 'admin/merchants/merchantSetting/'.$allrecord->slug)}}" title="Manage Merchant Setting" class=""><i class="fa fa-cog"></i>Manage Merchant Setting</a></li>
                                    <li class="right_acdc" id="api_status{{$allrecord->id}}">
                                        @if($allrecord->api_enable == 'Y')
                                        <a href="{{ URL::to( 'admin/merchants/api-deactivate/'.$allrecord->slug)}}" title="API Key Deactivate" class="apideactivate"><i class="fa fa-check"></i>Deactivate API Key</a>
                                        @else
                                        <a href="{{ URL::to( 'admin/merchants/api-activate/'.$allrecord->slug)}}" title="API Key Activate" class="apiactivate"><i class="fa fa-ban"></i>Activate API Key</a>
                                        @endif
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="search_frm">
                <button type="button" name="chkRecordId" onclick="checkAll(true);"  class="btn btn-info">Select All</button>
                <button type="button" name="chkRecordId" onclick="checkAll(false);" class="btn btn-info">Unselect All</button>
                <?php $accountStatus = array(
    'Activate' => "Activate User",
                    'Deactivate' => "Deactivate User",
    // 'Delete' => "Delete",
);; ?>
                <div class="list_sel">{{Form::select('action', $accountStatus,null, ['class' => 'small form-control','placeholder' => 'Action for selected record', 'id' => 'action'])}}</div>
                <button type="submit" class="small btn btn-success btn-cons btn-info" onclick="return ajaxActionFunction();" id="submit_action">OK</button>
            </div>    
        </div>
    </section>
    {{ Form::close()}}
</div>         
</div> 
@else 
<div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
<div class="admin_no_record">No record found.</div>
@endif

@if(!$allrecords->isEmpty())
@foreach($allrecords as $allrecord)
<div id="info{!! $allrecord->id !!}" style="display: none;">
    <div class="nzwh-wrapper">
        <fieldset class="nzwh">
            <legend class="head_pop">{!! $allrecord->business_name !!}</legend>
            <div class="drt">
                <div class="admin_pop"><span>User Type: </span>  <label>@isset($allrecord->user_type) {{$allrecord->user_type}} @endisset</label></div>
                <div class="admin_pop"><span>Business Name: </span>  <label>{!! $allrecord->business_name !!}</label></div>
                <div class="admin_pop"><span>Business Owner Name: </span>  <label>{!! $allrecord->name !!}</label></div>
                <div class="admin_pop"><span>Wallet Balance: </span>  <label>{!! CURR.' '.$allrecord->wallet_balance !!}</label></div>
                <div class="admin_pop"><span>Business Email: </span>  <label>{{$allrecord->email?$allrecord->email:'N/A'}}</label></div>
                <div class="admin_pop"><span>Business registration number: </span>  <label><?php echo $allrecord->registration_number?$allrecord->registration_number:''; ?></label></div>
                <!--<div class="admin_pop"><span>Business registration number: </span>  <label><?php echo $allrecord->registration_number;?></label></div>-->
                <div class="admin_pop"><span>Phone Number: </span>  <label>{!! $allrecord->phone !!}</label></div>
                <div class="admin_pop"><span>Date Of Birth: </span>  <label>{!! $allrecord->dob !!}</label></div>
               
                                
                @if($allrecord->profile_image != '')
                    <div class="admin_pop"><span>Profile Image</span> <label>{{HTML::image(PROFILE_FULL_DISPLAY_PATH.$allrecord->profile_image, SITE_TITLE,['style'=>"max-width: 200px"])}}</label></div>
                @endif
                
<!--                @if($allrecord->identity_image != '')
                    <div class="admin_pop"><span>Picture of business owner’s national identity</span> <label>{{HTML::image(IDENTITY_FULL_DISPLAY_PATH.$allrecord->identity_image, SITE_TITLE,['style'=>"max-width: 200px"])}}</label></div>
                @endif-->
                
        </fieldset>
    </div>
</div>
@endforeach
@endif