jQuery(document).ready(function($){
    var forms  = $(".wpcf7");

    var signs = [];
    forms.each(function(k, form){

        $(form).find(".wpcf7-sign-wrap").each(function(i, wrap){
            
            var canvas = $(wrap).find('canvas').get(0),
            button = $(wrap).find('button.cf7sg-sign'),
            form_id = $(form).find('[name=_wpcf7]').val(),
            input = $(wrap).find('input');
            // resizeCanvas(canvas);

            var signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)'  
            });

            signs[k+'-'+i] = signaturePad;
            signs[k+'-'+i].addEventListener('endStroke', function(e){
                var data = signaturePad.toDataURL('image/png');
                input.val(data);
            });

            document.addEventListener( 'wpcf7mailsent', function( event ) {
                if( event.detail.contactFormId != form_id ) return;
                signs[k+'-'+i].clear();
                $('.wpcf7-sign').val('');
            }, false );


            $(button).on('click',function(e){
                e.preventDefault();
                signs[k+'-'+i].clear();
                $(wrap).find('input').val('');
            });

        }); 
    }); 
});


function resizeCanvas( canvas ) {
    const ratio =  Math.max(window.devicePixelRatio || 1, 1);
    canvas.width = canvas.offsetWidth * ratio;
    canvas.height = canvas.offsetHeight * ratio;
    canvas.getContext("2d").scale(ratio, ratio);
}