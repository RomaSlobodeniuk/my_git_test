/**
 * Remove alerts block after defined time in seconds
 */
setTimeout(function () {
    $('.messages-container').remove();
}, 7000);

var loginTimestamp = $.cookie("login_timestamp");
if (loginTimestamp !== undefined) {
    var allowedTime = $.cookie("allowed_session_time");
    var linkLogout = $('.navbar-nav a[href$="/15_git/logout"]');
    var text = linkLogout.text();
    function triggerSessionCheck() {
        return setInterval(function () {
            var currTime = Math.floor(Date.now() / 1000);
            var timeToLogout = allowedTime - (currTime - loginTimestamp);
            if (timeToLogout <= 0) {
                window.location.href = window.location.origin + window.location.pathname;
                window.clearInterval(checkSessionTime);
                return;
            }

            var  newText = text + ' (' + timeToLogout + ')';
            linkLogout.text(newText);
        }, 500);
    }

    var checkSessionTime = triggerSessionCheck();
}

$('#register-form .btn.btn-primary').on('click', function (event) {
    var ext = $('#logoFile').val().split('.').pop().toLowerCase();
    if(ext !== '' && $.inArray(ext, ['png','jpg','jpeg']) === -1) {
        event.preventDefault();
        var errorMessage = 'Invalid file extension! Please, choose another image, allowed extensions are: png, jpg, jpeg.';
        $('#invalid-file-modal .modal-body').text(errorMessage);
        $('#invalid-file').click();
    }
});

