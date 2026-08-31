<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bulk Post Generator.
 *
 * Builds blog post titles/content locally and optionally generates
 * local JPG featured images using PHP GD.
 */
class BPG_Generator {

	private $settings;

	public function __construct() {
		$this->settings = wp_parse_args(
			get_option( 'bpg_settings', array() ),
			array(
				'generate_images'  => false,
				'default_category' => 1,
				'post_status'      => 'draft',
				'word_count'       => 'medium',
				'batch_size'       => BPG_DEFAULT_BATCH,
			)
		);
	}

	/**
	 * Build blog post titles.
	 *
	 * @return array|WP_Error
	 */
	public function get_topics( $niche, $keywords, $count, $business_name = '', $business_type = '' ) {
		$count       = max( 1, min( 10, intval( $count ) ) );
		$niche       = sanitize_text_field( $niche );
		$business_name = sanitize_text_field( $business_name );
		$business_type = sanitize_text_field( $business_type );

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
			return new WP_Error(
				'bpg_no_titles',
				__( 'Could not build titles. Try a different topic.', 'bulk-post-generator' )
			);
		}

		return array_slice( $titles, 0, $count );
	}

	/**
	 * Generate and insert one post.
	 *
	 * @return array|WP_Error
	 */
	public function generate_post( $title, $niche, $category_id, $status, $business_name = '', $business_type = '' ) {

		$title         = sanitize_text_field( $title );
		$niche         = sanitize_text_field( $niche );
		$business_name = sanitize_text_field( $business_name );
		$business_type = sanitize_text_field( $business_type );

		$content = $this->generate_content(
			$title,
			$niche,
			$business_name,
			$business_type
		);

		$excerpt = $this->build_excerpt( $content );

		$allowed_statuses = array(
			'draft',
			'pending',
			'publish',
		);

		$postarr = array(
			'post_title'   => wp_strip_all_tags( $title ),
			'post_content' => wp_kses_post( $content ),
			'post_excerpt' => $excerpt,
			'post_status'  => in_array( $status, $allowed_statuses, true ) ? $status : 'draft',
			'post_type'    => 'post',
		);

		if ( ! empty( $category_id ) ) {
			$postarr['post_category'] = array( absint( $category_id ) );
		}

		$post_id = wp_insert_post( $postarr, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_bpg_generated', 1 );
		update_post_meta( $post_id, '_bpg_niche', $niche );

		if ( ! empty( $business_name ) ) {
			update_post_meta(
				$post_id,
				'_bpg_business_name',
				$business_name
			);
		}

		if ( ! empty( $business_type ) ) {
			update_post_meta(
				$post_id,
				'_bpg_business_type',
				$business_type
			);
		}

		$has_image = false;

		if ( ! empty( $this->settings['generate_images'] ) ) {

			$attachment_id = $this->generate_local_featured_image(
				$post_id,
				$title,
				$niche
			);

			if ( ! is_wp_error( $attachment_id ) && $attachment_id ) {
				$has_image = set_post_thumbnail(
					$post_id,
					$attachment_id
				);
			}
		}

		return array(
			'post_id'   => $post_id,
			'edit_link' => get_edit_post_link( $post_id, 'raw' ),
			'title'     => get_the_title( $post_id ),
			'has_image' => (bool) $has_image,
		);
	}

	/**
	 * Build excerpt.
	 *
	 * @param string $html Content.
	 * @param int    $max_len Maximum length.
	 * @return string
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

	/**
	 * Generate local post content.
	 *
	 * @return string
	 */
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

		$html .= strtr(
			$intro_variants[ array_rand( $intro_variants ) ],
			array(
				'{niche}' => esc_html( $niche ),
			)
		);

		for ( $i = 0; $i < $num_sections; $i++ ) {

			$html .= '<h2>' . esc_html( $section_titles[ $i % count( $section_titles ) ] ) . '</h2>';

			$html .= strtr(
				$section_body_variants[ $i % count( $section_body_variants ) ],
				array(
					'{niche}' => esc_html( $niche ),
				)
			);
		}

		$html .= strtr(
			$conclusion_variants[ array_rand( $conclusion_variants ) ],
			array(
				'{niche}' => esc_html( $niche ),
			)
		);

		if ( ! empty( $business_name ) ) {

			$safe_business_name = esc_html( $business_name );
			$safe_business_type = esc_html( $business_type );

			$closing = '<p>At <strong>' . $safe_business_name . '</strong>, this is exactly the kind of thing we help people with every day';

			if ( ! empty( $safe_business_type ) ) {
				$closing .= ' as a ' . $safe_business_type . ' business';
			}

			$closing .= '. If you\'d like a hand applying any of this, feel free to reach out.</p>';

			$html .= $closing;
		}

		$html .= "\n<!-- Generated with Bulk Post Generator. Please review and personalize before publishing. -->";

		return $html;
	}

	/**
	 * Generate local JPG featured image.
	 *
	 * @return int|WP_Error
	 */
	private function generate_local_featured_image( $post_id, $title, $niche ) {

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return new WP_Error(
				'bpg_invalid_post',
				__( 'Invalid post ID for featured image.', 'bulk-post-generator' )
			);
		}

		$title = sanitize_text_field( $title );
		$niche = sanitize_text_field( $niche );

		if (
			! function_exists( 'imagecreatetruecolor' ) ||
			! function_exists( 'imagejpeg' )
		) {
			return new WP_Error(
				'bpg_gd_missing',
				__( 'PHP GD extension is required to generate JPG images.', 'bulk-post-generator' )
			);
		}

		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			return new WP_Error(
				'bpg_upload_dir_error',
				$upload_dir['error']
			);
		}

		$width  = 1200;
		$height = 675;

		$image = imagecreatetruecolor(
			$width,
			$height
		);

		if ( ! $image ) {
			return new WP_Error(
				'bpg_image_create_failed',
				__( 'Could not create the JPG image canvas.', 'bulk-post-generator' )
			);
		}

		/*
		 * Create deterministic colors from post data.
		 */
		$hash = md5(
			$post_id . '|' . $title . '|' . $niche
		);

		$color_1 = array(
			hexdec( substr( $hash, 0, 2 ) ),
			hexdec( substr( $hash, 2, 2 ) ),
			hexdec( substr( $hash, 4, 2 ) ),
		);

		$color_2 = array(
			hexdec( substr( $hash, 6, 2 ) ),
			hexdec( substr( $hash, 8, 2 ) ),
			hexdec( substr( $hash, 10, 2 ) ),
		);

		$background = imagecolorallocate(
			$image,
			$color_1[0],
			$color_1[1],
			$color_1[2]
		);

		imagefill(
			$image,
			0,
			0,
			$background
		);

		$overlay = imagecolorallocatealpha(
			$image,
			$color_2[0],
			$color_2[1],
			$color_2[2],
			70
		);

		imagefilledellipse(
			$image,
			1040,
			100,
			420,
			420,
			$overlay
		);

		imagefilledellipse(
			$image,
			1100,
			650,
			500,
			350,
			$overlay
		);

		$panel = imagecolorallocatealpha(
			$image,
			255,
			255,
			255,
			35
		);

		imagefilledrectangle(
			$image,
			60,
			55,
			1140,
			620,
			$panel
		);

		$font_candidates = array(
			'C:/Windows/Fonts/arialbd.ttf',
			'C:/Windows/Fonts/Arial Bold.ttf',
			'C:/Windows/Fonts/segoeuib.ttf',
			'C:/Windows/Fonts/calibrib.ttf',
		);

		$font = '';

		foreach ( $font_candidates as $candidate ) {

			if ( file_exists( $candidate ) ) {
				$font = $candidate;
				break;
			}
		}

		$white = imagecolorallocate(
			$image,
			255,
			255,
			255
		);

		$soft_white = imagecolorallocatealpha(
			$image,
			255,
			255,
			255,
			35
		);

		$niche_display = strtoupper(
			wp_strip_all_tags( $niche )
		);

		$niche_display = $this->truncate_image_text(
			$niche_display,
			28
		);

		if ( ! empty( $font ) ) {

			imagettftext(
				$image,
				20,
				0,
				90,
				125,
				$white,
				$font,
				$niche_display
			);

		} else {

			imagestring(
				$image,
				5,
				90,
				90,
				$niche_display,
				$white
			);
		}

		$title_lines = $this->wrap_image_title(
			wp_strip_all_tags( $title ),
			34,
			3
		);

		$y = 245;

		foreach ( $title_lines as $line ) {

			if ( ! empty( $font ) ) {

				imagettftext(
					$image,
					46,
					0,
					90,
					$y,
					$white,
					$font,
					$line
				);

			} else {

				imagestring(
					$image,
					5,
					90,
					$y - 25,
					$line,
					$white
				);
			}

			$y += 65;
		}

		imageline(
			$image,
			90,
			555,
			1110,
			555,
			$soft_white
		);

		$branding = 'Bulk Post Generator';

		if ( ! empty( $font ) ) {

			imagettftext(
				$image,
				18,
				0,
				90,
				600,
				$soft_white,
				$font,
				$branding
			);

		} else {

			imagestring(
				$image,
				4,
				90,
				580,
				$branding,
				$soft_white
			);
		}

		$base_name = sanitize_title( $title );

		if ( empty( $base_name ) ) {
			$base_name = 'featured-image';
		}

		$filename = 'bpg-' . $post_id . '-' . $base_name . '.jpg';

		$filename = sanitize_file_name( $filename );

		$filename = wp_unique_filename(
			$upload_dir['path'],
			$filename
		);

		$file_path = trailingslashit(
			$upload_dir['path']
		) . $filename;

		$saved = imagejpeg(
			$image,
			$file_path,
			88
		);

		imagedestroy( $image );

		if ( ! $saved || ! file_exists( $file_path ) ) {
			return new WP_Error(
				'bpg_jpg_save_failed',
				__( 'Could not save the generated JPG image.', 'bulk-post-generator' )
			);
		}

		$attachment = array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => $title,
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attachment_id = wp_insert_attachment(
			$attachment,
			$file_path,
			$post_id,
			true
		);

		if ( is_wp_error( $attachment_id ) ) {

			wp_delete_file( $file_path );

			return $attachment_id;
		}

		$metadata = wp_generate_attachment_metadata(
			$attachment_id,
			$file_path
		);

		if (
			! empty( $metadata ) &&
			! is_wp_error( $metadata )
		) {
			wp_update_attachment_metadata(
				$attachment_id,
				$metadata
			);
		}

		update_post_meta(
			$attachment_id,
			'_bpg_generated_image',
			1
		);

		update_post_meta(
			$attachment_id,
			'_bpg_image_source',
			'local-gd'
		);

		update_post_meta(
			$attachment_id,
			'_bpg_image_niche',
			$niche
		);

		update_post_meta(
			$attachment_id,
			'_bpg_image_dimensions',
			'1200x675'
		);

		return absint( $attachment_id );
	}

	/**
	 * Wrap title for image.
	 *
	 * @return array
	 */
	private function wrap_image_title( $text, $max_chars = 34, $max_lines = 3 ) {

		$text = trim(
			preg_replace(
				'/\s+/',
				' ',
				$text
			)
		);

		if ( empty( $text ) ) {
			return array( 'Blog Post' );
		}

		$words = preg_split(
			'/\s+/',
			$text
		);

		$lines = array();
		$line  = '';

		foreach ( $words as $word ) {

			$test = '' === $line
				? $word
				: $line . ' ' . $word;

			if ( strlen( $test ) <= $max_chars ) {

				$line = $test;

			} else {

				if ( '' !== $line ) {
					$lines[] = $line;
				}

				$line = $word;

				if ( count( $lines ) >= $max_lines ) {
					break;
				}
			}
		}

		if (
			count( $lines ) < $max_lines &&
			'' !== $line
		) {
			$lines[] = $line;
		}

		$joined = implode(
			' ',
			$lines
		);

		if ( strlen( $joined ) < strlen( $text ) ) {

			$last_index = count( $lines ) - 1;

			if ( $last_index >= 0 ) {

				$lines[ $last_index ] = rtrim(
					substr(
						$lines[ $last_index ],
						0,
						max( 1, $max_chars - 3 )
					)
				) . '...';
			}
		}

		return $lines;
	}

	/**
	 * Truncate text used on image.
	 *
	 * @return string
	 */
	private function truncate_image_text( $text, $max = 28 ) {

		$text = trim(
			preg_replace(
				'/\s+/',
				' ',
				$text
			)
		);

		if ( strlen( $text ) <= $max ) {
			return $text;
		}

		return substr(
			$text,
			0,
			max( 1, $max - 3 )
		) . '...';
	}

	/**
	 * Split comma/semicolon separated keywords.
	 *
	 * @return array
	 */
	private function split_keywords( $keywords ) {

		if ( empty( $keywords ) ) {
			return array();
		}

		$parts = preg_split(
			'/[,;]+/',
			$keywords
		);

		$parts = array_map(
			'sanitize_text_field',
			$parts
		);

		return array_values(
			array_filter( $parts )
		);
	}
}