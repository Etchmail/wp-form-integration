<?php defined('ABSPATH') || exit; // admin/assets/view.php
?>

<div class="wrap">
	<h1>Etchmail Form Integration</h1>

	<form action='options.php' method='post'>
		<?php
		settings_fields('EMFI');
		do_settings_sections('EMFI');
		submit_button();
		?>
	</form>

	<div class="card" style="margin-top: 20px;">
		<h2>Test Connection</h2>
		<p>Test your Etchmail API connection:</p>
		<button type="button" id="test-connection" class="button">Test Connection</button>
		<div id="connection-result" style="margin-top: 10px;"></div>
	</div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const button = document.getElementById("test-connection");
        const result = document.getElementById("connection-result");

        if (!button) return;

        button.addEventListener("click", function () {
            button.disabled = true;
            button.textContent = "Testing...";
            result.innerHTML = "";

            fetch(ajaxurl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: new URLSearchParams({
                    action: "test_etchmail_connection",
                    nonce: "<?php echo esc_js(wp_create_nonce('etchmail_nonce')); ?>"
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
</script>