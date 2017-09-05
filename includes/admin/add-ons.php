<?php
/**
 * Admin Add-ons Pages
 *
 * @package     GamiPress\Admin\Add_ons
 * @since       1.1.0
 */
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

/**
 * Add-ons page
 *
 * @since  1.1.0
 *
 * @return void
 */
function gamipress_add_ons_page() {

    if( ! function_exists( 'plugins_api' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    }

    wp_enqueue_script( 'plugin-install' );
    add_thickbox();
    wp_enqueue_script( 'updates' );

    ?>
    <div class="wrap">
        <div id="icon-options-general" class="icon32"></div>
        <h1 class="wp-heading-inline"><?php _e( 'GamiPress Add-ons', 'gamipress' ); ?></h1>
        <hr class="wp-header-end">

        <p><?php _e( 'Add-ons to extend and expand the functionality of GamiPress.', 'gamipress' ); ?></p>

        <form id="plugin-filter" method="post">
            <div class="wp-list-table widefat gamipress-add-ons">

            <?php

            $plugins = gamipress_plugins_api();

            if ( is_wp_error( $plugins ) ) {
                echo $plugins->get_error_message();
                return;
            }

            foreach ( $plugins as $plugin ) {

                gamipress_render_plugin_card( $plugin );

            }

            ?>
            </div>
        </form>
    </div>
    <?php
}

/**
 * Return an array of all installed plugins slugs
 *
 * @since  1.1.0
 *
 * @return array
 */
function gamipress_get_installed_plugins_slugs() {

    $slugs = array();

    $plugin_info = get_site_transient( 'update_plugins' );
    if ( isset( $plugin_info->no_update ) ) {
        foreach ( $plugin_info->no_update as $plugin ) {
            $slugs[] = $plugin->slug;
        }
    }

    if ( isset( $plugin_info->response ) ) {
        foreach ( $plugin_info->response as $plugin ) {
            $slugs[] = $plugin->slug;
        }
    }

    return $slugs;

}

/**
 * Helper function to render a plugin card from the add-ons page
 *
 * @since  1.1.0
 *
 * @param stdClass $plugin
 *
 * @return void
 */
function gamipress_render_plugin_card( $plugin ) {

    if( $plugin->info->status !== 'publish' ) {
        return;
    }

    $name = $plugin->info->title;
    $slug = $plugin->wp_info ? $plugin->wp_info->slug : $plugin->info->slug;

    $action_links = array();

    if( $plugin->wp_info ) {
        $details_link = self_admin_url( 'plugin-install.php?tab=plugin-information&amp;plugin=' . $slug . '&amp;TB_iframe=true&amp;width=600&amp;height=550' );

        // Check plugin status
        if ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' ) ) {
            $status = install_plugin_install_status( $plugin->wp_info );

            switch ( $status['status'] ) {
                case 'install':
                    if ( $status['url'] ) {
                        $action_links[] = '<a class="install-now button" data-slug="' . esc_attr( $slug ) . '" href="' . esc_url( $status['url'] ) . '" aria-label="' . esc_attr( sprintf( __( 'Install %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '">' . __( 'Install Now' ) . '</a>';
                    }
                    break;

                case 'update_available':
                    if ( $status['url'] ) {
                        $action_links[] = '<a class="update-now button aria-button-if-js" data-plugin="' . esc_attr( $status['file'] ) . '" data-slug="' . esc_attr( $slug ) . '" href="' . esc_url( $status['url'] ) . '" aria-label="' . esc_attr( sprintf( __( 'Update %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '">' . __( 'Update Now' ) . '</a>';
                    }
                    break;

                case 'latest_installed':
                case 'newer_installed':
                    if ( is_plugin_active( $status['file'] ) ) {
                        $action_links[] = '<button type="button" class="button button-disabled" disabled="disabled">' . _x( 'Active', 'plugin' ) . '</button>';
                    } elseif ( current_user_can( 'activate_plugins' ) ) {
                        $button_text  = __( 'Activate' );
                        $button_label = _x( 'Activate %s', 'plugin' );
                        $activate_url = add_query_arg( array(
                            '_wpnonce'    => wp_create_nonce( 'activate-plugin_' . $status['file'] ),
                            'action'      => 'activate',
                            'plugin'      => $status['file'],
                        ), network_admin_url( 'plugins.php' ) );

                        if ( is_network_admin() ) {
                            $button_text  = __( 'Network Activate' );
                            $button_label = _x( 'Network Activate %s', 'plugin' );
                            $activate_url = add_query_arg( array( 'networkwide' => 1 ), $activate_url );
                        }

                        $action_links[] = sprintf(
                            '<a href="%1$s" class="button activate-now" aria-label="%2$s">%3$s</a>',
                            esc_url( $activate_url ),
                            esc_attr( sprintf( $button_label, $name ) ),
                            $button_text
                        );
                    } else {
                        $action_links[] = '<button type="button" class="button button-disabled" disabled="disabled">' . _x( 'Installed', 'plugin' ) . '</button>';
                    }
                    break;
            }
        }

        $action_links[] = '<a href="' . esc_url( $details_link ) . '" class="more-details thickbox open-plugin-details-modal" aria-label="' . esc_attr( sprintf( __( 'More information about %s' ), $name ) ) . '" data-title="' . esc_attr( $name ) . '">' . __( 'More Details' ) . '</a>';
    } else {
        $details_link = 'https://gamipress.com/add-ons/' . $plugin->info->slug;

        $action_links[] = '<a href="' . $details_link . '" class="button button-primary" target="_blank">' . __( 'Get this Add-on', 'gamipress' ) . '</a>';
    }


    ?>
    <div class="gamipress-plugin-card plugin-card plugin-card-<?php echo sanitize_html_class( $slug ); ?>">

        <div class="plugin-card-top">

            <div class="thumbnail column-thumbnail">
                <a href="<?php echo esc_url( $details_link ); ?>" <?php if( $plugin->wp_info ) : ?>class="thickbox open-plugin-details-modal"<?php else : ?>target="_blank"<?php endif; ?>>
                    <img src="<?php echo esc_attr( $plugin->info->thumbnail ) ?>" class="plugin-thumbnail" alt="">
                </a>
            </div>

            <div class="name column-name">
                <h3>
                    <a href="<?php echo esc_url( $details_link ); ?>" <?php if( $plugin->wp_info ) : ?>class="thickbox open-plugin-details-modal"<?php else : ?>target="_blank"<?php endif; ?>>
                        <?php echo $name; ?>
                    </a>
                </h3>
            </div>

            <div class="desc column-description">
                <p><?php echo $plugin->info->excerpt; ?></p>
            </div>

        </div>

        <div class="plugin-card-bottom">
            <div class="action-links">
                <?php if ( $action_links ) {
                    echo '<ul class="plugin-action-buttons"><li>' . implode( '</li><li>', $action_links ) . '</li></ul>';
                } ?>
            </div>
        </div>

    </div>
    <?php
}

/**
 * Function to contact with the GamiPress website API
 *
 * @since  1.1.0
 *
 * @return object|WP_Error Object with GamiPress plugins
 */
function gamipress_plugins_api() {

    // If a plugins api request has been cached already, then use cached plugins
    if ( false !== ( $res = get_transient( 'gamipress_plugins_api' ) ) ) {
        return $res;
    }

    $url = $http_url = 'http://gamipress.com/edd-api/products/';

    if ( $ssl = wp_http_supports( array( 'ssl' ) ) ) {
        $url = set_url_scheme( $url, 'https' );
    }

    $http_args = array(
        'timeout' => 15,
    );

    $request = wp_remote_get( $url, $http_args );

    if ( $ssl && is_wp_error( $request ) ) {
        trigger_error(
            sprintf(
                __( 'An unexpected error occurred. Something may be wrong with gamipress.com or this server&#8217;s configuration. If you continue to have problems, please try to <a href="%s">contact us</a>.', 'gamipress' ),
                'https://gamipress.com/contact-us/'
            ) . ' ' . __( '(WordPress could not establish a secure connection to gamipress.com. Please contact your server administrator.)' ),
            headers_sent() || WP_DEBUG ? E_USER_WARNING : E_USER_NOTICE
        );

        $request = wp_remote_get( $http_url, $http_args );
    }

    if ( is_wp_error( $request ) ) {
        $res = new WP_Error( 'gamipress_plugins_api_failed',
            sprintf(
                __( 'An unexpected error occurred. Something may be wrong with gamipress.com or this server&#8217;s configuration. If you continue to have problems, please try to <a href="%s">contact us</a>.', 'gamipress' ),
                'https://gamipress.com/contact-us/'
            ),
            $request->get_error_message()
        );
    } else {
        $res = json_decode( $request['body'] );

        $res = (array) $res->products;

        // Set a transient of 12 hours with api plugins
        set_transient( 'gamipress_plugins_api', $res, 12 * HOUR_IN_SECONDS );
    }

    return $res;

}