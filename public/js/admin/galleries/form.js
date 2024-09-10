$(function() {
    $( "#save_gallery_btn" ).on( "click", function() {
        var data = $("#gallery_form").serialize();
        var urlsend = $("#gallery_form").attr("action");
        var base_url = $("#base_url").val();

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: urlsend,
            method: "POST",
            data: data,
            dataType: 'json',
            success: function (data) {
                var gallery = data.gallery;
                RetrieveGalleryLinks(base_url,gallery.id, gallery.google_link);

            },
            error: function (request, status, error) {
                var messages = JSON.parse(request.responseText);
               if(messages.errors['name']) alert(messages.errors['name'][0]);
               if(messages.errors['google_link']) alert(messages.errors['google_link'][0]);
            }
        });

    });
})
