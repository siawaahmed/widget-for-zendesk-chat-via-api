<?php
/**
 * Plugin Name:       Zendesk Chat Widget via API
 * Plugin URI:        https://www.pluginsandsnippets.com/
 * Description:       This plugin loads Zendesk Chat widget (formerly Zopim chat) via API with a slight time delay. This improves the page loading speed of your website compared to the standard Zendesk Chat plugin. Make your website faster loading Zendesk Chat widget this way!
 * Version:           1.1.1
 * Author:            Plugins & Snippets
 * Author URI:        https://www.pluginsandsnippets.com
 * Text Domain:       zendesk-chat-widget-via-api
 * Requires at least: 3.5
 * Tested up to:      5.9.1
 *
 * @author            PluginsandSnippets.com
 * @copyright         All rights reserved Copyright (c) 2022, PluginsandSnippets.com
 *
 */

if ( !class_exists( 'PS_Zendesk_Chat_Widget_Via_Api' ) ) {
    class PS_Zendesk_Chat_Widget_Via_Api {
        
        public function __construct() {
            
            // Plugin version
            define( 'PS_ZENDESK_CHAT_WIDGET_VIA_API_VER', '1.1.1' );
            
            // Plugin name
            define( 'PS_ZENDESK_CHAT_WIDGET_VIA_API_NAME', 'Zendesk Chat Widget via API' );
            
            // Plugin path
            define( 'PS_ZENDESK_CHAT_WIDGET_VIA_API_DIR', plugin_dir_path( __FILE__ ) );
            
            define( 'PS_ZENDESK_CHAT_WIDGET_VIA_API_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
            
            define( 'PS_ZENDESK_CHAT_WIDGET_VIA_API_DOCUMENTATION_URL', 'https://www.pluginsandsnippets.com/' );
            
            define( 'PS_ZENDESK_CHAT_WIDGET_VIA_API_OPEN_TICKET_URL', 'https://www.pluginsandsnippets.com/open-ticket/' );
            
            
            define( 'PS_ZENDESK_CHAT_WIDGET_VIA_API_REVIEW_URL', 'https://wordpress.org/plugins/zendesk-chat-widget-via-api/reviews/#new-post' );
            
            
            add_action( 'admin_menu', array(
                $this,
                'create_options_menu' 
            ) );
            add_action( 'wp_footer', array(
                $this,
                'init_zendesk_chat_widget' 
            ) );
            add_action( 'wp_ajax_ps_zendesk_chat_widget_via_api_review_notice', array(
                $this,
                'dismiss_review_notice' 
            ) );
            
            if ( is_admin() ) {
                add_action( 'admin_enqueue_scripts', array(
                    $this,
                    'load_admin_css' 
                ) );
            }
            
            if ( !get_option( 'ps_zendesk_chat_widget_via_api_review_time' ) ) {
                $review_time = time() + 7 * DAY_IN_SECONDS;
                add_option( 'ps_zendesk_chat_widget_via_api_review_time', $review_time, '', false );
            }
            
            if ( is_admin() && get_option( 'ps_zendesk_chat_widget_via_api_review_time' ) && get_option( 'ps_zendesk_chat_widget_via_api_review_time' ) < time() && !get_option( 'ps_zendesk_chat_widget_via_api_dismiss_review_notice' ) ) {
                add_action( 'admin_notices', array(
                    $this,
                    'notice_review' 
                ) );
                add_action( 'admin_footer', array(
                    $this,
                    'notice_review_script' 
                ) );
            }
            
            add_action( 'plugin_row_meta', array(
                $this,
                'add_action_links' 
            ), 10, 2 );
            add_action( 'admin_footer', array(
                $this,
                'add_deactive_modal' 
            ) );
            add_action( 'wp_ajax_ps_zendesk_chat_widget_via_api_deactivation', array(
                $this,
                'ps_zendesk_chat_widget_via_api_deactivation' 
            ) );
            add_action( 'plugin_action_links', array(
                $this,
                'ps_zendesk_chat_widget_via_api_action_links' 
            ), 10, 2 );
        }
        
        public function get_api_code() {
            return get_option( 'ps_zendesk_chat_widget_api_code' );
        }
        
        public function create_options_menu() {
            
            add_submenu_page( 'options-general.php', __( 'Zendesk Chat Settings', 'ps-zendesk-chat-widget-via-api' ), __( 'Zendesk Chat Settings', 'ps-zendesk-chat-widget-via-api' ), 'manage_options', 'ps-zendesk-chat-widget-via-api', array(
                $this,
                'options_page' 
            ) );
        }
        
        public function options_page() {
            
            if ( isset( $_POST['ps_zendesk_chat_widget_api_code'] ) ) {
                update_option( 'ps_zendesk_chat_widget_api_code', $_POST['ps_zendesk_chat_widget_api_code'], false );
            }
            
            $code = $this->get_api_code();
            echo '<h1>' . __( 'Zendesk Chat Settings', 'ps-zendesk-chat-widget-via-api' ) . '</h1>';
            
            echo '<form method="POST">';
            echo '<p>' . __( 'Please enter your Zendesk Chat Account Key so that the Zendesk Chat Widget can be loaded via API. After entering the key please clear all caches and please disable the regular Zendesk Chat plugin as it will no longer be needed. Now the Zendesk Chat widget will be loaded via API with a slight time delay which improves the page loading speed of your website. Make your website faster with this plugin.', 'ps-zendesk-chat-widget-via-api' ) . '</p>';
            echo '<p><a href="https://support.zendesk.com/hc/en-us/articles/4408825772698-How-do-I-find-my-Chat-Account-Key-" target="_blank">' . __( 'Find your Account Key', 'ps-zendesk-chat-widget-via-api' ) . '</a></p>';
            echo '<div class="ps-zendesk-chat-widget-via-api-field">';
            echo '<label for="ps-zendesk-chat-widget-via-api-code">' . __( 'Zendesk Chat API Code', 'ps-zendesk-chat-widget-via-api' ) . '</label>';
            echo '<input type="text" name="ps_zendesk_chat_widget_api_code" value="' . esc_attr( $code ) . '" />';
            echo '</div>';
            
            echo '<div class="ps-zendesk-chat-widget-via-api-submit">
                        <button type="submit" class="button-primary">' . __( 'Save', 'ps-zendesk-chat-widget-via-api' ) . '</button>
                    </div>';
            echo '</form>';
        }
        
        public function init_zendesk_chat_widget() {
            $code = $this->get_api_code();
            
            if ( empty( $code ) ) {
                return;
            }
            
            $markup = '<script>
                    function load_zopim() {
                        window.$zopim||(function(d,s){var z=$zopim=function(c){z._.push(c)},$=z.s=
                        d.createElement(s),e=d.getElementsByTagName(s)[0];z.set=function(o){z.set.
                        _.push(o)};z._=[];z.set._=[];$.async=!0;$.setAttribute(\'charset\',\'utf-8\');
                        $.src=\'//v2.zopim.com/?' . $code . '\';z.t=+new Date;$.
                        type=\'text/javascript\';e.parentNode.insertBefore($,e)})(document,\'script\');
                    }
                </script>';
            
            $current_user_data_set = '';
            
            if ( is_user_logged_in() ) {
                $current_user = wp_get_current_user();
                $first_name   = $current_user->display_name;
                $user_email   = $current_user->user_email;
                
                $current_user_data_set = '$zopim(function(){$zopim.livechat.set({name: \'' . $first_name . '\', email: \'' . $user_email . '\'}); });';
            }
            
            $markup .= '<script>';
            $markup .= 'function call_zopim() {
                    ' . $current_user_data_set . '
                    $zopim( function() {});
                }';
            
            // Following JS loads and calls widget when one of two criterian is met
            $markup .= 'var zopim_loaded = false;
                jQuery(window).on(\'scroll\', function() {
                    window.setTimeout(function() {
                        if( ! zopim_loaded ) {
                            load_zopim();
                            call_zopim();
                            zopim_loaded = true;
                        }
                    }, 3000);
                });

                jQuery(window).on(\'load\', function() {
                    window.setTimeout(function() {
                        if( ! zopim_loaded ) {
                            load_zopim();
                            call_zopim();
                            zopim_loaded = true;
                        }
                    }, 10000);
                });';
            
            $markup .= '</script>';
            
            echo $markup;
        }
        
        public function load_admin_css() {
            wp_enqueue_script( 'ps-zendesk-chat-widget-via-api-admin-js', PS_ZENDESK_CHAT_WIDGET_VIA_API_PLUGIN_URL . 'assets/js/admin.js' );
            wp_enqueue_style( 'ps-zendesk-chat-widget-via-api-admin-css', PS_ZENDESK_CHAT_WIDGET_VIA_API_PLUGIN_URL . 'assets/css/admin.css', array(), PS_ZENDESK_CHAT_WIDGET_VIA_API_VER, 'all' );
        }
        
        /**
         * Ask the user to leave a review for the plugin.
         */
        public function notice_review() {
            global $current_user;
            wp_get_current_user();
            $user_n = '';
            if ( !empty( $current_user->display_name ) ) {
                $user_n = " " . $current_user->display_name;
            }
            
            echo "<div id='ps-zendesk-chat-widget-via-api-review' class='notice notice-info is-dismissible'><p>" . sprintf( __( "Hi%s, Thank you for using <b>" . PS_ZENDESK_CHAT_WIDGET_VIA_API_NAME . "</b>. Please don't forget to rate our plugin. We sincerely appreciate your feedback.", 'ps-zendesk-chat-widget-via-api' ), $user_n ) . '<br><a target="_blank" href="' . PS_ZENDESK_CHAT_WIDGET_VIA_API_REVIEW_URL . '" class="button-secondary">' . esc_html__( 'Post Review', 'ps-zendesk-chat-widget-via-api' ) . '</a>' . '</p></div>';
        }
        
        /**
         * Loads the inline script to dismiss the review notice.
         */
        public function notice_review_script() {
            echo "<script>\n" . "jQuery(document).on('click', '#ps-zendesk-chat-widget-via-api-review .notice-dismiss', function() {\n" . "\tvar ps_zendesk_chat_widget_via_api_review_data = {\n" . "\t\taction: 'ps_zendesk_chat_widget_via_api_review_notice',\n" . "\t};\n" . "\tjQuery.post(ajaxurl, ps_zendesk_chat_widget_via_api_review_data, function(response) {\n" . "\t\tif (response) {\n" . "\t\t\tconsole.log(response);\n" . "\t\t}\n" . "\t});\n" . "});\n" . "</script>\n";
        }
        
        /**
         * Disables the notice about leaving a review.
         */
        public function dismiss_review_notice() {
            update_option( 'ps_zendesk_chat_widget_via_api_dismiss_review_notice', true, false );
            wp_die();
        }
        
        /**
         * Add support link
         *
         * @since 1.0.0
         * @param array $plugin_meta
         * @param string $plugin_file
         */
        
        public function add_action_links( $plugin_meta, $plugin_file ) {
            
            if ( $plugin_file === plugin_basename( __FILE__ ) ) {
                $link = '<a href="' . PS_ZENDESK_CHAT_WIDGET_VIA_API_DOCUMENTATION_URL . '" target="_blank">' . __( 'Documentation', 'ps-zendesk-chat-widget-via-api' ) . '</a>';
                
                array_push( $plugin_meta, $link );

                $link = '<a href="' . PS_ZENDESK_CHAT_WIDGET_VIA_API_OPEN_TICKET_URL . '" target="_blank">' . __( 'Open Support Ticket', 'ps-zendesk-chat-widget-via-api' ) . '</a>';
                
                array_push( $plugin_meta, $link );

                $link = '<a href="' . PS_ZENDESK_CHAT_WIDGET_VIA_API_REVIEW_URL . '" target="_blank">' . __( 'Post Review', 'ps-zendesk-chat-widget-via-api' ) . '</a>';

                array_push( $plugin_meta, $link );

            }
            
            return $plugin_meta;
        }
        
        /**
         * Add deactivate modal layout.
         */
        public function add_deactive_modal() {
            global $pagenow;
            
            if ( 'plugins.php' !== $pagenow ) {
                return;
            }
            include PS_ZENDESK_CHAT_WIDGET_VIA_API_DIR . 'includes/deactivation-form.php';
        }
        
        /**
         * Called after the user has submitted his reason for deactivating the plugin.
         *
         * @since  1.0.0
         */
        public function ps_zendesk_chat_widget_via_api_deactivation() {

            wp_verify_nonce( $_REQUEST['ps_zendesk_chat_widget_via_api_deactivation_nonce'], 'ps_zendesk_chat_widget_via_api_deactivation_nonce' );
            
            if ( !current_user_can( 'manage_options' ) ) {
                wp_die();
            }
            
            $reason_id = sanitize_text_field( wp_unslash( $_POST['reason'] ) );
            
            if ( empty( $reason_id ) ) {
                wp_die();
            }
            
            $reason_info = sanitize_text_field( wp_unslash( $_POST['reason_detail'] ) );
            
            if ( 1 === $reason_id ) {
                $reason_text = 'I only needed the plugin for a short period';
            } elseif ( 2 === $reason_id ) {
                $reason_text = 'I found a better plugin';
            } elseif ( 3 === $reason_id ) {
                $reason_text = 'The plugin broke my site';
            } elseif ( 4 === $reason_id ) {
                $reason_text = 'The plugin suddenly stopped working';
            } elseif ( 5 === $reason_id ) {
                $reason_text = 'I no longer need the plugin';
            } elseif ( 6 === $reason_id ) {
                $reason_text = 'It\'s a temporary deactivation. I\'m just debugging an issue.';
            } elseif ( 7 === $reason_id ) {
                $reason_text = 'Other';
            }
            
            $cuurent_user = wp_get_current_user();
            
            $options = array(
                'plugin_name'       => PS_ZENDESK_CHAT_WIDGET_VIA_API_NAME,
                'plugin_version'    => PS_ZENDESK_CHAT_WIDGET_VIA_API_VER,
                'reason_id'         => $reason_id,
                'reason_text'       => $reason_text,
                'reason_info'       => $reason_info,
                'display_name'      => $cuurent_user->display_name,
                'email'             => get_option( 'admin_email' ),
                'website'           => get_site_url(),
                'blog_language'     => get_bloginfo( 'language' ),
                'wordpress_version' => get_bloginfo( 'version' ),
                'php_version'       => PHP_VERSION 
            );
            
            $to      = 'info@pluginsandsnippets.com';
            $subject = 'Plugin Uninstallation';
            $body    = '<p>Plugin Name: ' . PS_ZENDESK_CHAT_WIDGET_VIA_API_NAME . '</p>';
            $body   .= '<p>Plugin Version: ' . PS_ZENDESK_CHAT_WIDGET_VIA_API_VER . '</p>';
            $body   .= '<p>Reason: ' . $reason_text . '</p>';
            $body   .= '<p>Reason Info: ' . $reason_info . '</p>';
            $body   .= '<p>Admin Name: ' . $cuurent_user->display_name . '</p>';
            $body   .= '<p>Admin Email: ' . get_option( 'admin_email' ) . '</p>';
            $body   .= '<p>Website: ' . get_site_url() . '</p>';
            $body   .= '<p>Website Language: ' . get_bloginfo( 'language' ) . '</p>';
            $body   .= '<p>Wordpress Version: ' . get_bloginfo( 'version' ) . '</p>';
            $body   .= '<p>PHP Version: ' . PHP_VERSION . '</p>';
            $headers = array(
                'Content-Type: text/html; charset=UTF-8' 
            );
            
            wp_mail( $to, $subject, $body, $headers );
            
            wp_die();
        }
        
        /**
         * Add a link to the settings page to the plugins list
         *
         * @since  1.0.0
         */
        public function ps_zendesk_chat_widget_via_api_action_links( $links, $file ) {
            
            static $this_plugin;
            
            if ( empty( $this_plugin ) ) {
                
                $this_plugin = 'zendesk-chat-widget-via-api/zendesk-chat-widget-via-api.php';
            }
            
            if ( $file == $this_plugin ) {
                
                $settings_link = sprintf( esc_html__( '%1$s Settings %2$s', 'ps-zendesk-chat-widget-via-api' ), '<a href="' . admin_url( 'options-general.php?page=ps-zendesk-chat-widget-via-api' ) . '">', '</a>' );
                
                array_unshift( $links, $settings_link );
                
            }
            
            return $links;
        }
    }
    
    // Instantiate the class
    new PS_Zendesk_Chat_Widget_Via_Api();
}