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
    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="manage_sec">
                <div class="topn_left">Used Recharge Cards List</div>
                <div class="topn_rightd ddpagingshorting" id="pagingLinks" align="right">
                    <div class="panel-heading" style="align-items:center;">
                        {{$allrecords->appends(Request::except('_token'))->render()}}
                    </div>
                </div>                
            </div>
            
            <div class="transaction_info">
                <div class="topn_left_btsec">
<!--                    <div class="payment_info">
                        <span class="pay_head">Card Total Value</span>
                        <span class="pay_body">{{CURR}} {{number_format($total['total'],2)}}</span>
                    </div>-->
                    <div class="payment_info">
                        <span class="pay_head">Total Used Card</span>
                        <span class="pay_body">{{CURR}} {{number_format($total['used_value'],2)}}</span>
                    </div>
<!--                    <div class="payment_info">
                        <span class="pay_head">Total Unused Card</span>
                        <span class="pay_body">{{CURR}} {{number_format($total['unused_value'],2)}}</span>
                    </div>-->
                </div>
            </div>
        </div>
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        <?php /*<th style="width:5%">#</th>*/ ?>
                        <th class="sorting_paging">@sortablelink('Card.company_name', 'Company Name')</th>
                        <th class="sorting_paging">@sortablelink('Card.card_type', 'Card Type')</th>
                        <th class="sorting_paging">@sortablelink('real_value', 'Card Value')</th>
                        <th class="sorting_paging">@sortablelink('card_value', 'Card Cost')</th>
                        <!--<th class="sorting_paging">@sortablelink('used_status', 'Used/Unused')</th>-->
                        <th class="sorting_paging">@sortablelink('User.name', 'Used By')</th>
                        <th class="sorting_paging">@sortablelink('used_date', 'Used Date')</th>
                        <th class="sorting_paging">@sortablelink('created_at', 'Created Date')</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php global $cardType; ?>
                    @foreach($allrecords as $allrecord)
                    <tr>
                        <!-- <th style="width:5%"><input type="checkbox" onclick="javascript:isAllSelect(this.form);" name="chkRecordId[]" value="{{$allrecord->id}}" /></th> -->
                        <td data-title="Company Name">@if($allrecord->Card){{$allrecord->Card->company_name}}@else''@endif</td>
                        <td data-title="Card Type">@if($allrecord->Card){{$cardType[$allrecord->Card->card_type]}}@else''@endif</td>
                        <td data-title="Card Value">{{$allrecord->currency}} {{$allrecord->real_value}}</td>
                        <td data-title="Card Cost">{{CURR}} {{$allrecord->card_value}}</td>
<!--                        <td data-title="Used/Unused">
                            @if($allrecord->used_status == 1)
                            Used
                            @else
                            Unused
                            @endif
                        </td>-->
                        <td data-title="Used By">@if(isset($allrecord->User->name)){{$allrecord->User->name}} ({{$allrecord->User->phone}})@else @endif</td>

                        <td data-title="Used Date">{{date('M d, Y h:i A',strtotime($allrecord->used_date))}}</td>
                        <td data-title="Created Date">{{$allrecord->created_at->format('M d, Y h:i A')}}</td>
                        <td data-title="Action">
                            <div id="loderstatus{{$allrecord->id}}" class="right_action_lo">{{HTML::image("public/img/loading.gif", '')}}</div>                            

                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">


                                    <li><a href="#info{!! $allrecord->id !!}" title="View Card Detail" class="" rel='facebox'><i class="fa fa-eye"></i>View Card Detail</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>   
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
            <legend class="head_pop">@if($allrecord->Card){!! $allrecord->Card->company_name !!}@else''@endif</legend>
            <div class="drt">
                <div class="admin_pop"><span>Card Type: </span>  <label>@if($allrecord->Card){!! $cardType[$allrecord->Card->card_type] !!}@else''@endif</label></div>
                <div class="admin_pop"><span>Company Name: </span>  <label>@if($allrecord->Card){{$allrecord->Card->company_name}}@else''@endif</label></div>
                <div class="admin_pop"><span>Card Value: </span>  <label>{{CURR}} {!! $allrecord->real_value !!}</label></div>
                <div class="admin_pop"><span>Used By Username: </span>  <label>@if(isset($allrecord->User->name)){{$allrecord->User->name}}@else''@endif</label></div>
                <div class="admin_pop"><span>User Phone: </span>  <label>@if(isset($allrecord->User->name)){{$allrecord->User->phone}}@else''@endif</label></div>
        </fieldset>
    </div>
</div>
@endforeach
@endif