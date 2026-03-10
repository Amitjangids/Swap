<div id="merchant_ch" style="width: 100%; height: 250px;"></div>
<script>
@if($dayCount == 365)
   $(function () {
    $('#merchant_ch').highcharts({
        title: { text: ''},
        xAxis: { categories: [<?php echo $catArray;?>] },
        yAxis: {
            title: { text: 'Number of Merchants' },
            gridLineWidth: 1,
            plotLines: [{
                value: 0,
                width: 1,
                color: '#808080'
            }]
        },
        exporting: { enabled: false },
        credits: { enabled: false},
        legend: { enabled: false},
        series: [{
            name: 'Merchants',
            data: [<?php echo $finalArray;?>]
        }]
    });
}); 
@else
    $(function () {
    $('#merchant_ch').highcharts({
        title: { text: ''},
        xAxis: { type: 'datetime', dateTimeLabelFormats: { day: '%e %b'}},
        yAxis: {
            title: { text: 'Number of Merchants' },
            gridLineWidth: 1,
            plotLines: [{
                value: 0,
                width: 1,
                color: '#808080'
            }]
        },
        exporting: { enabled: false },
        credits: { enabled: false},
        legend: { enabled: false},
        series: [{
            name: 'Merchants',
            data: [ {{$finalArray}}]
        }]
    });   
});
@endif
$("#merchant_chart_loader").hide();
</script>