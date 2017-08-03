<?php

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}


if ( is_admin() AND current_user_can( 'manage_options' ) ) {
	
	$wp_sellsy = new wp_sellsyClass();

	if ( isset( $_GET['settings-updated'] ) AND $_GET['settings-updated'] == true ) {

		if ( $wp_sellsy->thfo_checkSellsy_connect() )
			add_settings_error( 'wpsellsy_options', 'WPIupdated', __( 'Successfully connected to SellSy.', 'thfo_sellsy' ) , 'updated' );
		else
			add_settings_error( 'wpsellsy_options', 'WPItokens', __( 'Error, impossible to connect to Sellsy. Please check token.', 'thfo_sellsy' ) , 'error' );

	}
	?>
	<div class="wrap">
		<div id="icon-sellsy" class="icon32"><br /></div>
		<h2><?php _e( 'GF Sellsy Addons ' . WPI_VERSION, 'thfo_sellsy' ); ?></h2>
		<div class="adminInfo">
	        <?php _e( 'This plugin allow you to connect a specific Gravity Form to your Sellsy account. You must firstly have a Sellsy Account with prospection level. ', 'thfo_sellsy' )  ?> </br>
	        <a href="<?php echo WPI_URL ?>/img/GF-Sellsy-addons.json"><?php _e('Download the connected form here by right click "save cible as"', 'thfo_sellsy') ?></a></br>
			<a href="https://www.gravityhelp.com/documentation/article/importing-a-form-into-gravity-forms/" title="<?php _e('How-to import a form in Gravity Form','thfo_sellsy') ?>"><?php _e(' How-To import a form in Gravity Form','thfo_sellsy') ?></a></br>
			<a href="<?php echo WPI_WEBAPI_URL ?>"><?php _e('Get your API\'s keys (settings > API Access)','thfo_sellsy') ?></a>
		</div>
		<h2><?php _e('If You Enjoy this plugin... encourage me!', 'thfo_sellsy') ?></h2>
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
">
			<input type="image" src="https://www.paypalobjects.com/fr_FR/FR/i/btn/btn_donateCC_LG.gif" border="0"
			       name="submit" alt="PayPal - la solution de paiement en ligne la plus simple et la plus sécurisée !">
			<img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1">
		</form>
	    <?php
		settings_errors( 'wpsellsy_options' );
		?>

		<form id="wp-sellsy-admform" action="options.php" method="POST">
			<?php settings_fields( 'wpsellsy_options' ) ?>
			<?php do_settings_sections( $_GET['page'] ) ?>


			<?php submit_button(); ?>
			<?php 
				if ( function_exists( 'wp_nonce_field' ) ) 
					wp_nonce_field( 'wpi_nonce_field', 'wpi_nonce_verify_adm' );
			?>			
		</form>
	</div>

<?php
}
else {
	wp_die( __( 'You do not have correct permissions.', 'thfo_sellsy' ) );
}
?>