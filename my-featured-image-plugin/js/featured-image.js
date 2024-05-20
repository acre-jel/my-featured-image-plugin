jQuery(document).ready(function($) {
    var mediaUploader;

    $('#select-image').on('click', function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media({
            title: 'Select Background Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#select-image').data('image-url', attachment.url);
        });

        mediaUploader.open();
    });

    $('#generate-featured-image').on('click', function() {
        var selectedImage = $('#select-image').data('image-url');
        var titleText = $('#title-text').val();

        if (!selectedImage) {
            alert('Please select a background image.');
            return;
        }

        var canvas = document.createElement('canvas');
        var context = canvas.getContext('2d');

        var img = new Image();
        img.src = selectedImage;
        img.onload = function() {
            canvas.width = img.width;
            canvas.height = img.height;

            context.drawImage(img, 0, 0);
            context.globalAlpha = 0.5;
            context.fillStyle = '#000';
            context.fillRect(0, 0, canvas.width, canvas.height);

            context.globalAlpha = 1.0;
            context.fillStyle = '#fff';
            context.textAlign = 'center';
            wrapText(context, titleText, canvas.width / 2, canvas.height / 2, canvas.width - 40, 48); // Adjusted line height to 48

            var generatedImage = canvas.toDataURL('image/png');
            $('#generated-image').attr('src', generatedImage).show();
            $('#save-image').show().data('image', generatedImage);
        };
    });

    $('#save-image').on('click', function() {
        var image = $(this).data('image');
        var postId = $(this).data('post-id');
        $.ajax({
            type: 'POST',
            url: myPluginAjax.ajax_url,
            data: {
                action: 'save_image',
                image: image,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    alert('Image saved successfully and set as featured image.');
                } else {
                    alert('Image saving failed.');
                }
            }
        });
    });

    function wrapText(context, text, x, y, maxWidth, lineHeight) {
        var lines = text.split('\n');
        var fontSize = 40;
        var adjustedLineHeight = lineHeight * 1.2; // Adjust line height by 1.2 times

        // Calculate the initial font size
        context.font = fontSize + 'px Arial';

        // Adjust font size if necessary
        var isTooWide = false;
        do {
            isTooWide = false;
            context.font = fontSize + 'px Arial';
            for (var i = 0; i < lines.length; i++) {
                var testWidth = context.measureText(lines[i]).width;
                if (testWidth > maxWidth) {
                    isTooWide = true;
                    fontSize--;
                    break;
                }
            }
        } while (isTooWide && fontSize > 10);

        // Draw text lines with adjusted line height
        for (var i = 0; i < lines.length; i++) {
            context.fillText(lines[i], x, y + (i * adjustedLineHeight) - ((lines.length - 1) * adjustedLineHeight / 2));
        }
    }
});
