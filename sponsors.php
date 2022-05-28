<?php
/**
 * Plugin Name: Sponsors
 * Description: Complete URL management system that allows you create, manage, and track outbound links from your site by using custom post types and 301 redirects.
 * Version: 1.0.3
 * RequiresWP: 5.6
 * Requires PHP: 7.4.6
 * Text Domain: sponsors
 */
namespace app;

if (! defined('ABSPATH'))
{
    exit;
}
class SPONSORS
{
    private string $slug                 = 'sponsors';
    private string $redirect_to          = '_sponsors_redirect';
    private string $target               = '_sponsors_target';
    private string $meta_count           = '_sponsors_count_redirect';
    private string $rewrite_slug_default = 'go';
    private int    $position             = 15;
    private string $icon                 = 'dashicons-admin-links';
    private array  $support              = array('title', 'thumbnail', 'page-attributes');
    private array  $size                 = array(48,48);


    public function __construct()
    {
        add_action( 'init', array($this, 'init') );
        add_action( 'admin_menu', array($this, 'add_meta_box') );
        add_action( 'save_post', array($this, 'meta_box_save'),10,2 );
        add_action( 'template_redirect', array($this, 'template_redirect') );
        add_action( 'pre_get_posts', array($this, 'columns_order') );
        add_filter( 'manage_edit-'.$this->slug.'_columns', array($this, 'columns_filter') );
        add_filter( 'manage_edit-'.$this->slug.'_sortable_columns', array($this, 'column_sortable') );
        add_filter( 'manage_posts_columns', array($this, 'add_thumbnails_column') );
        add_action( 'manage_posts_custom_column', array($this, 'columns_data') );
        add_action( 'admin_enqueue_scripts', array($this, 'admin_enqueue_scripts') );
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_styles') );
        add_action( 'widgets_init', array($this, 'register_widget') );
        add_action( 'wp_dashboard_setup', array($this, 'add_dashboard_widget') );

    }

    public function add_dashboard_widget()
    {
            if (user_can(get_current_user_id(), 'manage_options')) {
                wp_add_dashboard_widget('sponsors_dashboard_widget', __('Sponsors - Top 10', 'sponsors'), array($this, 'dashboard_widget_render'));
            }
    }

    public function register_widget()
    {
        require_once (__DIR__.DIRECTORY_SEPARATOR."sponsors-widget.php");
        $partnerWidget = new SponsorsWidget($this->meta_count, $this->target);
        register_widget( $partnerWidget );
    }

    public function init()
    {
        $this->create_custom_post_type();

    }

    public function enqueue_styles()
    {
        wp_register_style( $this->slug, plugins_url( 'style.css', __FILE__ ), false );
        wp_enqueue_style( $this->slug );
    }

    public function admin_enqueue_scripts()
    {
        wp_register_style( $this->slug.'admin', plugins_url( 'admin-style.css', __FILE__ ), false );
        wp_enqueue_style( $this->slug.'admin' );
    }

    public function add_meta_box()
    {
        add_meta_box( 'sponsors_url_information', __( 'URL Informations', 'sponsors' ), [$this, 'meta_box'], 'sponsors', 'normal', 'high' );
    }

    public function meta_box()
    {
        global $post;
        //TODO : upgrade code !!!
        printf( '<input type="hidden" name="%s_nonce" value="%s" />', $this->redirect_to, esc_attr( wp_create_nonce( plugin_basename( __FILE__ ) ) ) );
        printf( '<p><label for="%s">%s:</label></p>', $this->redirect_to, esc_html__( 'Redirect URI', 'sponsors' ) );
        printf( '<p><input class="regular-text" style="width:100%%;max-width:100%%" required="required" type="text" name="%s" id="%s" value="%s" /></p>', $this->redirect_to, $this->redirect_to, esc_attr( get_post_meta( $post->ID, $this->redirect_to, true ) ) );
        printf( '<p><label for="%s">%s</label></p>', $this->target, esc_html__( 'Choose the target frame for your link.') );
        printf('<p><select name="_sponsors_target">');
        printf('<option value="_self" %s>%s</option>', selected( esc_attr( get_post_meta( $post->ID, $this->target, true )), '_self',false), __('Same window', 'sponsors') );
        printf('<option value="_new" %s>%s</option>', selected( esc_attr( get_post_meta( $post->ID, $this->target, true )), '_new',false), __('New window') );
        printf('</select></p>');
    }

    public function meta_box_save($post_id, $post)
    {
        if ( ! isset( $_POST[$this->redirect_to.'_nonce'] ) || ! wp_verify_nonce( $_POST[$this->redirect_to.'_nonce'], plugin_basename( __FILE__ ) ) ) {
            return;
        }
        // Don't try to save the data under autosave, ajax, or future post.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        };
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        };
        if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
            return;
        };
        if ( ! current_user_can( 'edit_posts' ) || $this->slug !== $post->post_type ) {
            return;
        }
        $redirect_value = $_POST[$this->redirect_to] ?? '';
        $target_value   = $_POST[$this->target] ?? '';
        $count          = intval(get_post_meta($post->ID, $this->meta_count, true )) ?? 0;
        if ( $redirect_value ) {
            update_post_meta( $post->ID, $this->redirect_to, $redirect_value );
            update_post_meta( $post->ID, $this->meta_count, $count );
        }
        else {
            delete_post_meta( $post->ID, $this->redirect_to );
            delete_post_meta( $post->ID, $this->meta_count );
        }
        if ( $target_value ) {
            update_post_meta($post->ID, $this->target, $target_value);
        }
        else {
            delete_post_meta( $post->ID, $this->target );
        }
    }

    private function create_custom_post_type()
    {
        $labels = array(
            'name'                  => __('Sponsors', 'sponsors'),
            'singular_name'         => __('Sponsor', 'sponsors'),
            'menu_name'             => __('Sponsors', 'sponsors'),
            'name_admin_bar'        => __('Sponsors', 'sponsors'),
            'archives'              => __('archives', 'sponsors'),
            'attributes'            => __('attributes', 'sponsors'),
            'parent_item_colon'     => __('parent_item_colon', 'sponsors'),
            'all_items'             => __('All sponsors', 'sponsors'),
            'add_new_item'          => __('Add new sponsors', 'sponsors'),
            'edit_item'             => __('edit_item', 'sponsors'),
            'update_item'           => __('update_item', 'sponsors'),
            'view_item'             => __('view_item', 'sponsors'),
            'view_items'            => __('View sponsors page', 'sponsors'),
            'search_items'          => __('Search sponsors', 'sponsors'),
            'not_found'             => __('no sponsors.', 'sponsors'),
            'not_found_in_trash'    => __('no sponsors in trash.', 'sponsors'),
            'featured_image'        => __('Logo', 'sponsors'),
            'set_featured_image'    => __('Add logo', 'sponsors'),
            'remove_featured_image' => __('Remove logo', 'sponsors'),
            'use_featured_image'    => __('Use logo', 'sponsors'),
            'insert_into_item'      => __('insert_into_item', 'sponsors'),
            'uploaded_to_this_item' => __('uploaded_to_this_item', 'sponsors'),
            'items_list'            => __('items_list', 'sponsors'),
            'items_list_navigation' => __('items_list_navigation', 'sponsors'),
            'filter_items_list'     => __('filter_items_list', 'sponsors'),
        );
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'query_var'             => true,
            'capability_type'       => 'page',
            'hierarchical'          => false,
            'menu_position'         => $this->position,
            'has_archive'           => false,
            'menu_icon'             => $this->icon,
            'supports'              => $this->support,
            'show_in_rest'          => true,
            'exclude_from_search'   => true,
            'taxonomies'            => [],
            'rewrite'               => ['slug' => $this->rewrite_slug_default, 'with_front' => false]
        );
        register_post_type('sponsors', $args);
    }

    public function template_redirect()
    {
        if ( ! is_singular( $this->slug ) ) return;
        global $wp_query;
        $counter = $this->meta_count;
        $count = isset( $wp_query->post->$counter ) ? (int) $wp_query->post->$counter : 0;
        $redirect = isset( $wp_query->post->ID ) ? get_post_meta( $wp_query->post->ID, $this->redirect_to, true ) : '';
        $redirect = apply_filters( 'sponsors_redirect_url', $redirect, $count );
        do_action( 'sponsors_redirect_url', $redirect, $count );
        if (empty( $redirect )) {
            wp_safe_redirect(home_url() . '?redirect=error', 302);
        }
        else {
            update_post_meta($wp_query->post->ID, $this->meta_count, $count + 1);
            wp_redirect(esc_url_raw($redirect), 301);
        }
    }

    public function add_thumbnails_column($columns)
    {
        if ( get_current_screen()->post_type === $this->slug ) {
            return array_merge($columns, array('thumbnail' => esc_html__('Thumbnail')));
        }
        else {
            return $columns;
        }
    }

    public function columns_data($column)
    {
        global $post;
        $allowed_tags = array(
            'a' => array(
            'href' => array(),
            'rel'  => array(),
            'target' => array(),
            ),
        );
        $url        = get_post_meta( $post->ID, $this->redirect_to, true );
        $count      = get_post_meta( $post->ID, $this->meta_count, true );
        $target     = get_post_meta( $post->ID, $this->target, true );

        switch ($column) {
            case "url" :
                echo wp_kses( make_clickable( esc_url( $url ? $url : '' ) ), $allowed_tags );
                break;
            case "permalink" :
                echo wp_kses( make_clickable( get_permalink() ), $allowed_tags );
                break;
            case "clicks" :
                echo esc_html( $count ? $count : 0 );
                break;
            case "thumbnail" :
                if ( get_current_screen()->post_type === $this->slug ){
                    echo '<div class="thumbnail">';
                    the_post_thumbnail($this->size, ['class' => 'img-fluid wp-post-image', 'title' => __('Thumbnail')] );
                    echo '</div>';
                }
                break;
            case "target" :
                if ( $target ==='_new' )
                    echo '<span title="'.$target.'"><img src="'. plugin_dir_url( __FILE__ ).'media/external.svg" width="24" height="24"></span>';
                else
                    echo '<span title="'.$target.'"><img src="'. plugin_dir_url( __FILE__ ).'media/internal.svg" width="24" height="24"></span>';
                break;

            case "order":
                echo $post->menu_order;
                break;
        }
    }

    public function columns_order($query)
    {
        if ( ! is_admin() ) {
            return;
        }
        switch ( $query->get( 'orderby' ) ):
            case 'clicks':
                $query->set( 'orderby', 'meta_value_num');
                $query->set( 'meta_key', $this->meta_count);
                break;
            case 'order':
                $query->set( 'orderby', 'menu_order');
                break;
        endswitch;
    }

    public function columns_filter($columns): array
    {
        return array_merge( $columns, array(
            'url'       => __( 'Redirect to', 'sponsors' ),
            'permalink' => __( 'Permalinks'),
            'order'     => __( 'Order'),
            'target'    => __( 'Target', 'sponsors' ),
            'clicks'    => __( 'Clicks', 'sponsors' ),
        ));
    }

    public function column_sortable(): array
    {
        $columns['title']  = 'title';
        $columns['clicks'] = 'clicks';
        $columns['order']  = 'order';
        $columns['other']  = 'other_column';
        return $columns;
    }

    public function dashboard_widget_render()
    {

        $posts = get_posts(
            [
                'post_type'         => $this->slug,
                'post_status'       => 'publish',
                'fields'            => 'ids',
                'meta_key'          => $this->meta_count,
                'orderby'           => 'meta_value_num',
                'order'             => 'DESC',
                'posts_per_page'    => 10,
            ]
        );
        if ( empty( $posts ) ) {
            echo '<p>' . __( 'There are no stats available yet!', 'sponsors' ) . '</p>';
            return;
        }
        else {
            echo '<table class="wp-list-table widefat striped">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>'. __('Sponsors', 'sponsors') .'</th>';
            echo '<th>'. __('Clicks', 'sponsors') .'</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            foreach ( $posts as $post_id ) :
                $link = get_post_meta( $post_id, $this->redirect_to, true );
                $counter = absint( get_post_meta( $post_id, $this->meta_count, true ) );
                echo '<tr>';
                echo '<td>'. strtr('<a target="_new" href="{link}">{partners_title}</a>', ['{link}' => $link, '{partners_title}' => get_the_title($post_id)]).'</td>';
                echo '<td>'.$counter.'</td>';
                echo '</tr>';
            endforeach;
            echo '</tbody>';
            echo '</table>';
        }

    }

}

$sponsors = new SPONSORS;
