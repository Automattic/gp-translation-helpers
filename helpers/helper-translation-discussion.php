<?php

class Helper_Translation_Discussion extends GP_Translation_Helper {

	public $priority = 2;

	const POST_TYPE = 'gmd_original';
	const POST_STATUS = 'publish';

	function __construct() {

		$post_type_args = array(
			'show_ui'               => false,
			'show_in_menu'          => false,
			'show_in_admin_bar'     => false,
			'show_in_nav_menus'     => false,
			'can_export'            => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'rewrite'               => false,
			'show_in_rest'          => true,
			'supports'              => array( 'custom-fields' ),
		);
		register_post_type( SELF::POST_TYPE, $post_type_args );

		add_filter( 'disable_highlander_comments', '__return_true' );
	}

	function get_output() {
		$gmd_post_id = $this->get_gmd_post( $this->data['original_id'] );

		$output = '<ul>';
		$output .= wp_list_comments(array(
			'reverse_top_level' => false, //Show the latest comments at the top of the list
			'echo' => false,
		), $this->get_comments( $gmd_post_id ) );
		$output .= '</ul>';

		// TODO: output buffering? we should find something better.
		ob_start();
		comment_form(
			array(
				'title_reply_before' => '<h6 class="original-comment-reply-title">',
				'title_reply_after' => '</h6>',
		), $gmd_post_id );
		$output .= ob_get_contents();
		ob_end_clean();

		return $output;
	}


	private function get_comments( $gmd_post_id ) {
		$comments_query = new WP_Comment_Query();
		return $comments_query->query(
			array( 'post_id' => $gmd_post_id )
		);
	}

	public function get_gmd_post( $original_id ) {
		$gp_posts = get_posts(
			array(
				'meta_key' => 'gp_original_id',
				'meta_value' => $original_id,
				'post_type' => self::POST_TYPE,
				'posts_per_page' => 1,
				'post_status' => self::POST_STATUS,
				'suppress_filters' => false,
			)
		);

		if ( empty( $gp_posts ) ) {
			$post_id = wp_insert_post(
				array(
					'post_type' => SELF::POST_TYPE,
					'meta_input' => array(
						'gp_original_id' => $original_id,
					),
					'post_status' => self::POST_STATUS,
					'comment_status' => 'open',
				)
			);
		} else {
			$post_id = $gp_posts[0]->ID;
		}

		return $post_id;
	}

}
