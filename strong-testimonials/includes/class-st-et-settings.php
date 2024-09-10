<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ST_ET_Settings {

	const TAB_NAME = 'migrate';

	const OPTION_NAME = 'wpmtst_st_et_options';

	const GROUP_NAME = 'wpmtst-st-et-settings-group';

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'wpmtst_settings_tabs', array( $this, 'register_tab' ), 5, 2 );
			add_filter( 'wpmtst_submenu_pages', array( $this, 'add_submenu' ) );
			add_filter( 'wpmtst_settings_callbacks', array( $this, 'register_settings_page' ) );
			add_action( 'admin_head', array( $this, 'admin_scripts' ) );
			add_action( 'wp_ajax_wpmtst_import_from_easy_testimonials', array( $this, 'import_et_testimonials' ) );
		}
	}

	/**
	 * Register settings tab.
	 *
	 * @param $active_tab
	 * @param $url
	 *
	 * @since 1.0.0
	 */
	public function register_tab( $active_tab, $url ) {
		printf(
			'<a href="%s" class="nav-tab %s">%s</a>',
			esc_url( add_query_arg( 'tab', self::TAB_NAME, $url ) ),
			esc_attr( self::TAB_NAME === $active_tab ? 'nav-tab-active' : '' ),
			esc_html_x( 'Migrate', 'adjective', 'et-st-migrator' )
		);
	}

	/**
	 * Register settings page.
	 *
	 * @param $pages
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	public function register_settings_page( $pages ) {
		$pages[ self::TAB_NAME ] = array( $this, 'settings_page' );
		return $pages;
	}

	/**
	 * Print settings page.
	 *
	 * @since 1.0.0
	 */
	public function settings_page() {
		//settings_fields( self::GROUP_NAME );
		?>
		<h2><?php esc_html_e( 'Migrate from Easy Testimonials', 'et-st-migrator' ); ?></h2>
		
		<table class="form-table" cellpadding="0" cellspacing="0">
		
			<tr valign="top">
				<td>
					<?php
					printf(
						/* translators: 1: Opening span tag for notice, 2: Number of Easy Testimonials posts, 3: Closing span tag */
						wp_kses_post( __( '%1$s %2$s Easy Testimonials posts have been found. %3$s', 'et-st-migrator' ) ),
						'<span class="wpmtst_import_notice wpmtst-alert">',
						absint( $this->easy_testimonials_count() ),
						'</span>',
					);
					?>
				</td>
			</tr>
			<tr valign="top">
				<td>
					<hr>
				</td>
			</tr>
			<tr valign="top">
				<td>
					<fieldset>
					<label>
						<input type="checkbox" id="wpmtst-migrator-delete-imported" name="wpmtst-migrator-delete-imported">
						<?php esc_html_e( 'Delete the imported Easy Testimonials', 'et-st-migrator' ); ?>
					</label>
					</fieldset>
					<fieldset>
					<label>
						<input type="checkbox" id="wpmtst-migrator-skip-imported" name="wpmtst-migrator-skip-imported">
						<?php esc_html_e( 'Skip already imported Easy Testimonials', 'et-st-migrator' ); ?>
					</label>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<td>
					<span id="wpmtst-import-response" class="wpmtst-alert" style="display:none;"></span>
					<a class="button button-primary" id="wpmtst_import_testimonials"><?php esc_html_e( 'Import Testimonials', 'et-st-migrator' ); ?></a>
				</td>
			</tr>
		</table>
		<?php
	}
	/**
	 * Add submenu page.
	 *
	 * @param $pages
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	public function add_submenu( $pages ) {
		$pages[45] = self::get_submenu();
		return $pages;
	}

	/**
	 * Return submenu page parameters.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function get_submenu() {
		return array(
			'page_title' => apply_filters( 'wpmtst_et_migrator_page_title', esc_html__( 'Migrate', 'et-st-migrator' ) ),
			'menu_title' => apply_filters( 'wpmtst_et_migrator_menu_title', esc_html__( 'Migrate', 'et-st-migrator' ) ),
			'capability' => 'strong_testimonials_options',
			'menu_slug'  => 'edit.php?post_type=wpm-testimonial&page=testimonial-settings&tab=migrate',
			'function'   => '',
		);
	}


	/**
	 * Imports testimonials via Ajax call
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */

	public function import_et_testimonials() {

		if ( ! wp_doing_ajax() ) {
			return;
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wpmtst_migrate_from_easy_testimonials' ) ) {
			wp_send_json_error( __( 'Import failed! Nonce validation failed.', 'et-st-migrator' ) );
			die();
		}

		$imported  = 0;
		$skipped   = 0;
		$args      = array(
			'numberposts' => -1,
			'post_type'   => 'testimonial',
			'order'       => 'ASC',
			'orderby'     => 'title',
		);
			$posts = get_posts( $args );

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {

				if ( isset( $_POST['skip_imported'] ) && 1 === absint( $_POST['skip_imported'] ) ) {
					$query = new WP_Query(
						array(
							'post_type'  => 'wpm-testimonial',
							'meta_key'   => 'wpmtst_old_et_id',
							'meta_value' => absint( $post->ID ),
						)
					);

					//check for already imported
					if ( $query->found_posts > 0 ) {

						++$skipped;

						//skipping import
						continue;
					}
				}
				// Create post object
				$new_post  = array(
					'post_title'   => wp_strip_all_tags( $post->post_title ),
					'post_content' => $post->post_content,
					'post_status'  => 'publish',
					'post_author'  => 1,
					'post_date'    => $post->post_date,
					'post_type'    => 'wpm-testimonial',
				);
				$post_meta = get_post_meta( $post->ID );

				// Insert testimonial into the post database table
				$new_post_id = wp_insert_post( $new_post );

				// Insert testimonial metas into the post_meta database table
				if ( isset( $post_meta['_thumbnail_id'][0] ) ) {
					add_post_meta( $new_post_id, '_thumbnail_id', $post_meta['_thumbnail_id'][0] );
				}
				if ( isset( $post_meta['_ikcf_client'][0] ) ) {
					add_post_meta( $new_post_id, 'client_name', $post_meta['_ikcf_client'][0] );
				}
				if ( isset( $post_meta['_ikcf_email'][0] ) ) {
					add_post_meta( $new_post_id, 'email', $post_meta['_ikcf_email'][0] );
				}
				if ( isset( $post_meta['_ikcf_rating'][0] ) ) {
					add_post_meta( $new_post_id, 'star_rating', $post_meta['_ikcf_rating'][0] );
				}

				add_post_meta( $new_post_id, 'nofollow', 'default' );
				add_post_meta( $new_post_id, 'noopener', 'default' );
				add_post_meta( $new_post_id, 'noreferrer', 'default' );

				// Set the old Easy Testimonials id so we can skip the import
				add_post_meta( $new_post_id, 'wpmtst_old_et_id', $post->ID );

				$terms = get_the_terms( $post->ID, 'easy-testimonial-category' );
				if ( ! empty( $terms ) ) {
					$categories = array();
					foreach ( $terms as $term ) {

						// Check if the imported category already exists
						$cat = term_exists( $term->name, 'wpm-testimonial-category' );
						if ( ! $cat ) {

							// Check if the category is a subcategory
							$args['parent'] = 0;
							if ( 0 !== absint( $term->parent ) ) {

								//get the name of the parent cat from ET
								$et_parent_name = get_term( $term->parent, 'easy-testimonial-category' )->name;

								//get the matching cat(by name) ID from ST
								$args['parent'] = get_term_by( 'name', $et_parent_name, 'wpm-testimonial-category' )->term_id;
							}
							$categories[] = wp_insert_term( $term->name, 'wpm-testimonial-category', $args )['term_id'];
						} else {
							$categories[] = $cat['term_id'];
						}
					}
					wp_set_post_terms( $new_post_id, $categories, 'wpm-testimonial-category', false );
				}

				++$imported;

				if ( isset( $_POST['delete_imported'] ) && ( 1 === absint( $_POST['delete_imported'] ) ) ) {
					wp_delete_post( $post->ID );
				}
			}
			/* translators: 1: Number of testimonials imported, 2: Number of testimonials skipped */
			wp_send_json_success( sprintf( __( 'Import finished! %1$s testimonials were imported and %2$s testimonials were skipped.', 'et-st-migrator' ), absint( $imported ), absint( $skipped ) ) );
			wp_die();
		}

		wp_send_json_error( __( 'Import failed! Please try again.', 'et-st-migrator' ) );
		wp_die();
	}


	/**
	 * Enqueues admin js scripts and css styles
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function admin_scripts() {
		if ( isset( $_GET['page'] ) && isset( $_GET['tab'] ) && 'testimonial-settings' === $_GET['page'] && 'migrate' === $_GET['tab'] ) {
			wp_enqueue_script( 'wpmtst-st-et-migrate', WPMTST_ET_MIGRATOR_URL . 'assets/js/st-et-migrate.js', array( 'jquery' ), WPMTST_ET_MIGRATOR_VERSION, true );
			wp_add_inline_script(
				'wpmtst-st-et-migrate',
				'const wpmtst = ' . json_encode(
					array(
						'ajaxUrl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'wpmtst_migrate_from_easy_testimonials' ),
					)
				),
				'before'
			);

			wp_enqueue_style( 'wpmtst-st-et-migrate', WPMTST_ET_MIGRATOR_URL . 'assets/css/st-et-migrate.css', array(), WPMTST_ET_MIGRATOR_VERSION );
		}
	}

	/**
	 * Returns number of East Testimonials posts.
	 *
	 * @return int
	 *
	 * @since 1.0.0
	 */
	public function easy_testimonials_count() {
		if ( post_type_exists( 'testimonial' ) ) {
			return wp_count_posts( 'testimonial' )->publish;
		}
		return 0;
	}
}

new ST_ET_Settings();