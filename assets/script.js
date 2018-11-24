setTimeout(function () {
    $('.alert').remove();
}, 3000);

var loginTimestamp = $.cookie("login_timestamp");
if (loginTimestamp !== undefined) {
    var allowedTime = $.cookie("allowed_session_time");
    var text = $('.navbar-brand').text();
    setInterval(function () {
        var currTime = Math.floor(Date.now() / 1000);
        var timeToLogout = allowedTime - (currTime - loginTimestamp);
        if (timeToLogout <= 0) {
            window.location.href = window.location.origin + window.location.pathname;
            return;
        }

        var  newText = text + ' - ' + timeToLogout;
        $('.navbar-brand').text(newText);
        console.log(newText);
    }, 500);
}


