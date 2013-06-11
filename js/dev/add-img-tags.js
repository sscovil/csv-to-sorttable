(function($) {
    "use strict";
    $(function () {

        $('table.sortable .image a').html(function(){
            var $text = $(this).text();
            $(this).html( '<img src="' + $text + '" alt="" />' );
        })

    });
}(jQuery));