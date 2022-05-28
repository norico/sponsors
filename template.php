<?php
if ( $instance )
{
    $title  = $post->post_title;
    $image  = has_post_thumbnail() ? get_the_post_thumbnail($post->ID, 'medium', array('alt'=> $post->post_title, 'title'=> $post->post_title)) : '<img src="'.plugin_dir_url(__FILE__).'placeholder.jpg" width="150" height="150" alt="'.$post->post_title.'">';
    $link   = get_the_permalink($post->ID);
    $count  = get_post_meta( $post->ID, $this->meta_count, true );
    $target = get_post_meta( $post->ID, $this->target, true );

    if ( is_user_logged_in() && current_user_can( 'edit_posts' ) )
    {
        $title = $title." [".$count."]";
    }
    if( $instance["Thumbnail"] ){
        echo <<<html
        <div id="sponsor">
            <a target="$target" href="$link">$image</a>
            <p>$title</p>
        </div>
        html;
    }
    else {
        echo <<<html
        <div id="sponsor">
            <ul>
                <li><a target="$target" href="$link">$title</a></li>
            </ul>
        </div>
        html;
    }
}
