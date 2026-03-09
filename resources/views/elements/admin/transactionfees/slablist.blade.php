{{ HTML::script('public/assets/js/facebox.js')}}
{{ HTML::style('public/assets/css/facebox.css')}}

<style>
   section.lstng-section.amountslab-section {
    padding: 50px 0 0;
    position: relative;
}


section.lstng-section.amountslab-section .add_new_record {
    padding: 0;
    right: 0;
    top: 0;
}
</style>
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
    <section id="no-more-tables" class="lstng-section amountslab-section">
  
    <div class="topn">
            <div class="manage_sec">
                <div class="topn_left">Amount Slab List</div>

                <div class="topn_rightd ddpagingshorting paggng-txt" id="pagingLinks" align="right">
                    <div class="topn_righ">
                    Showing {{$allrecords->count()}} of {{ $allrecords->total() }} record(s).
                    </div>
                    <div class="panel-heading" style="align-items:center;">
                    {{$allrecords->appends(Request::except('_token'))->render()}}
                    </div>
                    {{ Form::close()}}
              
                </div> 
               
            </div> 
            {{ Form::close()}}
          
    </div>
    
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        
                        <th class="sorting_paging">@sortablelink('id', 'ID')</th>
                        <th class="sorting_paging">@sortablelink('min_amount', 'MIN Amonunt')</th>
                        <th class="sorting_paging">@sortablelink('max_amount', 'Max Amount')</th>
                        <th class="sorting_paging">@sortablelink('created_at', 'Date')</th>
                        <!-- <th class="action_dvv"> Action</th> -->
                    </tr>
                </thead>
                <tbody>
                <?php $serialNumber = 1; ?>
                @foreach($allrecords as $allrecord) 
              
                    <tr>
                      
                        <td data-title="ID">{{$serialNumber }}</td>
                        <td data-title="MIN Amonunt">{{number_format((($allrecord->min_amount - floor($allrecord->min_amount)) > 0.5 ? ceil($allrecord->min_amount) : floor($allrecord->min_amount)), 0, '', ' ') ?? 0}}</td>
                        <td data-title="Max Amount">{{number_format((($allrecord->max_amount - floor($allrecord->max_amount)) > 0.5 ? ceil($allrecord->max_amount) : floor($allrecord->max_amount)), 0, '', ' ') ?? 0}}</td>
                        
                        <td data-title="Date">{{ $allrecord->created_at }}</td>
                        <td data-title="Action">
                            <div id="loderstatus{{$allrecord->id}}" class="right_action_lo">{{HTML::image("public/img/loading.gif", '')}}</div>
                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                <div class="add_new_record"><a href="{{URL::to('admin/transactionfees/editSlab/'.$allrecord->id)}}" class="btn btn-default"><i class="fa fa-pencil"></i>Edit Slab Amount</a></li>
                                
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php $serialNumber++; ?>
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

