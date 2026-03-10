<?php use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
?>
{{ HTML::script('public/assets/js/jquery.fancybox.js?v=2.1.5')}}
{{ HTML::style('public/assets/css/jquery.fancybox.css?v=2.1.5')}}
<script type="text/javascript">

    $(document).ready(function () {
        $('.fancybox').fancybox();
    });
</script>

@if(!empty($userInfo->national_identity_number) || !empty($userInfo->identity_image))
<div class="panel-body marginzero">
    <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
    {{ Form::open(array('method' => 'post', 'id' => 'actionFrom')) }}
    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="topn_left">KYC Details List</div>
            <div class="topn_rightd ddpagingshorting" id="pagingLinks" align="right">

            </div>                
        </div>
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        <th class="sorting_paging">Type</th>
                        <th class="sorting_paging">National Identity Number</th>
                        <th class="sorting_paging">Picture national identity Front</th>
                        <th class="sorting_paging">Picture national identity Back</th>
                        <th class="sorting_paging">Status</th>
                        <th class="sorting_paging">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                    <td data-title="Type">{{$userInfo->user_type?$userInfo->user_type:''}}</td> 
                        <td data-title="National Identity Number">{{$userInfo->national_identity_number?$userInfo->national_identity_number:''}}</td> 
                        <!--<td data-title="National Identity Number">{{$userInfo->national_identity_number}}</td>-->
                        <td data-title="Picture national identity Front">  
                        @if($userInfo->identity_front_image != '')
                        <a href="{{IDENTITY_FULL_DISPLAY_PATH.$userInfo->identity_front_image}}" title="View KYC Document" data-fancybox-group="gallery1" class="fancybox">
                            {{HTML::image(IDENTITY_FULL_DISPLAY_PATH.$userInfo->identity_front_image, SITE_TITLE,['style' => 'max-width: 32px; max-height: 32px'])}}
                        </a>
                        @else
                        No Image
                        @endif
                        </td>

                        <td data-title="Picture national identity Back">
                        @if($userInfo->identity_back_image != '')
                        <a href="{{IDENTITY_FULL_DISPLAY_PATH.$userInfo->identity_back_image}}" title="View KYC Document" data-fancybox-group="gallery1" class="fancybox">
                            {{HTML::image(IDENTITY_FULL_DISPLAY_PATH.$userInfo->identity_back_image, SITE_TITLE,['style' => 'max-width: 32px; max-height: 32px'])}}
                        </a>
                        @else
                        No Image
                        @endif
                            

                        </td>
                        <td data-title="Status">
                            @if($userInfo->is_kyc_done == 1)
                            Approved
                            @elseif($userInfo->is_kyc_done == 2)
                            Declined
                            @else
                            Pending
                            @endif
                        </td>
                        <td data-title="Date">{{$userInfo->created_at->format('M d, Y h:i A')}}</td>
                    </tr>
                </tbody>
            </table>
            <div class="search_frm">  <?php //echo $userInfo->is_kyc_done;?>
                @if($userInfo->is_kyc_done == 0 || $userInfo->is_kyc_done == 4)
                <a href="{{ URL::to( 'admin/users/approvekyc/'.$userInfo->slug)}}" title="Approve KYC" class="btn btn-info">Approve KYC</a>
                <a href="{{ URL::to( 'admin/users/declinekyc/'.$userInfo->slug)}}" title="Decline KYC" class="btn btn-info">Decline KYC</a>
                @elseif($userInfo->is_kyc_done == 2)
                <a href="javascript:void();" title="Declined KYC" class="btn btn-info">Declined</a>
                @else
                <a href="javascript:void();" title="Approved KYC" class="btn btn-info">Approved</a>
                @endif

                <a href="{{ URL::to( 'admin/users')}}" title="Cancel" class="btn btn-default canlcel_le">Cancel</a>
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

