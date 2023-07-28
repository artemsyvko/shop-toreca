<script>
    $(function() {
        $elem = $('#delivery_date');
        $('.card:eq(4)', this).after($elem.html());
        $elem.remove();
    });
    $(function() {
        $('#set_date_all').on('click', function() {
            var fee = $('#{{ form.date_all.vars.id }}').val();
            $('input[name$="[dates]"]').val(fee);
        });
    });
</script>
