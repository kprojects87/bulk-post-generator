<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds blog post titles, content, and (optionally) featured images.
 * Everything runs locally — no external AI API of any kind. Titles and
 * content come from built-in templates; featured images (if enabled) come
 * from a free, no-key-required stock photo service.
 */
class BPG_Generator {

	private $settings;

	public function __construct() {
		$this->settings = wp_parse_args(
			get_option( 'bpg_settings', array() ),
			array(
				'generate_images'   => false,
				'default_category'  => 1,
				'post_status'       => 'draft',
				'word_count'        => 'medium',
				'batch_size'        => BPG_DEFAULT_BATCH,
			)
		);
	}

	/* =================================================================
	 * PUBLIC ENTRY POINTS
	 * ================================================================= */

	/**
	 * Build N distinct blog post titles for a niche/topic.
	 *
	 * @return array|WP_Error array of plain-text titles.
	 */
	public function get_topics( $niche, $keywords, $count, $business_name = '', $business_type = '' ) {
		$count       = max( 1, min( 10, intval( $count ) ) );
		$subject     = $business_name ? $business_name : ucfirst( $niche );
		$kw_list     = $this->split_keywords( $keywords );
		$focus_terms = ! empty( $kw_list ) ? $kw_list : array( $niche );

		$patterns = array(
			'{count} Essential Tips for {niche}',
			'The Ultimate Guide to {niche}',
			'How to Get Started with {niche}',
			'{count} Common Mistakes to Avoid in {niche}',
			'Why {niche} Matters More Than You Think',
			'A Beginner\'s Guide to {niche}',
			'{count} Ways to Improve Your {niche} Results',
			'What Nobody Tells You About {niche}',
			'{niche}: A Practical Guide for {year}',
			'Top Trends Shaping {niche} Right Now',
			'How {subject} Approaches {niche}',
			'Everything You Need to Know About {focus}',
			'{count} Questions to Ask Before You Start with {niche}',
			'Simple Habits That Improve {niche}',
			'The Do\'s and Don\'ts of {niche}',
		);

		shuffle( $patterns );
		$titles = array();
		$i      = 0;

		while ( count( $titles ) < $count && $i < count( $patterns ) * 2 ) {
			$pattern = $patterns[ $i % count( $patterns ) ];
			$focus   = $focus_terms[ $i % count( $focus_terms ) ];

			$title = strtr(
				$pattern,
				array(
					'{niche}'   => $niche,
					'{count}'   => (string) wp_rand( 5, 12 ),
					'{year}'    => gmdate( 'Y' ),
					'{subject}' => $subject,
					'{focus}'   => $focus,
				)
			);
			$title = ucfirst( $title );

			if ( ! in_array( $title, $titles, true ) ) {
				$titles[] = $title;
			}
			$i++;
		}

		if ( empty( $titles ) ) {
			return new WP_Error( 'bpg_no_titles', __( 'Could not build titles. Try a different topic.', 'bulk-post-generator' ) );
		}

		return array_slice( $titles, 0, $count );
	}

	/**
	 * Generate full post content (and optionally a featured image) for a
	 * single title, then insert it as a WP post.
	 *
	 * @return array|WP_Error { post_id, edit_link, title, has_image }
	 */
	public function generate_post( $title, $niche, $category_id, $status, $business_name = '', $business_type = '' ) {
		$content = $this->generate_content( $title, $niche, $business_name, $business_type );
		$excerpt = $this->build_excerpt( $content );

		$postarr = array(
			'post_title'   => wp_strip_all_tags( $title ),
			'post_content' => wp_kses_post( $content ),
			'post_excerpt' => $excerpt,
			'post_status'  => in_array( $status, array( 'draft', 'pending', 'publish' ), true ) ? $status : 'draft',
			'post_type'    => 'post',
		);

		if ( ! empty( $category_id ) ) {
			$postarr['post_category'] = array( intval( $category_id ) );
		}

		$post_id = wp_insert_post( $postarr, true );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_bpg_generated', 1 );
		update_post_meta( $post_id, '_bpg_niche', sanitize_text_field( $niche ) );
		if ( ! empty( $business_name ) ) {
			update_post_meta( $post_id, '_bpg_business_name', sanitize_text_field( $business_name ) );
		}
		if ( ! empty( $business_type ) ) {
			update_post_meta( $post_id, '_bpg_business_type', sanitize_text_field( $business_type ) );
		}

		$has_image = false;
		if ( ! empty( $this->settings['generate_images'] ) ) {
			$attachment_id = $this->get_stock_photo_attachment( $post_id, $title, $niche );
			if ( ! is_wp_error( $attachment_id ) && $attachment_id ) {
				set_post_thumbnail( $post_id, $attachment_id );
				$has_image = true;
			}
		}

		return array(
			'post_id'   => $post_id,
			'edit_link' => get_edit_post_link( $post_id, 'raw' ),
			'title'     => get_the_title( $post_id ),
			'has_image' => $has_image,
		);
	}

	/**
	 * Build a short plain-text excerpt from HTML post content.
	 */
	private function build_excerpt( $html, $max_len = 155 ) {
		$text = wp_strip_all_tags( $html );
		$text = preg_replace( '/\s+/', ' ', trim( $text ) );

		if ( strlen( $text ) <= $max_len ) {
			return $text;
		}

		$truncated  = substr( $text, 0, $max_len );
		$last_space = strrpos( $truncated, ' ' );
		if ( false !== $last_space ) {
			$truncated = substr( $truncated, 0, $last_space );
		}

		return rtrim( $truncated, ',.;:' ) . '…';
	}

	/* =================================================================
	 * CONTENT TEMPLATES
	 * No external calls, no cost, no key required. Uses phrase templates
	 * combined with niche/keywords/business info to build structured
	 * (if generic) draft posts.
	 * ================================================================= */

	private function generate_content( $title, $niche, $business_name = '', $business_type = '' ) {
		$intro_variants = array(
			"<p>If you've been thinking about {niche}, you're not alone. It's a topic that keeps coming up for good reason, and getting the basics right can make a real difference.</p>",
			"<p>{niche} isn't always straightforward, but with the right approach it doesn't have to be overwhelming either. Here's a practical breakdown to help you move forward with confidence.</p>",
			"<p>Whether you're just starting out or looking to sharpen what you already know, understanding {niche} properly pays off. Let's walk through what actually matters.</p>",
		);

		$section_titles = array(
			'Why It Matters',
			'Getting the Basics Right',
			'Common Pitfalls to Watch For',
			'Practical Steps You Can Take',
			'What to Keep in Mind Going Forward',
			'Tips for Long-Term Success',
		);

		$section_body_variants = array(
			"<p>Getting this right starts with understanding the fundamentals of {niche}. Skipping this step is one of the most common reasons people struggle later on.</p><ul><li>Start with a clear goal in mind</li><li>Keep track of what's working and what isn't</li><li>Don't be afraid to adjust your approach as you learn</li></ul>",
			"<p>A lot of people run into trouble with {niche} simply because they rush the early stages. Slowing down here usually pays off later.</p><ul><li>Set realistic expectations from the start</li><li>Ask for feedback where you can get it</li><li>Revisit your plan regularly rather than setting it once</li></ul>",
			"<p>Consistency tends to matter more than intensity when it comes to {niche}. Small, steady improvements add up faster than big, occasional pushes.</p><ul><li>Build a routine you can actually stick to</li><li>Track progress in a simple, low-effort way</li><li>Celebrate small wins along the way</li></ul>",
			"<p>One thing that's easy to overlook with {niche} is how much context matters — what works well in one situation may not translate directly to another.</p><ul><li>Consider your specific circumstances before copying a general approach</li><li>Test changes on a small scale first</li><li>Stay open to updating your process as you go</li></ul>",
		);

		$conclusion_variants = array(
			'<p>At the end of the day, {niche} comes down to consistent, informed effort. Start with the basics, stay patient, and adjust as you learn what works best for you.</p>',
			"<p>There's no single perfect approach to {niche}, but the fundamentals above are a solid place to start. Keep experimenting and refine your approach over time.</p>",
		);

		shuffle( $section_titles );
		shuffle( $section_body_variants );

		$num_sections = wp_rand( 3, 4 );
		$html         = '';

		$html .= strtr( $intro_variants[ array_rand( $intro_variants ) ], array( '{niche}' => $niche ) );

		for ( $i = 0; $i < $num_sections; $i++ ) {
			$html .= '<h2>' . esc_html( $section_titles[ $i % count( $section_titles ) ] ) . '</h2>';
			$html .= strtr( $section_body_variants[ $i % count( $section_body_variants ) ], array( '{niche}' => $niche ) );
		}

		$html .= strtr( $conclusion_variants[ array_rand( $conclusion_variants ) ], array( '{niche}' => $niche ) );

		if ( ! empty( $business_name ) ) {
			$closing = "<p>At <strong>{$business_name}</strong>, this is exactly the kind of thing we help people with every day" .
				( ! empty( $business_type ) ? " as a {$business_type} business" : '' ) .
				". If you'd like a hand applying any of this, feel free to reach out.</p>";
			$html   .= $closing;
		}

		$html .= "\n<!-- Generated with Bulk Post Generator. This is placeholder/template content — please review and personalize before publishing. -->";

		return $html;
	}

	/* =================================================================
	 * FEATURED IMAGES — free stock photos, no API key required
	 * ================================================================= */

	/**
	 * Fetch a free, royalty-free stock photo (Picsum/Unsplash-backed, no API
	 * key required) and add it to the media library — the same approach
	 * tools like Instant Images use for placeholder content.
	 *
	 * @return int|WP_Error Attachment ID.
	 */
	private function get_stock_photo_attachment( $post_id, $title, $niche ) {
		// A stable seed per post keeps the image consistent if this is ever
		// re-run, while still varying across the batch.
		$seed = sanitize_title( $niche ) . '-' . $post_id;
		$url  = 'https://picsum.photos/seed/' . rawurlencode( $seed ) . '/1200/675';

		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error( 'bpg_stock_photo_failed', __( 'Could not fetch a stock photo.', 'bulk-post-generator' ) );
		}

		$binary = wp_remote_retrieve_body( $response );
		if ( empty( $binary ) ) {
			return new WP_Error( 'bpg_stock_photo_empty', __( 'Stock photo service returned no data.', 'bulk-post-generator' ) );
		}

		return $this->save_binary_image( $binary, 'image/jpeg', $title );
	}

	/**
	 * Save raw binary image data to the WordPress media library.
	 *
	 * @return int|WP_Error Attachment ID.
	 */
	private function save_binary_image( $binary, $mime_type, $title ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		if ( empty( $binary ) ) {
			return new WP_Error( 'bpg_image_empty', __( 'No image data to save.', 'bulk-post-generator' ) );
		}

		$ext      = ( 'image/jpeg' === $mime_type ) ? 'jpg' : 'png';
		$filename = sanitize_file_name( wp_unique_filename( wp_upload_dir()['path'], sanitize_title( $title ) . '.' . $ext ) );

		$upload = wp_upload_bits( $filename, null, $binary );
		if ( ! empty( $upload['error'] ) ) {
			return new WP_Error( 'bpg_upload_failed', $upload['error'] );
		}

		$attachment = array(
			'post_mime_type' => $mime_type,
			'post_title'     => sanitize_text_field( $title ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attachment_id = wp_insert_attachment( $attachment, $upload['file'] );
		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( $attachment_id, $attachment_data );

		return $attachment_id;
	}

	private function split_keywords( $keywords ) {
		if ( empty( $keywords ) ) {
			return array();
		}
		$parts = preg_split( '/[,;]+/', $keywords );
		$parts = array_map( 'trim', $parts );
		return array_values( array_filter( $parts ) );
	}
}
