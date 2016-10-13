<?php
class WP_Advent_Plugin_Shortcode {

	public function __construct( $plugin_name, $version, $plugin ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->plugin = $plugin;
	}

	public function init() {
		add_shortcode( 'adventcalendar', array($this, 'parseShortcode') );
	}

	public function enqueue_scripts() {
		wp_enqueue_style($this->plugin_name, plugin_dir_url( dirname( __FILE__ )) . 'css/WP_Advent_Plugin.css', false, $this->version, false);
		wp_enqueue_style('colorbox', plugin_dir_url( dirname( __FILE__ )) . 'css/colorbox.css', false, $this->version, false);
		wp_enqueue_script($this->plugin_name, plugin_dir_url( dirname( __FILE__ )) . 'js/WP_Advent_Plugin.js', array( 'jquery' ), null, true);
		wp_enqueue_script('colorbox', plugin_dir_url( dirname( __FILE__ )) . 'js/jquery.colorbox-min.js', array( 'jquery' ), null, true);
	}

	public function wp_advent_custom_template($single_template) {
		global $post;

		if ($post->post_type == 'wp_advent_sheet') {
			$single_template = plugin_dir_path( dirname( __FILE__ ) ) . '/tpl/post.php';
		}
		return $single_template;
	}

	public function parseShortcode ($atts, $content = null) {
		$this->enqueue_scripts();
		$attributes = shortcode_atts( array(
			'calendar' => false,
		)	, $atts );
		if($attributes['calendar'] == false){
			return;
		}
		$calendar_metadata = get_term_by('slug',sanitize_title($attributes['calendar']),'wp_advent_plugin_calendar');
		if($calendar_metadata == false){
			return;
		}

		$calendar_images = $this->plugin->getOption('calendar_images');
		if(!$calendar_images){
			$calendar_images = array();
		}

		$calendar = new WP_Advent_Plugin_Calendar();
		$calendar->setId($calendar_metadata->term_id);
		$calendar->setYear($calendar_metadata->description);
		$calendar->setName($calendar_metadata->name);
		$calendar->setSlug($calendar_metadata->slug);
		if(isset($calendar_images[$calendar->getId()])){
			$image = $calendar_images[$calendar->getId()];
			$calendar->setImage($image);
		}
		$args = array(
			'post_type'	=>	'wp_advent_sheet',
			'post_status'	=>	array(
				'publish'
			),
			'tax_query' => array(
				array(
					'taxonomy' => 'wp_advent_plugin_calendar',
					'field'    => 'term_id',
					'terms'    => $calendar_metadata->term_id,
				),
			),
		);
		$calQuery = new WP_Query( $args );
		$calQuery->get_posts();
		if($calQuery->post_count > 0){
			foreach($calQuery->posts as $post){
				$calendar->addPost($post);
			}
		}
		require plugin_dir_path( dirname( __FILE__ ) ).'tpl/shortcode.php';
	}
}
