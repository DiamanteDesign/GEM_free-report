(function($) {
    $(function(){
        $(document).on('submit','form.free-report',function(){
           var errors = new Array();
           $(this).find("input").each(function(j,i){
               i = $(i)
               if(i.data('required') && i.prop('value') == ""){
                   errors.push(i.prop('placeholder'));
                   i.addClass('alert');
               } else {
                   i.removeClass("alert");
               }
           })
           if(errors.length > 0){
               event.preventDefault();
           }
        });
    })        
})(jQuery);
