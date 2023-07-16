jQuery(document).ready(function($) {
    $('#youtube-channel-submit').click(function(e) {
        e.preventDefault();

        var channelName = $('#youtube-channel-name').val();

        if (channelName) {
            $.ajax({
                type: 'GET',
                url: 'https://www.googleapis.com/youtube/v3/channels',
                data: {
                    part: 'snippet',
                    forUsername: channelName,
                    key: 'AIzaSyA_rOzyU9duJzIQZkFrBX35YkO3SvgWoDo' // Replace with your YouTube API key
                },
                success: function(response) {
                    if (response.items && response.items.length > 0) {
                        var channelId = response.items[0].id;
                        $('#youtube-channel-result').text('Channel ID: ' + channelId);
                    } else {
                        $('#youtube-channel-result').text('Unable to retrieve channel ID.');
                    }
                },
                error: function() {
                    $('#youtube-channel-result').text('An error occurred. Please try again.');
                }
            });
        } else {
            $('#youtube-channel-result').text('Please enter a YouTube channel name.');
        }
    });
});