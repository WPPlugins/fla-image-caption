<?php
/****************************************************************************************
 * Plugin Name: fla_Image Caption
 * Plugin URI: http://flavian.imlig.info/?nav=2&show=1
 * Description: Plugin to insert image authors automatically.
 * Version: 1.1
 * Text Domain: fla_ic
 * Domain Path: /languages
 * Author: Flavian Imlig
 * Author URI: http://flavian.imlig.info
 * Author Email: flavian@imlig.info
 * License: GPLv2 or later
 ***************************************************************************************/
defined('ABSPATH') or die("No script kiddies please!");
ini_set('exif.encode_unicode', 'UTF-8');

// define('WP_DEBUG', true);
// define('WP_DEBUG_LOG', true);
// define('WP_DEBUG_DISPLAY', true);
// @ini_set('display_errors', E_ALL);

/****************************************************************************************
 * Hauptklasse: Backend, Frontend-Funktionen
 ***************************************************************************************/
if( !class_exists( 'flaImageCaption_Main' ) ) : 
class flaImageCaption_Main
{
/**
 * Klasse bei Wordpress anmelden, Konstruktor, Dekonstruktor
 */
	// public function __construct() 
	// {
		// register_activation_hook( __FILE__, array( &$this, 'activate' ) );
		// register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );
    // }

	public function __construct() 
	{
		if( is_admin() )
		{
			add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
			add_action( 'admin_init', array( $this, 'flaIC_adminPageInit' ) );
		}
		add_action( 'plugins_loaded', array( 'flaImageCaption_Main', 'flaIC_load_plugin_textdomain' ) ); 
		add_filter( 'img_caption_shortcode', array( 'flaImageCaption_Main', 'flaIC_caption_init' ), 3, 10 ); // Filter in media.php ersetzen!!
	}

	public function __destruct()
	{
		if( is_admin() )
		{
			remove_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
			remove_action( 'admin_init', array( $this, 'flaIC_adminPageInit' ) );
		}
			remove_action( 'plugins_loaded', array( 'flaImageCaption_Main', 'flaIC_load_plugin_textdomain' ) ); 
			remove_filter( 'img_caption_shortcode', array( 'flaImageCaption_Main', 'flaIC_caption_init' ), 10 );
	}
	
/**
 * Konstanten & Variablen
 */
    const pluginName = 'fla_Image Caption';
	const textDomain = 'fla_ic';
	
	protected $wp_options = array(); // befüllt mit gültigen fla_ic-Optionen aus Wordpress database
	public $ic_options = array(); // befüllt gesamter fla_ic-Optionenstruktur innerhalb des Plugins mit flaIC_loadPluginOptions
	protected $wp_post; // befüllt mit get_post in $this->flaIC_caption() = WP_Post object 
					 // URL unter 'guid', Autor-ID unter 'post_author', Beschreibung unter 'post_content', Beschriftung unter 'post_excerpt'
	protected $update_post = array(); // Argumente zur Aktualisierung der Infos (Autor, Beschriftung)

/**
 * Plugin-Optionen
 */
	protected function flaIC_loadPluginOptions()
	{
		if( count($this->ic_options) < 1 ) // nur falls nicht schon geladen!!
		{
			// Optionen definieren
			$a['flaIC_setting_section_1']['cap'] = 'How to deal with missing image authors?';
				$a['flaIC_setting_section_1']['flaIC_op_mode'] = array
						(
							'cap' => 'Mode',
							'mode' => 'radio',
							'types' => array
							(
								1 => 'empty',
								2 => 'Standardautor verwenden'
							),
							'default' => 1
						);
				$a['flaIC_setting_section_1']['flaIC_op_defaut'] = array
						(
							'cap' => 'Default author',
							'classes' => array('regular-text'),
							'default' => get_bloginfo()
						);

			$a['flaIC_setting_section_2']['cap'] = 'Appearance';
			$a['flaIC_setting_section_2']['info'] = array('Wrapping of the image author','HTML-Code allowed');
				$a['flaIC_setting_section_2']['flaIC_op_glue'] = array
						(
							'cap' => 'Glue',
							'classes' => array('regular-text', 'code'),
							'default' => ', '
						);
				$a['flaIC_setting_section_2']['flaIC_op_prefix'] = array
						(
							'cap' => 'Prefix',
							'classes' => array('regular-text', 'code'),
							'default' => '&nbsp;<span class="wp-caption-author">'
						);
				$a['flaIC_setting_section_2']['flaIC_op_suffix'] = array
						(
							'cap' => 'Suffix',
							'classes' => array('regular-text', 'code'),
							'default' => '</span>'
						);

			$a['flaIC_setting_section_3']['cap'] = 'Sync of caption';
			$a['flaIC_setting_section_3']['info'] = 'Not implemented yet!';
				$a['flaIC_setting_section_3']['flaIC_op_sync_caption'] = array
						(
							'cap'=> 'Sync mode',
							'mode' => 'radio',
							'types' => array(
								0 => 'no sync',
								1 => 'attachment overrides page/post',
								2 => 'page/post overrides attachment'),
							'default' => 0
						);
			// Defaults herausschreiben
			foreach($a as $i)
				{
					foreach(preg_grep('#^(cap|info)$#',array_keys($i),PREG_GREP_INVERT) as $j)
					{
							$b[$j] = $i[$j]['default'];
					}
				}
			// in Objekt laden
			$this->ic_options['full'] = $a;
			$this->ic_options['defaults'] = $b;
		}
		
		if( count($this->wp_options) < 1 ) // Wordpress-Optionen laden
		{
			$this->wp_options = get_option( 'flaIC_option', $this->ic_options['defaults'] );
		}
		// Wordpress-Optionen um Defaults ergänzen
		foreach (array_keys($this->ic_options['defaults']) as $on)
		{
			if( !array_key_exists($on, $this->wp_options) )
			{
				$this->wp_options[$on] = $this->ic_options['defaults'][$on];
			}
		}
	}
	
/****************************************************************************************
 * Backend
 ***************************************************************************************/

/**
 * Textdomain laden
 */
	public function flaIC_load_plugin_textdomain() 
	{
		load_plugin_textdomain( self::textDomain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
 
/**
 * Backend-Optionsseite einfügen
 */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            self::pluginName . ': ' . __('Options', self::textDomain), 
            self::pluginName, 
            'manage_options', 
            'fla_ic', 
            array( $this, 'create_admin_page' )
        );
    }

/**
 * Backend-Optionsseite kreieren
 */
    public function create_admin_page()
    {
		$this->flaIC_loadPluginOptions();
		?>
        <div class="wrap">
            <?php 
			screen_icon(); 
			printf('<h2>%s: %s<h2>',
				self::pluginName,
				__('Options', self::textDomain));
			printf('<h3>%s</h3>',
				__('Plugin to insert image authors automatically', self::textDomain));
			printf('<p>%s %s via<br/>%s</p>',
				__('Plugin accesses the', self::textDomain),
				'<code>caption</code>-Shortcode',
				'<code>add_filter( \'img_caption_shortcode\', ... )</code>');
			printf('<p>%s %s<br/></p>',
				__('By default, image authors are read out of the description of the respective image.', self::textDomain),
				__('Therefore, they have to be tagged in a specific way:', self::textDomain));
			printf('<p>%s <em><a href="%s">%s</a></em> &gt; <em>%s</em>  &gt; <em>%s</em>, %s.</p>',
				__('In the description of the respective image, accessible under', self::textDomain),
				get_site_url() . '/wp-admin/upload.php',
				__('Media'),
				__('Edit'),
				__('Description'),
				__('the image author (or multiple image authors) have to be wrapped in <code>author</code> tags', self::textDomain));
			
			printf('<p>%s %s</p>',
				__("If there are no authors in image description, the plugin tries to read authors from the image's Exif information.", self::textDomain),
				__('If successful, image authors are automatically saved into image description.', self::textDomain));
			
			?>
			<form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'flaIC_option_group' );   
                do_settings_sections( 'fla_ic' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

/**
 * Backend-Optionsseite initiieren
 */
	public function flaIC_adminPageInit()
	{
        $this->flaIC_loadPluginOptions();
		
		register_setting(
            'flaIC_option_group', // Option group
            'flaIC_option', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );
		
		foreach( array_keys($this->ic_options['full']) as $section_name)
		{
			// Sektion einfügen
			add_settings_section($section_name, 
								  __($this->ic_options['full'][$section_name]['cap'], self::textDomain), 
								 array( $this, 'print_section_info' ), 
								 'fla_ic');
			// Einzelne Option einfügen
			foreach( preg_grep('#^(cap|info)$#',array_keys($this->ic_options['full'][$section_name]),PREG_GREP_INVERT) as $option_name )
			{
				add_settings_field($option_name,
								   __($this->ic_options['full'][$section_name][$option_name]['cap'], self::textDomain),
								   array( $this, 'flaIC_option_callback' ),
								   'fla_ic',
								   $section_name,
								   array_merge(array('field' => $option_name), $this->ic_options['full'][$section_name][$option_name]));
			}
		}
	}

/**
 * Backend-Optionsseite
 * Optionen säubern
 *
 * Eingabe: array $input Enthält alle Optionen als Array Keys
 */
    public function sanitize( $input )
    {
		$new_input = $input;
		
		$active = 0; // Bypass
		if( isset( $input['flaIC_op_prefix'] ) && $active == 1)
			$new_input['flaIC_op_prefix'] = sanitize_text_field( $input['flaIC_op_prefix'] );

		$active = 0; // Bypass
		if( isset( $input['flaIC_op_suffix'] )  && $active == 1)
			$new_input['flaIC_op_suffix'] = sanitize_text_field( $input['flaIC_op_suffix'] );

		$active = 1; // Bypass
		if( isset( $input['flaIC_op_mode'] )  && $active == 1)
			$new_input['flaIC_op_mode'] = absint( $input['flaIC_op_mode'] );

		return $new_input;
    }

/** 
 * Backend-Optionsseite
 * Infotext für Einstellungssektion
 */
    public function print_section_info($args)
    {
		// $args['id'] = $section_name
		$info = $this->ic_options['full'][$args['id']]['info'];
		if(is_array($info))
		{
			foreach($info as $t) 
			{
				printf('<p>%s</p>', __($t, self::textDomain));
			}
		}
		else
		{
			_e($info, self::textDomain);
		}
		// Spezialausgabe Wrapping
		if($args['id'] == 'flaIC_setting_section_2')
		{
			printf(
				'<p>%s:<br/><code>%s%s%s%s%s</code></p>',
				__('Current setting', self::textDomain), 
				esc_html($this->wp_options['flaIC_op_prefix']),
				__('Author', self::textDomain) . '1',
				esc_html($this->wp_options['flaIC_op_glue']),
				__('Author', self::textDomain) . '2',
				esc_html($this->wp_options['flaIC_op_suffix']));
		}
	}

 /** 
 * Backend-Optionsseite
 * Einstellung setzen Callback
 */
    public function flaIC_option_callback($args)
    {
		$args['mode'] = ( !isset($args['mode']) ) ? 'input' : $args['mode'] ;
		switch ($args['mode'])
		{
			case 'input':
				printf(
					'<input type="text" id="'.$args['field'].'" name="flaIC_option['.$args['field'].']" value="%s" class="%s" />',
					isset( $this->wp_options[$args['field']] ) ? esc_attr( $this->wp_options[$args['field']] ) : '*not found*',
					implode(' ', $args['classes'])
				);
				break;
			
			case 'radio':
				$args['types'] = ( !isset($args['types']) ) ? array() : $args['types'] ;
				$min = min(array_keys($args['types']));
				for( $i = $min; $i < $min + count($args['types']); $i++ ) 
				{
					$chec = ( $curr == $i ) ? ' checked="checked"' : '' ;
					printf(
						'<input type="radio" id="%s" name="%s" value="%d"%s />&nbsp;<label>%s</label><br/>',
						$args['field'].$i, // HTML_ID
						'flaIC_option['.$args['field'].']', // Feldname
						$i, // Wert
						checked( $i, $this->wp_options[$args['field']], 0  ), // Checked or not?
						__($args['types'][$i], self::textDomain) // Caption
					);
				}
				break;
		}
    }

/****************************************************************************************
 * Front-End
 ***************************************************************************************/

/**
 * Shortcode weitergeben, Instanz der Subklasse instantiieren
 */
	public function flaIC_caption_init( $empty, $attr, $content )
	{
		$attr = shortcode_atts( array(
			'id'      => '',
			'align'   => 'alignleft',
			'width'   => '',
			'caption' => ''
			), $attr );
		
		if ( (int) $attr['width'] < 1 || empty( $attr['caption'] ) || empty($attr['id'] ) ) 
		{
			return $content;
		}
		
		$a = new flaImageCaption( $attr, $content );
		return $a->html;
	}
 
/** 
 * Bildautoren-Funktion
 *
 * ergänzt $this->attr['caption']
 * schreibt evtl. in WP_Post
 */
	protected function flaIC_getImageauthors() 
	{
		$fla_authors = array();
		if($this->attr['caption'] && get_class($this->wp_post) == 'WP_Post' )
		{
			// Autoren aus wp_post-Beschreibung einlesen
			$pattern = '#<auth?or>(.+)</auth?or>#';
			if( preg_match_all($pattern,$this->wp_post->post_content,$matches,PREG_PATTERN_ORDER) )
			{
				
				$fla_authors = $matches[1];
			}
	
			// Autoren aus Datei einlesen
			if( count($fla_authors) < 1 && $exif = @exif_read_data($this->wp_post->guid,'COMPUTED') ) 
			{
				$authorstring = (isset($exif['Author']) ? $exif['Author'] : '');
				$authorstring .= (isset($exif['Artist']) ? ';'.$exif['Artist'] : '');
				if(strlen($authorstring) > 1)
				{
					$fla_authors = array_unique(explode(';',$authorstring));
					// wp_post-Beschreibung ergänzen
					foreach($fla_authors as $a)
					{
						$b[] = '<autor>' . $a . '</autor>' . "\n";
					}
					$this->update_post[] = array(
							'ID' => $this->wp_post->ID, 
							'post_content' => $this->wp_post->post_content . "\n" . implode('', $b) );
				}
			}
		
			// Keine Autoren gefunden -> Standardautor
			if( count($fla_authors) < 1 && $this->wp_options['flaIC_op_mode'] == 2 )
			{
				$fla_authors[] = $this->wp_options['flaIC_op_defaut'];
			}
		
			if( count($fla_authors) > 0 )
			{
				// print_r($this->ic_options['defaults']); echo "<br>\n";
				// print_r($this->wp_options); echo "<br>\n";
				$this->attr['caption'] .= sprintf('%s%s%s',
										$this->wp_options['flaIC_op_prefix'],
										implode($this->wp_options['flaIC_op_glue'], $fla_authors),
										$this->wp_options['flaIC_op_suffix']);
				// Debug-Ausgabe
				// $this->attr['caption'] .= ' '.$this->wp_post->post_content;
			}
		}	
	}

/** 
 * Attachment updaten
 */
	protected function flaIC_updateAtt()
	{
		if(count($this->update_post) > 0)
		{
			foreach($this->update_post as $u)
			{
				wp_update_post( $u );
				// print_r($this->update_post);
			}
		}
	}

	
/** 
 * Bildbeschriftung abgleichen zwischen Page/Post und Attachment
 *
 * coming soon!
 */
	protected function flaIC_syncCaptions()
	{
	}
	
} // Ende Klasse
endif;

/****************************************************************************************
 * Subklasse für einzelne Caption
 ***************************************************************************************/
if( !class_exists( 'flaImageCaption' ) ) : 
class flaImageCaption extends flaImageCaption_Main
{
/**
 * Variablen
 */
	protected $attr, $content;
	public $html = '';

/**
 * Konstruktor
 */
	public function __construct( $attr, $content )
	{
   		$this->flaIC_loadPluginOptions();
   		$this->attr = $attr;
   		$this->content = $content;
		$this->flaIC_caption();
	}

/**
 * Eigene Caption-Funktion
 *
 * $this->attr [id] => id="attachment_892"
 *       [align] => alignright 
 *       [width] => 300 
 *       [caption] => Das Horn- und ein Teil des Perkussionsregisters des Musikvereins Goldau
 */
	protected function flaIC_caption()
	{
		// ID-String für HTML formatieren
		$this->attr['id'] = 'id="' . esc_attr( $this->attr['id'] ) . '" ';
			
		// Attachment-WP_Post herauslesen
		if(@preg_match('#\d+#', $this->attr['id'], $att_id)) 
		{
			if($this->wp_post = get_post($att_id[0]))
			{
				$this->flaIC_syncCaptions();
				$this->flaIC_getImageauthors();
				$this->flaIC_updateAtt();
			}
		}
	
		$this->html = sprintf('<div %s class="wp-caption %s" style="width:%dpx">%s<p class="wp-caption-text">%s</p></div>',
							$this->attr['id'],
							esc_attr( $this->attr['align'] ),
							(10 + (int) $this->attr['width']),
							do_shortcode( $this->content ),
							$this->attr['caption']);
	}
}  // Ende Klasse
endif;

$a = new flaImageCaption_Main();