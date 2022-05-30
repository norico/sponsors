<?php

namespace app;

class SponsorsWidget extends \WP_Widget
{
    public function __construct($meta_count_key, $target) {

        $this->meta_count = $meta_count_key;
        $this->target     = $target;

        parent::__construct(
            'sponsors_widget',
            esc_html__( 'Sponsors', 'sponsors' ),
            array( 'description' => esc_html__( 'Sponsors widget', 'sponsors' ) )
        );
    }

    private array $widget_fields = array(
        ['label' => 'Title','id' => 'title','type' => 'text',],
        ['label' => 'Order','default' => 'ASC','id' => 'order','type' => 'select','options' => ['ASC','DESC'] ],
        ['label' => 'Thumbnail','default' => 'true','id' => 'Thumbnail','type' => 'checkbox']
    );

    public function form( $instance ) {
        $this->field_generator( $instance );
    }

    public function field_generator( $instance ) {
        $output = '';
        foreach ( $this->widget_fields as $widget_field ) {
            $default = '';
            if ( isset($widget_field['default']) ) {
                $default = $widget_field['default'];
            }
            $widget_value = ! empty( $instance[$widget_field['id']] ) ? $instance[$widget_field['id']] : esc_html__( $default, 'partners' );


            $field_id    = esc_attr__( $this->get_field_id( $widget_field['id'] ) );
            $field_name  =  esc_attr( $this->get_field_name( $widget_field['id'] ) );
            $field_label = $widget_field['label'];

            switch ( $widget_field['type'] ) {
                case 'checkbox':
                    $output .= '<p>';
                    $output .= '<input class="checkbox" type="checkbox" '.checked( $widget_value, true, false ).' id="'.$field_id.'" name="'.$field_name.'" value="1">';
                    $output .= '<label for="'.$field_id.'">'.esc_attr__( $field_label ).'</label>';
                    $output .= '</p>';
                    break;

                case 'select':
                    $output .= '<p>';
                    $output .= '<label for="'.$field_id.'">'.$field_label.':</label> ';
                    $output .= '<select id="'.$field_id.'" name="'.$field_name.'">';
                    foreach ($widget_field['options'] as $option) {
                        if ($widget_value == $option) {
                            $output .= '<option value="'.$option.'" selected>'.__($option, 'partners').'</option>';
                        } else {
                            $output .= '<option value="'.$option.'">'.__($option, 'partners').'</option>';
                        }
                    }
                    $output .= '</select>';
                    $output .= '</p>';
                    break;

                default:
                    $output .= '<p>';
                    $output .= '<label for="'.$field_id.'">'.$field_label.':</label> ';
                    $output .= '<input class="widefat" id="'.$field_id.'" name="'.$field_name.'" type="'.$widget_field['type'].'" value="'.esc_attr( $widget_value ).'">';
                    $output .= '</p>';
            }
        }
        echo $output;
    }
    public function update( $new_instance, $old_instance ): array
    {
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        foreach ( $this->widget_fields as $widget_field ) {
            switch ( $widget_field['type'] ) {
                default:
                    $instance[$widget_field['id']] = ( ! empty( $new_instance[$widget_field['id']] ) ) ? strip_tags( $new_instance[$widget_field['id']] ) : '';
            }
        }
        return $instance;
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        $title = $instance['title'] ? $instance['title'] : __('Sponsors', 'sponsors');
        if ( ! empty( $title ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $title ) . $args['after_title'];
        }
        $sponsors_query_args = array(
            'post_type' => 'sponsors',
            'orderby'   => 'menu_order',
            'order'     => $instance['order']
        );
        query_posts( $sponsors_query_args );

        $class = !$instance["Thumbnail"] ? 'class="list"' : 'class="thumbnail"';
        if ( have_posts() ) :
            global $post;
            echo '<div id="sponsors" '.$class.'>';
            while ( have_posts() ) : the_post();
                require(__DIR__.DIRECTORY_SEPARATOR."template.php");
            endwhile;
            echo '</div>';
        endif;
        echo $args['after_widget'];
        wp_reset_query();
    }
}
