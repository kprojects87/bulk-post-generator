<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BPG_Admin {

	const MENU_SLUG = 'bulk-post-generator';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'maybe_save_settings' ) );

		add_action( 'wp_ajax_bpg_get_topics', array( $this, 'ajax_get_topics' ) );
		add_action( 'wp_ajax_bpg_generate_post', array( $this, 'ajax_generate_post' ) );
	}

	public function register_menu() {
		add_menu_page(
			__( 'Bulk Post Generator', 'bulk-post-generator' ),
			__( 'Post Generator', 'bulk-post-generator' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' ),
			'dashicons-edit-large',
			26
		);
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, self::MENU_SLUG ) === false ) {
			return;
		}
		wp_enqueue_style( 'bpg-admin', BPG_URL . 'assets/css/admin.css', array(), BPG_VERSION );
		wp_enqueue_script( 'bpg-admin', BPG_URL . 'assets/js/admin.js', array( 'jquery' ), BPG_VERSION, true );
		wp_localize_script(
			'bpg-admin',
			'BPG',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'bpg_nonce' ),
				'i18n'    => array(
					'gettingTopics' => __( 'Coming up with 6 titles…', 'bulk-post-generator' ),
					'writing'       => __( 'Writing', 'bulk-post-generator' ),
					'done'          => __( 'All done! Review your drafts below.', 'bulk-post-generator' ),
					'error'         => __( 'Something went wrong', 'bulk-post-generator' ),
				),
			)
		);
	}

	public function maybe_save_settings() {
		if ( ! isset( $_POST['bpg_settings_nonce'] ) || ! wp_verify_nonce( $_POST['bpg_settings_nonce'], 'bpg_save_settings' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = array(
			'generate_images'   => ! empty( $_POST['generate_images'] ),
			'default_category'  => intval( $_POST['default_category'] ?? 0 ),
			'post_status'       => sanitize_text_field( $_POST['post_status'] ?? 'draft' ),
			'word_count'        => sanitize_text_field( $_POST['word_count'] ?? 'medium' ),
			'batch_size'        => max( 1, min( 10, intval( $_POST['batch_size'] ?? BPG_DEFAULT_BATCH ) ) ),
			'business_name'     => sanitize_text_field( $_POST['business_name'] ?? '' ),
			'business_type'     => $this->resolve_business_type( $_POST ),
		);

		update_option( 'bpg_settings', $settings );
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'bulk-post-generator' ) . '</p></div>';
		} );
	}

	/**
	 * Common business/blog type options shown in the dropdown.
	 */
	public static function get_business_types() {
		return array(
			'e-commerce / online store',
			'restaurant or cafe',
			'real estate',
			'fitness / gym / wellness',
			'healthcare / medical practice',
			'legal / law firm',
			'technology / SaaS',
			'travel / tourism',
			'fashion / beauty',
			'finance / accounting',
			'education / e-learning',
			'home services / contracting',
			'marketing / creative agency',
			'nonprofit',
		);
	}

	/**
	 * Resolve the business type from POST data, honoring a custom "Other" value.
	 */
	private function resolve_business_type( $data ) {
		$type = sanitize_text_field( wp_unslash( $data['business_type'] ?? '' ) );
		if ( 'other' === $type ) {
			$type = sanitize_text_field( wp_unslash( $data['business_type_other'] ?? '' ) );
		}
		return $type;
	}

	/* ---------------------------------------------------------------
	 * AJAX handlers
	 * ------------------------------------------------------------- */

	public function ajax_get_topics() {
		check_ajax_referer( 'bpg_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'bulk-post-generator' ) ) );
		}

		$niche         = sanitize_text_field( wp_unslash( $_POST['niche'] ?? '' ) );
		$keywords      = sanitize_text_field( wp_unslash( $_POST['keywords'] ?? '' ) );
		$count         = intval( $_POST['count'] ?? BPG_DEFAULT_BATCH );
		$business_name = sanitize_text_field( wp_unslash( $_POST['business_name'] ?? '' ) );
		$business_type = $this->resolve_business_type( $_POST );

		if ( empty( $niche ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a topic/niche.', 'bulk-post-generator' ) ) );
		}

		$generator = new BPG_Generator();
		$topics    = $generator->get_topics( $niche, $keywords, $count, $business_name, $business_type );

		if ( is_wp_error( $topics ) ) {
			wp_send_json_error( array( 'message' => $topics->get_error_message() ) );
		}

		wp_send_json_success( array( 'topics' => $topics ) );
	}

	public function ajax_generate_post() {
		check_ajax_referer( 'bpg_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'bulk-post-generator' ) ) );
		}

		$title         = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
		$niche         = sanitize_text_field( wp_unslash( $_POST['niche'] ?? '' ) );
		$status        = sanitize_text_field( $_POST['status'] ?? 'draft' );
		$business_name = sanitize_text_field( wp_unslash( $_POST['business_name'] ?? '' ) );
		$business_type = $this->resolve_business_type( $_POST );
		$settings      = get_option( 'bpg_settings', array() );
		$category      = intval( $settings['default_category'] ?? 0 );

		if ( empty( $title ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing title.', 'bulk-post-generator' ) ) );
		}

		$generator = new BPG_Generator();
		$result    = $generator->generate_post( $title, $niche, $category, $status, $business_name, $business_type );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/* ---------------------------------------------------------------
	 * Rendering
	 * ------------------------------------------------------------- */

	public function render_page() {
		$settings = wp_parse_args(
			get_option( 'bpg_settings', array() ),
			array(
				'generate_images'  => false,
				'default_category' => 0,
				'post_status'      => 'draft',
				'word_count'       => 'medium',
				'batch_size'       => BPG_DEFAULT_BATCH,
				'business_name'    => '',
				'business_type'    => '',
			)
		);

		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'generate';
		?>
		<div class="wrap bpg-wrap">
			<div class="bpg-header">
				<div class="bpg-header-left">
					<span class="dashicons dashicons-edit-large bpg-logo"></span>
					<div>
						<h1><?php esc_html_e( 'Bulk Post Generator', 'bulk-post-generator' ); ?></h1>
						<p><?php esc_html_e( 'Generate 6 blog post drafts in one click — no API needed.', 'bulk-post-generator' ); ?></p>
					</div>
				</div>
			</div>

			<nav class="bpg-tabs">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG . '&tab=generate' ) ); ?>" class="bpg-tab <?php echo 'generate' === $tab ? 'active' : ''; ?>">
					<?php esc_html_e( 'Generate Posts', 'bulk-post-generator' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG . '&tab=history' ) ); ?>" class="bpg-tab <?php echo 'history' === $tab ? 'active' : ''; ?>">
					<?php esc_html_e( 'History', 'bulk-post-generator' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG . '&tab=settings' ) ); ?>" class="bpg-tab <?php echo 'settings' === $tab ? 'active' : ''; ?>">
					<?php esc_html_e( 'Settings', 'bulk-post-generator' ); ?>
				</a>
			</nav>

			<div class="bpg-content">
				<?php
				if ( 'settings' === $tab ) {
					$this->render_settings_tab( $settings );
				} elseif ( 'history' === $tab ) {
					$this->render_history_tab();
				} else {
					$this->render_generate_tab( $settings );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a <select> of common business/blog types with an "Other" option.
	 */
	private function render_business_type_select( $id, $selected ) {
		$types    = self::get_business_types();
		$is_known = in_array( $selected, $types, true );
		?>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>">
			<option value=""><?php esc_html_e( 'General / not specified', 'bulk-post-generator' ); ?></option>
			<?php foreach ( $types as $type ) : ?>
				<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $selected, $type ); ?>>
					<?php echo esc_html( ucfirst( $type ) ); ?>
				</option>
			<?php endforeach; ?>
			<option value="other" <?php selected( ! $is_known && ! empty( $selected ), true ); ?>>
				<?php esc_html_e( 'Other (describe below)', 'bulk-post-generator' ); ?>
			</option>
		</select>
		<?php if ( ! $is_known && ! empty( $selected ) ) : ?>
			<input type="hidden" class="bpg-business-type-preset-other" value="<?php echo esc_attr( $selected ); ?>" />
		<?php endif; ?>
		<?php
	}

	private function render_generate_tab( $settings ) {
		?>
		<div class="bpg-grid">
			<div class="bpg-card bpg-card-main">
				<h2><?php esc_html_e( 'New batch', 'bulk-post-generator' ); ?></h2>

				<div class="bpg-alert bpg-alert-info">
					<?php
					if ( ! empty( $settings['generate_images'] ) ) {
						esc_html_e( 'No API key needed — titles and content come from built-in templates, and each post gets a free stock photo as its featured image.', 'bulk-post-generator' );
					} else {
						esc_html_e( 'No API key needed — posts are generated instantly from built-in templates. Content is generic and meant as a starting draft; review and personalize before publishing.', 'bulk-post-generator' );
					}
					?>
				</div>

				<div class="bpg-field-row">
					<div class="bpg-field">
						<label for="bpg-business-name"><?php esc_html_e( 'Business name (optional)', 'bulk-post-generator' ); ?></label>
						<input type="text" id="bpg-business-name" value="<?php echo esc_attr( $settings['business_name'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. Brew & Bean Coffee Co.', 'bulk-post-generator' ); ?>" />
					</div>
					<div class="bpg-field">
						<label for="bpg-business-type"><?php esc_html_e( 'Blog / business type', 'bulk-post-generator' ); ?></label>
						<?php $this->render_business_type_select( 'bpg-business-type', $settings['business_type'] ); ?>
					</div>
				</div>

				<div class="bpg-field" id="bpg-business-type-other-wrap" style="display:none;">
					<label for="bpg-business-type-other"><?php esc_html_e( 'Describe the business/blog type', 'bulk-post-generator' ); ?></label>
					<input type="text" id="bpg-business-type-other" placeholder="<?php esc_attr_e( 'e.g. pet grooming salon', 'bulk-post-generator' ); ?>" />
				</div>

				<div class="bpg-field">
					<label for="bpg-niche"><?php esc_html_e( 'Blog topic / niche', 'bulk-post-generator' ); ?></label>
					<input type="text" id="bpg-niche" placeholder="<?php esc_attr_e( 'e.g. home coffee brewing', 'bulk-post-generator' ); ?>" />
				</div>

				<div class="bpg-field">
					<label for="bpg-keywords"><?php esc_html_e( 'Keywords / themes (optional)', 'bulk-post-generator' ); ?></label>
					<input type="text" id="bpg-keywords" placeholder="<?php esc_attr_e( 'e.g. espresso, pour over, grinders', 'bulk-post-generator' ); ?>" />
				</div>

				<div class="bpg-field-row">
					<div class="bpg-field">
						<label for="bpg-count"><?php esc_html_e( 'Number of posts', 'bulk-post-generator' ); ?></label>
						<input type="number" id="bpg-count" min="1" max="10" value="<?php echo esc_attr( $settings['batch_size'] ); ?>" />
					</div>
					<div class="bpg-field">
						<label for="bpg-status"><?php esc_html_e( 'Post status', 'bulk-post-generator' ); ?></label>
						<select id="bpg-status">
							<option value="draft" <?php selected( $settings['post_status'], 'draft' ); ?>><?php esc_html_e( 'Draft', 'bulk-post-generator' ); ?></option>
							<option value="pending" <?php selected( $settings['post_status'], 'pending' ); ?>><?php esc_html_e( 'Pending review', 'bulk-post-generator' ); ?></option>
							<option value="publish" <?php selected( $settings['post_status'], 'publish' ); ?>><?php esc_html_e( 'Publish', 'bulk-post-generator' ); ?></option>
						</select>
					</div>
				</div>

				<p class="description">
					<?php
					printf(
						/* translators: %s settings link */
						esc_html__( 'New posts use your default category from %s. You can change that anytime.', 'bulk-post-generator' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG . '&tab=settings' ) ) . '">' . esc_html__( 'Settings', 'bulk-post-generator' ) . '</a>'
					);
					?>
				</p>

				<button id="bpg-generate-btn" class="button button-primary button-hero">
					<?php esc_html_e( 'Generate 6 Posts', 'bulk-post-generator' ); ?>
				</button>

				<div id="bpg-progress" class="bpg-progress" style="display:none;">
					<div class="bpg-progress-bar"><div class="bpg-progress-fill" id="bpg-progress-fill"></div></div>
					<p id="bpg-progress-label"></p>
				</div>

				<ul id="bpg-results" class="bpg-results"></ul>
			</div>

			<div class="bpg-card bpg-card-side">
				<h3><?php esc_html_e( 'How it works', 'bulk-post-generator' ); ?></h3>
				<ol class="bpg-steps">
					<li><?php esc_html_e( 'Enter a topic and optional keywords.', 'bulk-post-generator' ); ?></li>
					<li><?php esc_html_e( 'Templates build distinct post titles.', 'bulk-post-generator' ); ?></li>
					<li><?php esc_html_e( 'Each title is expanded into a full draft.', 'bulk-post-generator' ); ?></li>
					<li><?php esc_html_e( 'Review and publish from your Posts list.', 'bulk-post-generator' ); ?></li>
				</ol>
			</div>
		</div>
		<?php
	}

	private function render_settings_tab( $settings ) {
		?>
		<form method="post" class="bpg-card bpg-settings-form">
			<?php wp_nonce_field( 'bpg_save_settings', 'bpg_settings_nonce' ); ?>

			<div class="bpg-alert bpg-alert-info">
				<?php esc_html_e( 'This plugin generates content locally from built-in templates — no API key, account, or external AI service is used.', 'bulk-post-generator' ); ?>
			</div>

			<h2><?php esc_html_e( 'Featured Images', 'bulk-post-generator' ); ?></h2>
			<div class="bpg-field bpg-checkbox-field">
				<label>
					<input type="checkbox" name="generate_images" id="generate_images" value="1" <?php checked( ! empty( $settings['generate_images'] ) ); ?> />
					<?php esc_html_e( 'Add a featured image to each generated post.', 'bulk-post-generator' ); ?>
				</label>
			</div>
			<p class="description" style="margin-top:-6px;">
				<?php esc_html_e( 'Uses a free stock photo service — no API key required.', 'bulk-post-generator' ); ?>
			</p>

			<h2><?php esc_html_e( 'Business Defaults', 'bulk-post-generator' ); ?></h2>
			<p class="description" style="margin-top:-8px;"><?php esc_html_e( 'Pre-fills the Generate Posts tab. You can still override these per batch.', 'bulk-post-generator' ); ?></p>
			<div class="bpg-field-row">
				<div class="bpg-field">
					<label for="business_name"><?php esc_html_e( 'Business name', 'bulk-post-generator' ); ?></label>
					<input type="text" name="business_name" id="business_name" value="<?php echo esc_attr( $settings['business_name'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. Brew & Bean Coffee Co.', 'bulk-post-generator' ); ?>" />
				</div>
				<div class="bpg-field">
					<label for="business_type"><?php esc_html_e( 'Blog / business type', 'bulk-post-generator' ); ?></label>
					<?php $this->render_business_type_select( 'business_type', $settings['business_type'] ); ?>
				</div>
			</div>
			<div class="bpg-field" id="business_type_other_wrap" style="display:none;">
				<label for="business_type_other"><?php esc_html_e( 'Describe the business/blog type', 'bulk-post-generator' ); ?></label>
				<input type="text" name="business_type_other" id="business_type_other" placeholder="<?php esc_attr_e( 'e.g. pet grooming salon', 'bulk-post-generator' ); ?>" />
			</div>

			<h2><?php esc_html_e( 'Generation Defaults', 'bulk-post-generator' ); ?></h2>
			<div class="bpg-field-row">
				<div class="bpg-field">
					<label for="batch_size"><?php esc_html_e( 'Default batch size', 'bulk-post-generator' ); ?></label>
					<input type="number" name="batch_size" id="batch_size" min="1" max="10" value="<?php echo esc_attr( $settings['batch_size'] ); ?>" />
				</div>
				<div class="bpg-field">
					<label for="word_count"><?php esc_html_e( 'Post length', 'bulk-post-generator' ); ?></label>
					<select name="word_count" id="word_count">
						<option value="short" <?php selected( $settings['word_count'], 'short' ); ?>><?php esc_html_e( 'Short (~500 words)', 'bulk-post-generator' ); ?></option>
						<option value="medium" <?php selected( $settings['word_count'], 'medium' ); ?>><?php esc_html_e( 'Medium (~800 words)', 'bulk-post-generator' ); ?></option>
						<option value="long" <?php selected( $settings['word_count'], 'long' ); ?>><?php esc_html_e( 'Long (~1400 words)', 'bulk-post-generator' ); ?></option>
					</select>
				</div>
				<div class="bpg-field">
					<label for="post_status"><?php esc_html_e( 'Default post status', 'bulk-post-generator' ); ?></label>
					<select name="post_status" id="post_status">
						<option value="draft" <?php selected( $settings['post_status'], 'draft' ); ?>><?php esc_html_e( 'Draft', 'bulk-post-generator' ); ?></option>
						<option value="pending" <?php selected( $settings['post_status'], 'pending' ); ?>><?php esc_html_e( 'Pending review', 'bulk-post-generator' ); ?></option>
						<option value="publish" <?php selected( $settings['post_status'], 'publish' ); ?>><?php esc_html_e( 'Publish', 'bulk-post-generator' ); ?></option>
					</select>
				</div>
			</div>

			<div class="bpg-field">
				<label for="default_category"><?php esc_html_e( 'Default category', 'bulk-post-generator' ); ?></label>
				<?php
				wp_dropdown_categories(
					array(
						'id'               => 'default_category',
						'name'             => 'default_category',
						'hide_empty'       => false,
						'selected'         => $settings['default_category'],
						'show_option_none' => __( 'Uncategorized', 'bulk-post-generator' ),
					)
				);
				?>
			</div>

			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'bulk-post-generator' ); ?></button>
		</form>
		<?php
	}

	private function render_history_tab() {
		$query = new WP_Query(
			array(
				'post_type'      => 'post',
				'posts_per_page' => 20,
				'meta_key'       => '_bpg_generated',
				'meta_value'     => 1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		?>
		<div class="bpg-card">
			<h2><?php esc_html_e( 'Recently generated posts', 'bulk-post-generator' ); ?></h2>
			<?php if ( ! $query->have_posts() ) : ?>
				<p><?php esc_html_e( 'No generated posts yet. Head to the Generate Posts tab to create your first batch.', 'bulk-post-generator' ); ?></p>
			<?php else : ?>
				<table class="widefat striped bpg-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Title', 'bulk-post-generator' ); ?></th>
							<th><?php esc_html_e( 'Niche', 'bulk-post-generator' ); ?></th>
							<th><?php esc_html_e( 'Business', 'bulk-post-generator' ); ?></th>
							<th><?php esc_html_e( 'Image', 'bulk-post-generator' ); ?></th>
							<th><?php esc_html_e( 'Status', 'bulk-post-generator' ); ?></th>
							<th><?php esc_html_e( 'Date', 'bulk-post-generator' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php while ( $query->have_posts() ) : $query->the_post(); ?>
							<tr>
								<td><?php the_title(); ?></td>
								<td><?php echo esc_html( get_post_meta( get_the_ID(), '_bpg_niche', true ) ); ?></td>
								<td><?php echo esc_html( get_post_meta( get_the_ID(), '_bpg_business_name', true ) ?: '—' ); ?></td>
								<td><?php echo has_post_thumbnail() ? '✅' : '—'; ?></td>
								<td><span class="bpg-badge bpg-badge-<?php echo esc_attr( get_post_status() ); ?>"><?php echo esc_html( ucfirst( get_post_status() ) ); ?></span></td>
								<td><?php echo esc_html( get_the_date() ); ?></td>
								<td><a href="<?php echo esc_url( get_edit_post_link() ); ?>"><?php esc_html_e( 'Edit', 'bulk-post-generator' ); ?></a></td>
							</tr>
						<?php endwhile; wp_reset_postdata(); ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
