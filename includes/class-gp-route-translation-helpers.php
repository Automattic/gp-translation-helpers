<?php

class GP_Route_Translation_Helpers extends GP_Route {

	private $helpers = array();

	public function __construct() {
		$this->helpers       = GP_Translation_Helpers::load_helpers();
		$this->template_path = dirname( __FILE__ ) . '/../templates/';
	}

	public function original_permalink( $project_path, $original_id, $locale_slug = null, $set_slug = null, $translation_id = null ) {
		$project = GP::$project->by_path( $project_path );
		if ( ! $project ) {
			$this->die_with_404();
		}

		$args            = array(
			'project_id'     => $project->id,
			'locale_slug'    => $locale_slug,
			'set_slug'       => $set_slug,
			'original_id'    => $original_id,
			'translation_id' => $translation_id,
		);
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $set_slug, $locale_slug );
		$original        = GP::$original->get( $original_id );

		$translation_helper = $this->helpers['comments'];
		$translation_helper->set_data( $args );

		$post_id  = $translation_helper::get_shadow_post( $original_id );
		$comments = get_comments(
			array(
				'post_id'            => $post_id,
				'status'             => 'approve',
				'type'               => 'comment',
				'include_unapproved' => array( get_current_user_id() ),
			)
		);

		$locales_with_comments = array();
		if ( $comments ) {
			foreach ( $comments as $comment ) {
				$comment_meta          = get_comment_meta( $comment->comment_ID, 'locale' );
				$single_comment_locale = is_array( $comment_meta ) && ! empty( $comment_meta ) ? $comment_meta[0] : '';
				if ( $single_comment_locale && ! in_array( $single_comment_locale, $locales_with_comments ) ) {
					$locales_with_comments[] = $single_comment_locale;
				}
			}
		}

		$translation                    = GP::$translation->get( $translation_id );
		$original_permalink             = gp_url_project( $project, array( 'filters[original_id]' => $original_id ) );
		$original_translation_permalink = false;
		if ( $translation_set ) {
			$original_translation_permalink = gp_url_project_locale( $project, $locale_slug, $translation_set->slug, array( 'filters[original_id]' => $original_id ) );
		}

		wp_register_style( 'gp-discussion-css', plugins_url( './css/discussion.css', __FILE__ ) );

		wp_register_script( 'gp-translation-discussion-js', plugins_url( './js/discussion.js', __FILE__ ) );

		add_filter(
			'comment_form_logged_in',
			function( $logged_in_as, $commenter, $user_identity ) {
				return sprintf( '<p class="logged-in-as">%s</p>', sprintf( __( 'Logged in as %s.' ), $user_identity ) );
			},
			10,
			3
		);

		add_filter(
			'comment_form_fields',
			function( $comment_fields ) {
				$comment_fields['comment'] = str_replace( '>Comment<', '>Please leave your comment about this string here:<', $comment_fields['comment'] );
				return $comment_fields;
			}
		);

		remove_action( 'comment_form_top', 'rosetta_comment_form_support_hint' );

		add_filter(
			'comment_reply_link',
			function( $link, $args, $comment, $post ) use ( $project, $translation_set, $original ) {
				$permalink = '/projects/' . $project->path . '/' . $original->id;
				if ( $translation_set ) {
					$permalink .= '/' . $translation_set->locale . '/' . $translation_set->slug;
				}
				$permalink = home_url( $permalink );

				$data_attributes = array(
					'commentid'      => $comment->comment_ID,
					'postid'         => $post->ID,
					'belowelement'   => $args['add_below'] . '-' . $comment->comment_ID,
					'respondelement' => $args['respond_id'],
					'replyto'        => sprintf( $args['reply_to_text'], $comment->comment_author ),
				);

				$data_attribute_string = '';

				foreach ( $data_attributes as $name => $value ) {
					$data_attribute_string .= " data-${name}=\"" . esc_attr( $value ) . '"';
				}

				$data_attribute_string = trim( $data_attribute_string );

				$link = sprintf(
					"<a rel='nofollow' class='comment-reply-link' href='%s' %s aria-label='%s'>%s</a>",
					esc_url(
						add_query_arg(
							array(
								'replytocom'      => $comment->comment_ID,
								'unapproved'      => false,
								'moderation-hash' => false,
							),
							$permalink
						)
					) . '#' . $args['respond_id'],
					$data_attribute_string,
					esc_attr( sprintf( $args['reply_to_text'], $comment->comment_author ) ),
					$args['reply_text']
				);
				return $args['before'] . $link . $args['after'];
			},
			10,
			4
		);

		add_filter(
			'get_comment_author_link',
			function() {
				$comment_author = get_comment_author();
				return '<a href="https://profiles.wordpress.org/' . $comment_author . '">' . $comment_author . '</a>';
			}
		);

		/** Get translation for this original */
		$string_translation  = null;
		$translation_details = array();
		if ( $translation_set && $original_id ) {
			$translation_details = GP::$translation->find_many_no_map(
				array(
					'status'             => 'current',
					'original_id'        => $original_id,
					'translation_set_id' => $translation_set->id,
				)
			);
		}

		if ( is_array( $translation_details ) && ! empty( $translation_details ) ) {
			$string_translation = $translation_details[0]->translation_0;
		}
		$translations       = GP::$translation->find_many_no_map(
			array(
				'status'      => 'current',
				'original_id' => $original_id,
			)
		);
		$no_of_translations = count( $translations );

		$translations_by_locale = array();
		if ( $translations ) {
			foreach ( $translations as $translation ) {
				$_set                                    = GP::$translation_set->get( $translation->translation_set_id );
				$translations_by_locale[ $_set->locale ] = $translation->translation_0;
			}
		}
		$priorities_key_value = $original->get_static( 'priorities' );
		$priority             = $priorities_key_value[ $original->priority ];

		$this->tmpl( 'original-permalink', get_defined_vars() );
	}

	public function translation_helpers( $project_path, $locale_slug, $set_slug, $original_id, $translation_id = null ) {
		$project = GP::$project->by_path( $project_path );
		if ( ! $project ) {
			$this->die_with_404();
		}

		$args = array(
			'project_id'     => $project->id,
			'locale_slug'    => $locale_slug,
			'set_slug'       => $set_slug,
			'original_id'    => $original_id,
			'translation_id' => $translation_id,
		);

		$single_helper = gp_get( 'helper' );
		$helpers       = $this->helpers;
		if ( isset( $this->helpers[ $single_helper ] ) ) {
			$helpers = array( $this->helpers[ $single_helper ] );
		}

		$sections = array();
		foreach ( $helpers as $translation_helper ) {
			$translation_helper->set_data( $args );
			if ( $translation_helper->has_async_content() && $translation_helper->activate() ) {
				$sections[ $translation_helper->get_div_id() ] = array(
					'content' => $translation_helper->get_async_output(),
					'count'   => $translation_helper->get_count(),
				);
			};
		}

		echo wp_json_encode( $sections );
	}
}
