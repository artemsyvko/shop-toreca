<script>
    $(function() {
        var index = 9;
        $('th').each(function(i) {
            if($(this).text().match(/{{'admin.product.delivery_duration'|trans}}/)){
                index = i;
            }
        });
        $('table tr').each(function(i) {
            if (i != 0) {
                $elem = $('#deliverydate_days_' + i);
                $('td:eq('+index+')', this).after('<td class="align-middle">' + $elem.html() + '</td>');
                $elem.remove();
                $('td:eq('+index+')', this).remove();
            } else {
                $elem = $('#deliverydate_days_th');
                $('th:eq('+index+')', this).after('<th class="pt-2 pb-2">' + $elem.text() + '</th>');
                $elem.remove();
                $('th:eq('+index+')', this).remove();
            }
        });

        // 1行目をコピーボタン
        $('#copy').click(function() {
            var weight = $('#product_class_matrix_product_classes_0_delivery_date_days').val();
            $('input[id$=_delivery_date_days]').val(weight);
        });
    });
</script>
