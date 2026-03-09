<div id="trans_ch" style="width: 100%; height: 250px;"></div>
<script>
<?php if($dayCount == 365): ?>
   $(function () {
    $('#trans_ch').highcharts({
        title: { text: ''},
        xAxis: { categories: [<?php echo $catArray;?>] },
        yAxis: {
            title: { text: 'Number of Transactions' },
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
            name: 'Transactions',
            data: [<?php echo $finalArray;?>]
        }]
    });
}); 
<?php else: ?>
    $(function () {
    $('#trans_ch').highcharts({
        title: { text: ''},
        xAxis: { type: 'datetime', dateTimeLabelFormats: { day: '%e %b'}},
        yAxis: {
            title: { text: 'Number of Transactions' },
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
            name: 'Transactions',
            data: [ <?php echo e($finalArray); ?>]
        }]
    });   
});
<?php endif; ?>
$("#trans_chart_loader").hide();
</script><?php /**PATH /var/www/internal-swap-africa/resources/views/elements/admin/transchart.blade.php ENDPATH**/ ?>