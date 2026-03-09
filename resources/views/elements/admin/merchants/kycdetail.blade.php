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

@if(!empty($userInfo->selfie_image) || !empty($userInfo->identity_front_image))
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
                        <th class="sorting_paging">Id Type</th>
                        <th class="sorting_paging">Selfile Image</th>
                        <th class="sorting_paging">Front Image</th>
                        @if(!empty($userInfo->identity_back_image))
                        <th class="sorting_paging">Back Image</th>
                        @endif
                        <?php global $documents; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        @foreach ($documents as $value)
                        @if (is_array($value) && array_key_exists('id', $value))
                        @if ($value['id'] == $userInfo->national_identity_type)
                        <td data-title="Identity Type">{{ $value['name'] }}</td>
                        @endif
                        @endif
                        @endforeach
                        @if($userInfo->national_identity_type =="")
                        <td data-title="Identity Type"></td>
                        @endif

                        <td data-title="Picture Selfie Image">
                            @if($userInfo->selfie_image != '')

                            <a href="{{ $userInfo->selfie_image }}" title="View KYC Document"
                                data-fancybox-group="gallery1" class="fancybox" target="_blank">
                                {{HTML::image($userInfo->selfie_image, SITE_TITLE,['style'=>'max-width:50px;
                                max-height:50px;'])}}
                            </a>

                            @else
                            No Image
                            @endif

                        </td>
                        <td data-title="Picture Front Image">
                            @if($userInfo->identity_front_image != '')
                            <a href="{{$userInfo->identity_front_image}}" title="View KYC Document"
                                data-fancybox-group="gallery1" class="fancybox" target="_blank">
                                {{HTML::image($userInfo->identity_front_image, SITE_TITLE,['style' => 'max-width:50px;
                                max-height:50px;'])}}
                            </a>
                            @else
                            No Image
                            @endif

                        </td>
                        <td data-title="Picture Selfie Image">
                            @if($userInfo->identity_back_image != '')
                            <a href="{{$userInfo->identity_back_image}}" title="View KYC Document"
                                data-fancybox-group="gallery1" class="fancybox" target="_blank">
                                {{HTML::image($userInfo->identity_back_image, SITE_TITLE,['style' => 'max-width:50px;
                                max-height:50px;'])}}
                            </a>
                            @endif

                        </td>


                        <!-- <td data-title="Status">
                            @if($userInfo->is_kyc_done == 1)
                            Approved
                            @elseif($userInfo->is_kyc_done == 2)
                            Declined
                            @else
                            Pending
                            @endif
                        </td>
                        <td data-title="Date">{{$userInfo->created_at->format('M d, Y h:i A')}}</td> -->

                    </tr>
                </tbody>
            </table>
            <div class="search_frm">
                <!-- <?php if ($userInfo->is_kyc_done == 4) {
                    $userInfo->is_kyc_done = 0;
                } ?>
                @if($userInfo->is_kyc_done == 0)
                <a href="{{ URL::to( 'admin/merchants/approvekyc/'.$userInfo->slug)}}" title="Approve KYC" class="btn btn-info">Approve KYC</a>
                <a href="{{ URL::to( 'admin/merchants/declinekyc/'.$userInfo->slug)}}" title="Decline KYC" class="btn btn-info">Decline KYC</a>
                @elseif($userInfo->is_kyc_done == 2)
                <a href="javascript:void();" title="Declined KYC" class="btn btn-info">Declined</a>
                @else
                <a href="javascript:void();" title="Approved KYC" class="btn btn-info">Approved</a>
                @endif -->
                <?php if ($userInfo->kyc_status == "pending") { ?>
                    <a href="{{ URL::to('admin/merchants/approvekyc/' . $userInfo->slug) }}" title="Approve"
                        class="btn btn-info">Approve Kyc</a>
                <?php } ?>
                <a href="{{ URL::to( 'admin/merchants')}}" title="Cancel" class="btn btn-default canlcel_le">Cancel</a>
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