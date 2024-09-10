<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ST_ET_Migrator {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( class_exists( 'Strong_Testimonials' ) ) {

			if ( is_admin() ) {

				//require migrator admin interface and settings
				require_once WPMTST_ET_MIGRATOR_PATH . 'includes/class-st-et-settings.php';
			}
		} else {
			if ( is_admin() ) {

				add_action( 'wp_ajax_wpmtst-dismiss-plugin-missing-notice', array( $this, 'ajax' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
				add_action( 'admin_print_footer_scripts', array( $this, 'ajax_script' ) );

				//show Strong Testimonials plugin required notice
				add_action( 'admin_notices', array( $this, 'wpmtst_plugin_missing_notice' ), 8 );
			}
		}
	}


	/**
	 * BETA testers notice
	 *
	 * @since 1.0.0
	 */
	public function wpmtst_plugin_missing_notice() {
		if ( get_option( 'wpmtst_plugin_missing_notice_hide', false ) ) {
			return;
		}
		?>
		<div data-dismissible="wpmtst-st-et-missing-notice" id="wpmtst-st-et-missing-notice" class="notice notice-warning is-dismissible" style="margin-top:30px;">
			<p><?php esc_html_e( 'Strong Testimonials - Migrate Away from Easy Testimonials addon requires Strong Testimonials plugin to be installed and activated.', 'et-st-migrator' ); ?></p>
		</div>
		<?php
	}
	/**
	 * AJAX functions
	 *
	 * @since 1.0.0
	 */
	public function ajax() {

		check_ajax_referer( 'wpmtst-st-et-missing-notice', 'security' );
		update_option( 'wpmtst_plugin_missing_notice_hide', true );
		wp_die( 'ok' );
	}

	/**
	 * Enqueue scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue() {
		wp_enqueue_script( 'jquery' );
	}

	/**
	 * AJAX script
	 *
	 * @since 1.0.0
	 */
	public function ajax_script() {
		?>

		<script type="text/javascript">
			jQuery( document ).ready( function( $ ){

				$(document).on('click','#wpmtst-st-et-missing-notice .notice-dismiss', function( ){
					var data = {
						action: 'wpmtst-dismiss-plugin-missing-notice',
						security: '<?php echo wp_create_nonce( 'wpmtst-st-et-missing-notice' ); ?>',
					};

					$.post( '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', data, function( response ) {
						$( '#wpmtst-st-et-missing-notice' ).slideUp( 'fast', function() {
							$( this ).remove();
						} );
					});

				} );

			});
		</script>

		<?php
	}
}
