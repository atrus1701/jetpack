<?php

/*
 * Currently, this widget depends on the Stats Module. To not load this file
 * when the Stats Module is not active would potentially bypass Jetpack's
 * fatal error detection on module activation, so we always load this file.
 * Instead, we don't register the widget if the Stats Module isn't active.
 */

/**
 * Register the widget for use in Appearance -> Widgets
 */
add_action( 'widgets_init', array( 'Jetpack_Top_Posts_Widget', 'register_widget' ) );


/**
 * The Top Posts widget contacts the WP.com REST API, then displays the list
 * of the most viewed posts or pages on the site.
 * 
 * @package    Jetpack
 * @subpackage modules/widgets
 */
class Jetpack_Top_Posts_Widget extends WP_Widget {
	public $alt_option_name = 'widget_stats_topposts';

	/**
	 * The default title of the widget if one is not provided.
	 * @var  String
	 */
	public $default_title = '';

	
	/**
	 * Register the Top Posts widget.
	 */
	public static function register_widget() {
		/**
		 * Determine if widget should be registered.
		 *
		 * @module widgets
		 *
		 * @since 
		 *
		 * @param bool true True if widget should be registered.
		 * @param string $widget_name The widget name.
		 */
		$register_widget = apply_filters( 'jetpack_register_widget', true, 'top-posts' );

		if( $register_widget ) {
			register_widget( 'Jetpack_Top_Posts_Widget' );
		}
	}


	/**
	 * Initialize the widget.
	 */
	function __construct() {
		
		parent::__construct(
			'top-posts',
			/** This filter is documented in modules/widgets/facebook-likebox.php */
			apply_filters( 'jetpack_widget_name', __( 'Top Posts &amp; Pages', 'jetpack' ) ),
			array(
				'description' => __( 'Shows your most viewed posts and pages.', 'jetpack' ),
			)
		);

		$this->default_title =  __( 'Top Posts &amp; Pages', 'jetpack' );

		if ( is_active_widget( false, false, $this->id_base ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_style' ) );
		}
	}


	/**
	 * Enqueue the styles used for the Top Posts widget.
	 */
	function enqueue_style() {
		wp_register_style( 'jetpack-top-posts-widget', plugins_url( 'top-posts/style.css', __FILE__ ), array(), '20141013' );
		wp_enqueue_style( 'jetpack-top-posts-widget' );
	}


	/**
	 * Display the widget entry form.
	 * 
	 * @param  Array  $instance  The current widget instance's options.
	 */
	function form( $instance ) {

		$title = isset( $instance['title' ] ) ? $instance['title'] : false;
		if ( false === $title ) {
			$title = $this->default_title;
		}

		$count = isset( $instance['count'] ) ? (int) $instance['count'] : 10;
		if ( $count < 1 || 10 < $count ) {
			$count = 10;
		}

		$allowed_post_types = array_values( get_post_types( array( 'public' => true ) ) );
		$types = isset( $instance['types'] ) ? (array) $instance['types'] : array( 'post', 'page' );

		if ( isset( $instance['display'] ) && in_array( $instance['display'], array( 'grid', 'list', 'text'  ) ) ) {
			$display = $instance['display'];
		} else {
			$display = 'text';
		}

		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'jetpack' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php esc_html_e( 'Maximum number of posts to show (no more than 10):', 'jetpack' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="number" value="<?php echo (int) $count; ?>" min="1" max="10" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'types' ); ?>"><?php esc_html_e( 'Types of pages to display:', 'jetpack' ); ?></label>
			<ul>
				<?php foreach( $allowed_post_types as $type ) {
					// Get the Post Type name to display next to the checkbox
					$post_type_object = get_post_type_object( $type );
					$label = $post_type_object->labels->name;

					$checked = '';
					if ( in_array( $type, $types ) ) {
						$checked = 'checked="checked" ';
					} ?>

					<li><label>
						<input value="<?php echo esc_attr( $type ); ?>" name="<?php echo $this->get_field_name( 'types' ); ?>[]" id="<?php echo $this->get_field_id( 'types' ); ?>-<?php echo $type; ?>" type="checkbox" <?php echo $checked; ?>>
						<?php echo esc_html( $label ); ?>
					</label></li>

				<?php } // End foreach ?>
			</ul>
		</p>

		<p>
			<label><?php esc_html_e( 'Display as:', 'jetpack' ); ?></label>
			<ul>
				<li><label><input id="<?php echo $this->get_field_id( 'display' ); ?>-text" name="<?php echo $this->get_field_name( 'display' ); ?>" type="radio" value="text" <?php checked( 'text', $display ); ?> /> <?php esc_html_e( 'Text List', 'jetpack' ); ?></label></li>
				<li><label><input id="<?php echo $this->get_field_id( 'display' ); ?>-list" name="<?php echo $this->get_field_name( 'display' ); ?>" type="radio" value="list" <?php checked( 'list', $display ); ?> /> <?php esc_html_e( 'Image List', 'jetpack' ); ?></label></li>
				<li><label><input id="<?php echo $this->get_field_id( 'display' ); ?>-grid" name="<?php echo $this->get_field_name( 'display' ); ?>" type="radio" value="grid" <?php checked( 'grid', $display ); ?> /> <?php esc_html_e( 'Image Grid', 'jetpack' ); ?></label></li>
			</ul>
		</p>

		<p><?php esc_html_e( 'Top Posts &amp; Pages by views are calculated from 24-48 hours of stats. They take a while to change.', 'jetpack' ); ?></p>

		<?php
	}


	/**
	 * Process the current widget instance's options on save.
	 *
	 * @param  Array  $new_instance  The new widget instance's options.
	 * @param  Array  $old_instance  The previous widget instance's options.
	 * @return  Array  The processed options.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = wp_kses( $new_instance['title'], array() );
		if ( $instance['title'] === $this->default_title ) {
			$instance['title'] = false; // Store as false in case of language change
		}

		$instance['count'] = (int) $new_instance['count'];
		if ( $instance['count'] < 1 || 10 < $instance['count'] ) {
			$instance['count'] = 10;
		}

		$allowed_post_types = array_values( get_post_types( array( 'public' => true ) ) );
		$instance['types'] = $new_instance['types'];
		foreach( $new_instance['types'] as $key => $type ) {
			if ( ! in_array( $type, $allowed_post_types ) ) {
				unset( $new_instance['types'][ $key ] );
			}
		}

		if ( isset( $new_instance['display'] ) && in_array( $new_instance['display'], array( 'grid', 'list', 'text'  ) ) ) {
			$instance['display'] = $new_instance['display'];
		} else {
			$instance['display'] = 'text';
		}

		return $instance;
	}


	/**
	 * Display the widget.
	 * 
	 * @param  Array  $args  The widget arguments.
	 * @param  Array  $instance  The current widget instance's options.
	 */
	function widget( $args, $instance ) {

		$title = isset( $instance['title' ] ) ? $instance['title'] : false;
		if ( false === $title ) {
			$title = $this->default_title;
		}
		/** This filter is documented in core/src/wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', $title );

		$count = isset( $instance['count'] ) ? (int) $instance['count'] : false;

		/**
		 * Control the number of displayed posts.
		 *
		 * @module widgets
		 *
		 * @since 3.3.0
		 *
		 * @param string $count Number of Posts displayed in the Top Posts widget. Default is 10.
		 */
		$count = apply_filters( 'jetpack_top_posts_widget_count', $count );

		if ( $count < 1 || 10 < $count ) {
			$count = 10;
		}

		$types = isset( $instance['types'] ) ? (array) $instance['types'] : array( 'post', 'page' );

		if ( isset( $instance['display'] ) && in_array( $instance['display'], array( 'grid', 'list', 'text'  ) ) ) {
			$display = $instance['display'];
		} else {
			$display = 'text';
		}

		if ( 'text' != $display ) {
			$get_image_options = array(
				'fallback_to_avatars' => true,
				/** This filter is documented in modules/shortcodes/audio.php */
				'gravatar_default' => apply_filters( 'jetpack_static_url', set_url_scheme( 'http://en.wordpress.com/i/logo/white-gray-80.png' ) ),
			);
			if ( 'grid' == $display ) {
				$get_image_options['avatar_size'] = 200;
			} else {
				$get_image_options['avatar_size'] = 40;
			}

			/**
			 * Top Posts Widget Image options.
			 *
			 * @module widgets
			 *
			 * @since 1.8.0
			 *
			 * @param array $get_image_options {
			 * Array of Image options.
			 * @type bool true Should we default to Gravatars when no image is found? Default is true.
			 * @type string $gravatar_default Default Image URL if no Gravatar is found.
			 * @type int $avatar_size Default Image size.
			 * }
			 */
			$get_image_options = apply_filters( 'jetpack_top_posts_widget_image_options', $get_image_options );
		}


		$posts = $this->get_by_views( $types, $count );


		echo $args['before_widget'];
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];
		
		if ( ! $posts ) {
			if ( current_user_can( 'edit_theme_options' ) ) {
				echo '<p>' . sprintf(
					__( 'There are no posts to display. <a href="%s">Want more traffic?</a>', 'jetpack' ),
					'http://en.support.wordpress.com/getting-more-site-traffic/'
				) . '</p>';
			}

			echo $args['after_widget'];
			return;
		}

		switch ( $display ) {
		case 'list' :
		case 'grid' :
			wp_enqueue_style( 'widget-grid-and-list' );
			foreach ( $posts as &$post ) {
				$image = Jetpack_PostImages::get_image( $post['post_id'], array( 'fallback_to_avatars' => true ) );
				$post['image'] = $image['src'];
				if ( 'blavatar' != $image['from'] && 'gravatar' != $image['from'] ) {
					$size = (int) $get_image_options['avatar_size'];
					$post['image'] = jetpack_photon_url( $post['image'], array( 'resize' => "$size,$size" ) );
				}
			}

			unset( $post );

			if ( 'grid' == $display ) {
				echo "<div class='widgets-grid-layout no-grav'>\n";
				foreach ( $posts as $post ) :
				?>
					<div class="widget-grid-view-image">
						<?php
						/**
						 * Fires before each Top Post result, inside <li>.
						 *
						 * @module widgets
						 *
						 * @since 3.2.0
						 *
						 * @param string $post['post_id'] Post ID.
						 */
						do_action( 'jetpack_widget_top_posts_before_post', $post['post_id'] );
						?>
						<a href="<?php echo esc_url( $post['permalink'] ); ?>" title="<?php echo esc_attr( wp_kses( $post['title'], array() ) ); ?>" class="bump-view" data-bump-view="tp">
							<img src="<?php echo esc_url( $post['image'] ); ?>" alt="<?php echo esc_attr( wp_kses( $post['title'], array() ) ); ?>" data-pin-nopin="true" />
						</a>
						<?php
						/**
						 * Fires after each Top Post result, inside <li>.
						 *
						 * @module widgets
						 *
						 * @since 3.2.0
						 *
						 * @param string $post['post_id'] Post ID.
						 */
						do_action( 'jetpack_widget_top_posts_after_post', $post['post_id'] );
						?>
					</div>
				<?php
				endforeach;
				echo "</div>\n";
			} else {
				echo "<ul class='widgets-list-layout no-grav'>\n";
				foreach ( $posts as $post ) :
				?>
					<li>
						<?php
						/** This action is documented in modules/widgets/top-posts.php */
						do_action( 'jetpack_widget_top_posts_before_post', $post['post_id'] );
						?>
						<a href="<?php echo esc_url( $post['permalink'] ); ?>" title="<?php echo esc_attr( wp_kses( $post['title'], array() ) ); ?>" class="bump-view" data-bump-view="tp">
							<img src="<?php echo esc_url( $post['image'] ); ?>" class='widgets-list-layout-blavatar' alt="<?php echo esc_attr( wp_kses( $post['title'], array() ) ); ?>" data-pin-nopin="true" />
						</a>
						<div class="widgets-list-layout-links">
							<a href="<?php echo esc_url( $post['permalink'] ); ?>" class="bump-view" data-bump-view="tp">
								<?php echo esc_html( wp_kses( $post['title'], array() ) ); ?>
							</a>
						</div>
						<?php
						/** This action is documented in modules/widgets/top-posts.php */
						do_action( 'jetpack_widget_top_posts_after_post', $post['post_id'] );
						?>
					</li>
				<?php
				endforeach;
				echo "</ul>\n";
			}
			break;
		default :
			echo '<ul>';
			foreach ( $posts as $post ) :
			?>
				<li>
					<?php
					/** This action is documented in modules/widgets/top-posts.php */
					do_action( 'jetpack_widget_top_posts_before_post', $post['post_id'] );
					?>
					<a href="<?php echo esc_url( $post['permalink'] ); ?>" class="bump-view" data-bump-view="tp">
						<?php echo esc_html( wp_kses( $post['title'], array() ) ); ?>
					</a>
					<?php
					/** This action is documented in modules/widgets/top-posts.php */
					do_action( 'jetpack_widget_top_posts_after_post', $post['post_id'] );
					?>
				</li>
			<?php
			endforeach;
			echo '</ul>';
		}

		echo $args['after_widget'];
	}


	/**
	 * Get the list of Top Posts organized by the number of views.
	 * 
	 * @param  array  $types  The post types to include in the list.
	 * @param  int  The max number of posts to include in the list.
	 * @return  array|bool  The array of posts or False if an error occurs.
	 */
	function get_by_views( $types, $count ) {
		
		if ( Jetpack::is_development_mode() ) {
			return FALSE;
		}

		/**
		 * Filter the number of days used to calculate Top Posts for the Top Posts widget.
		 *
		 * @module widgets
		 *
		 * @since 2.8.0
		 *
		 * @param int 2 Number of days. Default is 2.
		 */
		$days = (int) apply_filters( 'jetpack_top_posts_days', 2 );

		if ( $days < 1 ) {
			$days = 2;
		}

		if ( $days > 10 ) {
			$days = 10;
		}

		$days = absint( $days );


		// Contact the WP.com REST API for Top Posts list.
		$args = array(
			'num'    => $days,
			'period' => 'day',
		);

		$results = stats_get_from_restapi( array( 'method' => 'GET' ), add_query_arg( $args, 'top-posts' ) );
		
		
		if ( ( ! ( $results ) ) || ( ! isset( $results->days ) ) ) {
			return FALSE;
		}


		// Organize the results into a list with the post id as the key.
		$posts = array();
		$i = 0;
		foreach ( get_object_vars( $results->days ) as $day ) {
			
			if ( $day->total_views == 0 ) {
				continue;
			}

			foreach ( $day->postviews as $postview ) {
				
				if ( ! in_array( $postview->type, $types ) ) {
					continue;
				}

				if ( ! array_key_exists( $postview->id, $posts ) ) {
					$posts[ $postview->id ] = $postview->views;
					continue;
				}

				$posts[ $postview->id ] += $postview->views;
			}
		}


		// Sort list by views.
		arsort( $posts );


		// Get the complete post list with post data.
		return $this->get_posts( array_keys( $posts ), $count );
	}


	/**
	 * Get the first post as a fallback.
	 * 
	 * @return  array  The list of the one fallback post.
	 */
	function get_fallback_posts() {
		if ( current_user_can( 'edit_theme_options' ) ) {
			return array();
		}

		$post_query = new WP_Query;

		$posts = $post_query->query( array(
			'posts_per_page' => 1,
			'post_status' => 'publish',
			'post_type' => array( 'post', 'page' ),
			'no_found_rows' => true,
		) );

		if ( ! $posts ) {
			return array();
		}

		$post = array_pop( $posts );

		return $this->get_posts( $post->ID, 1 );
	}


	/**
	 * Get a list of posts based on a list of post ids.
	 * 
	 * @param  array  $post_ids  The list of post ids.
	 * @param  int  $count  The max number of posts that should be in the list.
	 * @return  array  The list of post with display data.
	 */
	function get_posts( $post_ids, $count ) {
		$counter = 0;

		$posts = array();
		foreach ( (array) $post_ids as $post_id ) {
			$post = get_post( $post_id );

			if ( ! $post )
				continue;

			// hide private and password protected posts
			if ( 'publish' != $post->post_status || ! empty( $post->post_password ) || empty( $post->ID ) )
				continue;

			// Both get HTML stripped etc on display
			if ( empty( $post->post_title ) ) {
				$title_source = $post->post_content;
				$title = wp_html_excerpt( $title_source, 50 );
				$title .= '&hellip;';
			} else {
				$title = $post->post_title;
			}

			$permalink = get_permalink( $post->ID );

			$post_type = $post->post_type;

			$posts[] = compact( 'title', 'permalink', 'post_id', 'post_type' );
			$counter++;

			if ( $counter == $count ) {
				break; // only need to load and show x number of likes
			}
		}

		/**
		 * Filter the Top Posts and Pages.
		 *
		 * @module widgets
		 *
		 * @since 3.0.0
		 *
		 * @param array $posts Array of the most popular posts.
		 * @param array $post_ids Array of Post IDs.
		 * @param string $count Number of Top Posts we want to display.
		 */
		return apply_filters( 'jetpack_widget_get_top_posts', $posts, $post_ids, $count );
	}
}
