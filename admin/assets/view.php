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
		<p>Test your EtchMail API connection:</p>
		<button type="button" id="test-connection" class="button">Test Connection</button>
		<div id="connection-result" style="margin-top: 10px;"></div>
	</div>

	<div class="card" style="margin-top: 20px;">
		<h2>Setup Instructions</h2>
		<ol>
			<li><strong>Get your API credentials</strong>:
				<ul>
					<li>Log into your EtchMail backend</li>
					<li>Go to "Configuration" → "API keys"</li>
					<li>Copy your Private API Key (API 2.0 only needs the private key)</li>
				</ul>
			</li>
			<li><strong>Configure the plugin</strong>:
				<ul>
					<li>Enter your EtchMail API URL (e.g., https://yourdomain.com/api)</li>
					<li>Enter your Private API Key</li>
					<li>Save the settings</li>
				</ul>
			</li>
			<li><strong>Configure Contact Form 7</strong>:
				<ul>
					<li>Edit any Contact Form 7 form</li>
					<li>Go to the "EtchMail Integration" tab</li>
					<li>Enable integration and select a mailing list</li>
					<li>Map your form fields to EtchMail subscriber fields</li>
				</ul>
			</li>
		</ol>
	</div>
</div>
<?php $nonce = wp_create_nonce('etchmail_nonce'); ?>
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
                    nonce: "<?php echo esc_js($nonce); ?>"
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