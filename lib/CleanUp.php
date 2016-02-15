<?php
/**
 * WordPress theme mess cleanup methods
 *
 * PHP version 5
 *
 * @category CleanUp
 * @package  Raccoon
 * @author   Damien Senger <hi@hiwelo.co>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3.0
 * @link     ./docs/api/classes/Hwlo.Raccoon.Core.html
 * @since    1.0.0
 */
namespace Hiwelo\Raccoon;

/**
 * WordPress theme mess cleanup methods
 *
 * PHP version 5
 *
 * @category CleanUp
 * @package  Raccoon
 * @author   Damien Senger <hi@hiwelo.co>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3.0
 * @link     ./docs/api/classes/Hwlo.Raccoon.Core.html
 * @since    1.0.0
 */
class CleanUp
{
    /**
      * CleanUp configuration from the manifest
      *
      * @var array
      */
    private $cleanUp = [];

    /**
     * CleanUp default configuration
     *
     * @var array
     */
    public $default = [
        "admin" => [
            "metaboxes" => [
                "dashboard_incoming_links",
                "dashboard_quick_press",
                "dashboard_plugins",
                "dashboard_recent_drafts",
                "dashboard_recent_comments",
                "dashboard_primary",
                "dashboard_secondary",
                "dashboard_activity",
            ],
        ],
        "security" => [
            "wlwmanifest_link",
            "rsd_link",
            "index_rel_link",
            "parent_post_rel_link",
            "start_post_rel_link",
            "adjacent_posts_rel_link",
            "feed_links_extra",
            "adjacent_posts_rel_link_wp_head",
            "wp_generator",
            "wp_shortlink_wp_head",
            "no-ftp",
            "login-error"
        ],
        "wp_head" => [
            "remove-adminbar-css",
            "emoji-css",
        ]
    ];

    /**
      * Clean up class constructor, check for configuration or informations
      * in the manifest
      *
      * @param array $configuration cleanUp configuration
      * @return void
      *
      * @link  https://codex.wordpress.org/Function_Reference/get_template_directory
      * @since 1.0.0
      * @uses  CleanUp::adminCleanUp()
      * @uses  CleanUp::defaultThemesCleanUp()
      * @uses  CleanUp::securityCleanUp()
      * @uses  CleanUp::wpheadCleanUp()
      * @uses  Tools::parseBooleans()
      */
    public function __construct($configuration = [])
    {
        // load manifest with an empty configuration
        if (count($configuration) === 0) {
            $file = get_template_directory() . '/' . $file;

            // verify if file exists
            if (!file_exists($file)) {
                return false;
            }

            $file = file_get_contents($file);
            $manifest = json_decode($file, true);

            if (array_key_exists('theme-features', $manifest)
                && array_key_exists('cleanup', $manifest['theme-features'])
            ) {
                $configuration = $manifest['theme-features']['cleanup'];
            }
        }

        if (is_array($configuration)) {
            $this->cleanUp = array_merge($this->default, $configuration);
        } else {
            Tools::parseBooleans($configuration);
            if ($configuration) {
                $this->cleanUp = $this->default;
            }
        }

        // we call admin clean up parts, if asked in the manifest
        $this->adminCleanUp();

        // we call security clean up parts, if asked in the manifest
        $this->securityCleanUp();

        // we call wp_head clean up parts, if asked in the manifest
        $this->wpheadCleanUp();

        // we call default theme clean up parts, if asked in the manifest
        if (array_key_exists('themes', $this->cleanUp)) {
            if ($this->cleanUp['themes']) {
                $this->defaultThemesCleanUp();
            }
        }
    }

    /**
      * Clean Up WordPress Admin mess
      *
      * @return void
      *
      * @link  https://codex.wordpress.org/Function_Reference/add_action
      * @link  https://codex.wordpress.org/Function_Reference/remove_meta_box
      * @since 1.0.0
      * @uses  Tools::parseBooleans()
      */
    public function adminCleanUp()
    {
        if (is_array($this->cleanUp['admin'])) {
            $this->cleanUp['admin'] = array_merge(
                $this->default['admin'],
                $this->cleanUp['admin']
            );
        } else {
            Tools::parseBooleans($this->cleanUp['admin']);
            if ($this->cleanUp['admin']) {
                $this->cleanUp['admin'] = $this->default['admin'];
            }
        }

        if (array_key_exists('admin', $this->cleanUp)
            && is_array($this->cleanUp['admin'])
            && array_key_exists('metaboxes', $this->cleanUp['admin'])
            && is_array($this->cleanUp['admin']['metaboxes'])
            && count($this->cleanUp['admin']['metaboxes'])
        ) {
            $metaboxes = $this->cleanUp['admin']['metaboxes'];

            foreach ($metaboxes as $metabox) {
                add_action('admin_menu', function () use ($metabox) {
                    // remove comment status
                    remove_meta_box($metabox, 'dashboard', 'core');
                });
            }
        }
    }

    /**
      * Clean up WordPress wp_head mess for more security
      *
      * @return void
      *
      * @link  https://codex.wordpress.org/Function_Reference/add_filter
      * @link  https://codex.wordpress.org/Function_Reference/remove_action
      * @since 1.0.0
      * @uses  Tools::parseBooleans()
      */
    public function securityCleanUp()
    {
        if (is_array($this->cleanUp['security'])) {
            $this->cleanUp['security'] = array_merge(
                $this->default['security'],
                $this->cleanUp['security']
            );
        } else {
            Tools::parseBooleans($this->cleanUp['security']);
            if ($this->cleanUp['security']) {
                $this->cleanUp['security'] = $this->default['security'];
            }
        }

        if (count($this->cleanUp['security'])) {
            foreach ($this->cleanUp['security'] as $action) {
                switch ($action) {
                    case 'no-ftp':
                        define('FS_METHOD', 'direct');
                        break;

                    case 'login-error':
                        add_filter('login_errors', function ($defaults) {
                            return null;
                        });
                        break;

                    default:
                        remove_action('wp_head', $action);
                        break;
                }
            }
        }
    }

    /**
      * Remove default WordPress theme from admin panel lists
      *
      * @global array $wp_theme_directories List all themes directories
      *
      * @return void
      *
      * @link  https://developer.wordpress.org/reference/classes/wp_theme
      * @link  https://developer.wordpress.org/reference/functions/add_action
      * @link  https://developer.wordpress.org/reference/functions/wp_get_themes
      * @since 1.0.0
      */
    public function defaultThemesCleanUp()
    {
        // if WordPress have multiple theme directories and one looks like the
        // Bedrock theme directory, we unset all different directories
        global $wp_theme_directories;

        if (count($wp_theme_directories) > 1) {
            // we check if we have an app/ dir
            $path_end_part = substr(ABSPATH, -4, 4);

            if ($path_end_part === '/wp/') {
                $bedrock_theme_dir = substr(ABSPATH, 0, -4) . '/app/themes';
            }

            if (in_array($bedrock_theme_dir, $wp_theme_directories)) {
                foreach ($wp_theme_directories as $key => $theme_dir) {
                    if ($theme_dir !== $bedrock_theme_dir) {
                        unset($wp_theme_directories[$key]);
                    }
                }
            }
        }

        $themes = wp_get_themes();

        foreach ($themes as $slug => $theme) {
            $author = $theme->get('Author');

            if ($author === 'the WordPress team') {
                unset($themes[$slug]);
            }
        }

        // remove element from the dashboard activity widget
        add_action('admin_footer-index.php', function () {
            echo "
                <script>
                    jQuery(document).ready(function () {
                        jQuery('p.hide-if-no-customize').remove();
                    });
                </script>
            ";
        });
    }

    /**
     * WP_head() mess clean up method
     *
     * @return void
     *
     * @link  https://developer.wordpress.org/reference/functions/add_theme_support
     * @link  https://developer.wordpress.org/reference/functions/remove_action
     * @link  https://developer.wordpress.org/reference/functions/remove_filter
     * @since 1.0.0
     * @uses  Tools::parseBooleans();
     */
    private function wpheadCleanUp()
    {
        if (is_array($this->cleanUp['wp_head'])) {
            $this->cleanUp['wp_head'] = array_merge(
                $this->default['wp_head'],
                $this->cleanUp['wp_head']
            );
        } else {
            Tools::parseBooleans($this->cleanUp['wp_head']);
            if ($this->cleanUp['wp_head']) {
                $this->cleanUp['wp_head'] = $this->default['wp_head'];
            }
        }

        if (count($this->cleanUp['wp_head'])) {
            foreach ($this->cleanUp['wp_head'] as $action) {
                switch ($action) {
                    case 'remove-adminbar-css':
                        add_theme_support('admin-bar', ['callback' => '__return_false']);
                        break;

                    case 'emoji-css':
                        remove_action('admin_print_styles', 'print_emoji_styles');
                        remove_action('wp_head', 'print_emoji_detection_script', 7);
                        remove_action('admin_print_scripts', 'print_emoji_detection_script');
                        remove_action('wp_print_styles', 'print_emoji_styles');
                        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
                        remove_filter('the_content_feed', 'wp_staticize_emoji');
                        remove_filter('comment_text_rss', 'wp_staticize_emoji');
                        break;
                }
            }
        }
    }
}
