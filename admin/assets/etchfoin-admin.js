document.addEventListener("DOMContentLoaded", function () {
    const button = document.getElementById("test-connection");
    const result = document.getElementById("connection-result");

    if (!button) return;

    button.addEventListener("click", function () {
        button.disabled = true;
        button.textContent = "Testing...";
        result.innerHTML = "";

        fetch(etchfoinData.ajaxUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                action: "test_etchmail_connection",
                nonce: etchfoinData.nonce
            })
        })
            .then(res => res.json())
            .then(data => {
                const noticeClass = data.success ? 'notice-success' : 'notice-error';
                result.innerHTML = `<div class="notice ${noticeClass}"><p>${data.data}</p></div>`;
            })
            .catch(() => {
                result.innerHTML = `<div class="notice notice-error"><p>AJAX error. Please try again.</p></div>`;
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = "Test Connection";
            });
    });
});