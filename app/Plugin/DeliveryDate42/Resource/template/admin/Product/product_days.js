{% if has_class == false %}
    <script>
        $(function() {
            $('div.row').each(function(i) {
                if($(this).text().match(/{{'admin.product.delivery_duration'|trans}}/)){
                    $elem = $('#deliverydate_days');
                    $(this).after($elem.html());
                    $(this).remove();
                    $elem.remove();
                }
            });
        });
    </script>
{% endif %}