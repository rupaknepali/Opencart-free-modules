$(document).ready(function() {
    $('#button-generate').on('click', function() {
        const productName = $('#input-name-1').val();
        const model = $('#input-model').val();
        
        if (!productName) {
            alert('Please enter a product name first');
            return;
        }
        
        $.ajax({
            url: 'index.php?route=extension/ai_description_generator/module/ai_description_generator.generate&user_token=' + $('#form-product [name=\'user_token\']').val(),
            type: 'post',
            data: {
                'product_name': productName,
                'model': model
            },
            dataType: 'json',
            beforeSend: function() {
                $('#button-generate').button('loading');
            },
            complete: function() {
                $('#button-generate').button('reset');
            },
            success: function(json) {
                if (json['error']) {
                    alert(json['error']);
                }
                
                if (json['description']) {
                    // Convert markdown to HTML
                    let formattedDesc = json['description']
                        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')  // Bold
                        .replace(/\*(.*?)\*/g, '<em>$1</em>')              // Italic
                        .replace(/^\s*- (.*$)/gm, '<li>$1</li>')            // Unordered lists
                        .replace(/^\s*\d+\. (.*$)/gm, '<li>$1</li>')      // Ordered lists
                        .replace(/\n\n/g, '<br><br>')                      // Paragraphs
                        .replace(/\n/g, '<br>');                           // Line breaks
                    
                    // Set value and trigger change
                    $('#input-description-1').val(formattedDesc);
                    $('#input-description-1').trigger('keyup');

                    console.log(formattedDesc);
                    
                    // For WYSIWYG editors:
                    if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['input-description-1']) {
                        CKEDITOR.instances['input-description-1'].setData(formattedDesc);
                    }
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    });
});
