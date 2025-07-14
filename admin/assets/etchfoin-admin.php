<?php defined( 'ABSPATH' ) || exit; ?>

<div class="wrap">
    <h1>Etchmail Form Integration</h1>

    <form action='options.php' method='post'>
		<?php
		settings_fields( 'ETCHFOIN' );
		do_settings_sections( 'ETCHFOIN' );
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