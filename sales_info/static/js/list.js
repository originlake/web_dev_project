const ids = ["#id_sort", "#id_condition", "#id_category", "#id_price", "#id_price1", "#id_zipcode", "#id_search"];
// how to simulate form get https://stackoverflow.com/a/30430163
var util = {};
util.post = function(url, fields) {
    var $form = $('<form>', {
        action: url,
        method: 'get'
    });
    $.each(fields, function(idx, val) {
         if($(val).val()){
             $('<input>').attr({
                 type: "hidden",
                 name: $(val).attr('name'),
                 value: $(val).val()
             }).appendTo($form);
         }
    });
    $form.appendTo('body').submit();
}

$(document).ready(function(){
    $('#filter').click(function(){
        util.post($(location).attr('href'), ids);
    });
    $('#search').click(function(){
        util.post($(location).attr('href'), ids);
    });
    $('.item-grid').click(function(){
        window.location.href=$(this).find("a").attr("href");
    });
});