jQuery(document).ready(function($) {
    $('.feedback-btn').click(function() {
        var feedback = $(this).data('feedback');
        var post_id = $(this).data('post-id');

        var data = {
            'action': 'save_feedback',
            'feedback': feedback,
            'post_id': post_id,
            'nonce': feedback_script_params.nonce,
        };

        $.ajax({
            url: feedback_script_params.ajaxurl + '?rest_route=/feedback/v1/save',
            type: 'POST',
            data: data,
            success: function(response) {
                console.log('Feedback submitted successfully');
            },
            error: function(error) {
                console.log('Error submitting feedback');
            }
        });
    });
});
