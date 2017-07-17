(function($, document) {
    $(function() {
        var $payment = $('#g2apay__payment'),
            $loader = $('#g2apay__payment-loader'),
            $message = $('#g2apay__payment-message'),
            url = $payment.data('url'),
            checkTimeout = 5000;

        checkStatus();

        function checkStatus() {
            $.ajax({
                url     : url,
                dataType: 'json',
                success : function(data) {
                    if (data.success) {
                        $loader.hide();
                        $message.text(data.message).addClass('success').show();
                    } else if (data.retry) {
                        setTimeout(checkStatus, checkTimeout);
                    } else {
                        $loader.hide();
                        $message.text(data.message).addClass('notice').show();
                    }
                },
                error   : function(xhr) {
                    $loader.hide();
                    $message.text('Something went wrong').addClass('notice').show();
                }
            });
        }
    });
})(jQuery, document);
