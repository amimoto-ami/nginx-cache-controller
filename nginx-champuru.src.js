(function($){
    $.getJSON("/nginx-champuru.json", function(json){
        $('#author').val(json.comment_author);
        $('#email').val(json.comment_author_email);
        $('#url').val(json.comment_author_url);
    });
})(jQuery);
