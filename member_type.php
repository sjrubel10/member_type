<?php
/*
Plugin Name: Member type
Plugin URI:
Description: Checks the health of your WordPress install
Version: 1.0.0
Author: sjrubel10
Author URI:
Text Domain: member_type
Domain Path: /languages
*/

class MemberTypePlugin {
    public function __construct() {
        define( 'MEMBER_LINK', plugin_dir_url(__FILE__ ) );
        define( 'MEMBER_ASSETS_LINK', MEMBER_LINK . 'Assets/' );

        add_action( 'init', array($this, 'register_styles' ) );
        add_action( 'init', array($this, 'register_custom_post_type' ) );
        add_action( 'init', array($this, 'register_custom_taxonomy'));
        add_shortcode('team_members', array( $this, 'team_members_shortcode' ) );

       // [team_members member_to_show='5' image_position='top' see_all_button=1 /]
    }

    public function register_styles() {
        wp_enqueue_style('display_members', MEMBER_ASSETS_LINK.'css/member.css', array(), '1.0', 'all' );
    }

    public function register_custom_post_type() {
        $labels = array(
            'name'               => __( 'Team Members', 'member_type' ),
            'singular_name'      => __( 'Team Member', 'member_type' ),
            'menu_name'          => __( 'Team Members', 'member_type' ),
            'add_new'            => __( 'Add New', 'member_type' ),
            'add_new_item'       => __( 'Add New Team Member', 'member_type' ),
            'edit_item'          => __( 'Edit Team Member', 'member_type' ),
            'new_item'           => __( 'New Team Member', 'member_type' ),
            'view_item'          => __( 'View Team Member', 'member_type' ),
            'search_items'       => __( 'Search Team Members', 'member_type' ),
            'not_found'          => __( 'No team members found', 'member_type' ),
            'not_found_in_trash' => __( 'No team members found in Trash', 'member_type' ),
            'parent_item_colon'  => __( 'Parent Team Member:', 'member_type' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array( 'slug' => 'team-member' ),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => null,
            'supports'            => array( 'title', 'editor', 'thumbnail' ),
        );

        register_post_type('team_member', $args );
    }

    public function register_custom_taxonomy() {
        $labels = array(
            'name'              => _x( 'Member Types', 'taxonomy general name', 'member_type' ),
            'singular_name'     => _x( 'Member Type', 'taxonomy singular name', 'member_type' ),
            'search_items'      => __( 'Search Member Types', 'member_type' ),
            'all_items'         => __( 'All Member Types', 'member_type' ),
            'parent_item'       => __( 'Parent Member Type', 'member_type' ),
            'parent_item_colon' => __( 'Parent Member Type:', 'member_type' ),
            'edit_item'         => __( 'Edit Member Type', 'member_type' ),
            'update_item'       => __( 'Update Member Type', 'member_type' ),
            'add_new_item'      => __( 'Add New Member Type', 'member_type' ),
            'new_item_name'     => __( 'New Member Type Name', 'member_type' ),
            'menu_name'         => __( 'Member Types', 'member_type' ),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'member-type' ),
        );

        register_taxonomy('member_type', array( 'team_member' ), $args );
    }

    public function team_members_shortcode( $shortcode_params ) {

        if( is_array( $shortcode_params ) ){
            $member_to_show = isset( $shortcode_params['member_to_show'] ) ? sanitize_text_field($shortcode_params['member_to_show'] ) : 5;
            $image_position = isset( $shortcode_params['image_position'] ) ? sanitize_text_field( $shortcode_params['image_position'] ) : 'top';
            $see_all_button =  isset( $shortcode_params['see_all_button'] ) ?sanitize_text_field( $shortcode_params['see_all_button'] ) : 1;
        }else{
            $member_to_show = 5;
            $image_position = 'top';
            $see_all_button = 1;
        }

        $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
        $posts_per_page =  $member_to_show; // 5 posts per page for first page

        $query_args = array(
            'post_type' => 'team_member',
            'posts_per_page' => $posts_per_page,
            'paged' => $paged
        );

        $team_members_query = new WP_Query( $query_args );

        $output = '';
        if ($team_members_query->have_posts()) {
            $output .= '<div class="MT-team-members-holder"><div class="MT-team-members">';

            while ( $team_members_query->have_posts() ) {
                $team_members_query->the_post();
                $output .= '<div class="MT-team-member">';
                if ( $image_position === 'top' ) {
                    $output .= '<a href="' . esc_url(get_permalink() ) . '"><div class="MT-member-image">' . get_the_post_thumbnail() . '</div></a>';
                }
                $output .= '<div class="MT-member-info">';
                $output .= '<a href="' . esc_url( get_permalink() ) . '"><h2 class="MT-member-name">' . get_the_title() . '</h2></a>';

                // Get taxonomy terms
                $taxonomy_terms = get_the_terms( get_the_ID(), 'member_type' );
                if ($taxonomy_terms && !is_wp_error( $taxonomy_terms ) ) {
                    $output .= '<div class="MT-taxonomy-terms">';
                    foreach ( $taxonomy_terms as $term ) {
                        $output .= '<span class="MT-taxonomy-term">' . $term->name . '</span>';
                    }
                    $output .= '</div>';
                }

                $output .= '</div>';
                if ( $image_position === 'bottom' ) {
                    $output .= '<a href="' . esc_url(get_permalink() ) . '"><div class="MT-member-image">' . get_the_post_thumbnail() . '</div></a>';
                }
                $output .= '</div>';
            }

            $output .= '</div></div>';

            // Pagination
            $big = 999999999; // need an unlikely integer
            $output .= paginate_links(array(
                'base'    => str_replace( $big, '%#%', esc_url(get_pagenum_link( $big ) ) ),
                'format'  => '?paged=%#%',
                'current' => max(1, $paged ),
                'total'   => $team_members_query->max_num_pages
            ));

            // See All Button
            if ( $see_all_button ) {
                $output .= '<div class="MT-see_all_btn"><a href="' . get_post_type_archive_link('team_member') . '" class="see-all-button">See All Team Members</a></div>';
            }

            wp_reset_postdata();
        } else {
            $output .= 'No team members found';
        }

        return $output;
    }
}

new MemberTypePlugin();

