<?php

/**
 * @package     MultipleAuthorBoxes
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.1.0
 */

namespace MultipleAuthorBoxes;

use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Author_Editor;
use MA_Author_Boxes;

/**
 * Author boxes Ajax
 *
 * @package MultipleAuthorBoxes\Classes
 *
 */
class AuthorBoxesAjax
{

    /**
     * Handle a request to generate author boxes preview.
     */
    public static function handle_author_boxes_editor_get_preview()
    {

        $response['status']  = 'success';
        $response['content'] = esc_html__('An error occured.', 'publishpress-authors');

        //do not process request if nonce validation failed
        if (empty($_POST['nonce']) 
            || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'author-boxes-request-nonce')
        ) {
            $response['status']  = 'error';
            $response['content'] = esc_html__(
                'Security error. Kindly reload this page and try again', 
                'publishpress-authors'
            );
        } else {
            $post_data = $_POST['editor_data'];// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $editor_data = [];
            foreach ($post_data as $key => $value) {
                if (substr($key, -13) === '_display_icon') {
                    $editor_data[$key] = htmlspecialchars(stripslashes_deep($value));
                } else {
                    $editor_data[$key] = sanitize_text_field($value);
                }
            }

            $author_term_id = !empty($_POST['author_term_id']) ? (int) $_POST['author_term_id'] : 0;
            $post_id = !empty($_POST['post_id']) ? (int) $_POST['post_id'] : 0;

            $preview_args            = [];
            $preview_args['authors'] = [Author::get_by_term_id($author_term_id)];
            $preview_args['post_id'] = $post_id;

            $fields = apply_filters('multiple_authors_author_boxes_fields', MA_Author_Boxes::get_fields(get_post($post_id)), get_post($post_id));
            foreach ($fields as $key => $args) {
                $args['key']   = $key;
                $args['value'] = isset($editor_data[$key]) ? $editor_data[$key] : '';
                $preview_args[$key] = $args;
            }
            $response['content'] = MA_Author_Boxes::get_rendered_author_boxes_editor_preview($preview_args);
        }

        wp_send_json($response);
        exit;
    }

    /**
     * Handle a request to generate author boxes template.
     */
    public static function handle_author_boxes_editor_get_template()
    {

        $response['status']  = 'success';
        $response['content'] = esc_html__('An error occured.', 'publishpress-authors');

        //do not process request if nonce validation failed
        if (empty($_POST['nonce']) 
            || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'author-boxes-request-nonce')
        ) {
            $response['status']  = 'error';
            $response['content'] = esc_html__(
                'Security error. Kindly reload this page and try again', 
                'publishpress-authors'
            );
        } else {
            ob_start();

            $profile_fields   = Author_Editor::get_fields(false);
            $profile_fields   = apply_filters('multiple_authors_author_fields', $profile_fields, false);

            $editor_data = !empty($_POST['editor_data']) ? array_map('sanitize_text_field', $_POST['editor_data']) : [];
            $fields = apply_filters('multiple_authors_author_boxes_fields', MA_Author_Boxes::get_fields(false), false);
            $args = [];
            $args['post_id'] = '</?php echo $post_id; ?>';
            foreach ($fields as $key => $field_args) {
                $field_args['key']   = $key;
                $field_args['value'] = isset($editor_data[$key]) ? $editor_data[$key] : '';
                $args[$key] = $field_args;
            }

            $custom_styles = '';
            ?>
</?php
/**
 * Custom Author Boxes template
 * 
 * This file should be placed in /publishpress-authors/author-boxes/ 
 * Inside your theme and it will automatically be available for 
 * selection in settings layouts and this file slug can be use as layout 
 * parameter in shortcode.
 * 
 * The layout name will be this file name.
 * 
 * $ppma_template_authors is a global variable and an array of authors.
 * $ppma_template_authors_post is a global variable of the author post.
 * This sometimes may be different from global $post as user can  get authors 
 * for specific post.
 */
global $ppma_template_authors, $ppma_template_authors_post, $post;

$authors = $ppma_template_authors;
$post_id = isset($ppma_template_authors_post->ID) ? $ppma_template_authors_post->ID : $post->ID
?>
<div class="pp-multiple-authors-boxes-wrapper pp-multiple-authors-wrapper <?php echo esc_attr($args['box_tab_custom_wrapper_class']['value']); ?> box-post-id-</?php echo esc_attr($post_id); ?>"">
<?php if ($args['show_title']['value']) : ?>
    <<?php echo esc_html($args['title_html_tag']['value']); ?> class="widget-title box-header-title">
        </?php if (count($authors) > 1) : ?>
            <?php echo esc_html($args['title_text_plural']['value']); ?><?php echo "\n"; ?>
        </?php else : ?>
            <?php echo esc_html($args['title_text']['value']); ?><?php echo "\n"; ?>
        </?php endif; ?>
    </<?php echo esc_html($args['title_html_tag']['value']); ?>>
<?php endif; ?>

    <ul class="pp-multiple-authors-boxes-ul">
        </?php foreach ($authors as $index => $author) : ?>

            </?php if ($author && is_object($author) && isset($author->term_id)) : ?>

<?php if ($args['author_recent_posts_show']['value']) : ?>

                </?php $author_recent_posts = multiple_authors_get_author_recent_posts($author, true, <?php echo esc_html($args['author_recent_posts_limit']['value']); ?>, '<?php echo esc_html($args['author_recent_posts_orderby']['value']); ?>', '<?php echo esc_html($args['author_recent_posts_order']['value']); ?>'); ?>

<?php endif; ?>
                <li class="pp-multiple-authors-boxes-li author_index_</?php echo esc_attr($index); ?> author_</?php echo esc_attr($author->slug); ?>">

<?php if ($args['avatar_show']['value']) : ?>
                    <div class="pp-author-boxes-avatar">
                        </?php if ($author->get_avatar) : ?>
                            </?php echo $author->get_avatar('<?php echo esc_html($args['avatar_size']['value']); ?>'); ?>
                        </?php else : ?>
                            </?php echo get_avatar($author->user_email, '<?php echo esc_html($args['avatar_size']['value']); ?>'); ?>
                        </?php endif; ?>
                    </div>
<?php else : 
$custom_styles = '.pp-multiple-authors-layout-boxed ul li > div:nth-child(1) {flex: 1 !important;}';
?>
<?php endif; ?>

                    <div>
                        <<?php echo esc_html($args['name_html_tag']['value']); ?> class="pp-author-boxes-name multiple-authors-name">
                            <a href="</?php echo esc_url($author->link); ?>" rel="author" title="</?php echo esc_attr($author->display_name); ?>" class="author url fn">
                                </?php echo esc_html($author->display_name); ?>
                            </a>
                        </<?php echo esc_html($args['name_html_tag']['value']); ?>>

<?php if ($args['author_bio_show']['value']) : ?>
                        <<?php echo esc_html($args['author_bio_html_tag']['value']); ?> class="pp-author-boxes-description multiple-authors-description">
                            </?php echo $author->get_description(<?php echo esc_html($args['author_bio_limit']['value']); ?>); ?>
                        </<?php echo esc_html($args['author_bio_html_tag']['value']); ?>>
<?php endif; ?>

<?php if ($args['meta_show']['value']) : ?>
                        <<?php echo esc_html($args['meta_html_tag']['value']); ?> class="pp-author-boxes-meta multiple-authors-links">
<?php if ($args['meta_view_all_show']['value']) : ?>
                            <a href="</?php echo esc_url($author->link); ?>" title="</?php echo esc_attr__('View all posts', 'publishpress-authors'); ?>">
                                    <span></?php echo esc_html__('View all posts', 'publishpress-authors'); ?></span>
                            </a>
<?php endif; ?>
<?php if ($args['meta_email_show']['value']) : ?>
                            </?php if ($author->user_email) : ?>
                                <a href="</?php echo esc_url('mailto:'.$author->user_email); ?>" target="_blank">
                                    <span class="dashicons dashicons-email-alt"></span>
                                </a>
                            </?php endif; ?>
<?php endif; ?>
<?php if ($args['meta_site_link_show']['value']) : ?>
                            </?php if ($author->user_email) : ?>
                                <a href="</?php echo esc_url($author->user_url); ?>" target="_blank">
                                    <span class="dashicons dashicons-admin-links"></span>
                                </a>
                            </?php endif; ?>
<?php endif; ?>
                        </<?php echo esc_html($args['meta_html_tag']['value']); ?>>
<?php endif; ?>

                     
                        <div class="pp-author-boxes-profile-fields">
<?php foreach ($profile_fields as $key => $data) {
if (!in_array($key, MA_Author_Boxes::AUTHOR_BOXES_EXCLUDED_FIELDS)) {
                                $profile_show_field = $args['profile_fields_show_' . $key]['value'] ? true : false;

                                $profile_html_tag  = !empty($args['profile_fields_' . $key . '_html_tag']['value'])
                                    ? $args['profile_fields_' . $key . '_html_tag']['value'] : 'span';

                                $profile_display  = !empty($args['profile_fields_' . $key . '_display']['value'])
                                    ? $args['profile_fields_' . $key . '_display']['value'] : 'icon_prefix_value_suffix';

                                $profile_value_prefix   = $args['profile_fields_' . $key . '_value_prefix']['value'];
                                $profile_display_prefix = $args['profile_fields_' . $key . '_display_prefix']['value'];
                                $profile_display_suffix = $args['profile_fields_' . $key . '_display_suffix']['value'];
                                $profile_display_icon  = $args['profile_fields_' . $key . '_display_icon']['value'];
                                
if ($profile_show_field) : ?>
                            <<?php echo esc_html($profile_html_tag); ?> 
                                class="ppma-author-<?php echo esc_attr($key); ?>-profile-data"
<?php if ($profile_html_tag === 'a') : ?>
                                href="</?php echo $author->$key; ?>"
<?php endif; ?>
                                >
<?php if ($profile_display === 'icon_prefix_value_suffix') {
if (!empty($profile_display_icon)) {
                                echo html_entity_decode($profile_display_icon) . ' ';// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
if (!empty($profile_display_prefix)) {
                                echo  esc_html($profile_display_prefix) . ' ';
} ?>
                                </?php echo esc_html($author-><?php echo esc_html($key); ?>) . ' '; ?>
<?php if (!empty($profile_display_suffix)) {
                                echo esc_html($profile_display_suffix);
}
} elseif ($profile_display === 'value') { ?>
                                </?php echo esc_html($author-><?php echo esc_html($key); ?>) . ' '; ?>
<?php } elseif ($profile_display === 'prefix') {
                                echo esc_html($profile_display_prefix);
} elseif ($profile_display === 'suffix') {
                                echo esc_html($profile_display_suffix);
} elseif ($profile_display === 'icon') {
                                echo html_entity_decode($profile_display_icon) . ' ';// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} elseif ($profile_display === 'prefix_value_suffix') {
if (!empty($profile_display_prefix)) {
                                echo esc_html($profile_display_prefix) . ' ';
} ?>
                                </?php echo esc_html($author-><?php echo esc_html($key); ?>) . ' '; ?>
<?php if (!empty($profile_display_suffix)) {
                                echo esc_html($profile_display_suffix);
}
}
?>
                            </<?php echo esc_html($profile_html_tag); ?>>
<?php endif; } } ?>
                        </div>
<?php if ($args['author_recent_posts_show']['value']) : ?>
                        <div class="pp-author-boxes-recent-posts">

                            </?php if (!empty($author_recent_posts)) : ?>
                                <div class="pp-author-boxes-recent-posts-title">
                                    </?php echo esc_html__('Recent Posts', 'publishpress-authors'); ?>
                                </div>
                                <div class="pp-author-boxes-recent-posts-items">
                                    </?php foreach($author_recent_posts as $recent_post_id) : ?>
                                        <<?php echo esc_html($args['author_recent_posts_html_tag']['value']); ?> class="pp-author-boxes-recent-posts-item">
                                            <span class="dashicons dashicons-media-text"></span>
                                            <a href="</?php echo esc_url(get_the_permalink($recent_post_id)); ?>" title="</?php echo esc_attr(get_the_title($recent_post_id)); ?>">
                                                </?php echo esc_html(html_entity_decode(get_the_title($recent_post_id))); ?>
                                            </a>
                                        </<?php echo esc_html($args['author_recent_posts_html_tag']['value']); ?>>
                                    </?php endforeach; ?>
                                </div>
                            </?php else : ?>
                                <div class="pp-author-boxes-recent-posts-empty">
                                    </?php echo esc_html__('No Recent Posts by this Author', 'publishpress-authors'); ?>
                                </div>
                            </?php endif; ?>

                        </div>
<?php endif; ?>
                     </div>                 
                </li>
            </?php endif; ?>

        </?php endforeach; ?>
    </ul>
</div>

<?php 
$generated_styles = '';
if (!empty($custom_styles)) {
    $generated_styles .= $custom_styles . "\n";
}
if (!empty($new_style_1 = AuthorBoxesStyles::getTitleFieldStyles($args, ''))) {
    $generated_styles .= $new_style_1 . "\n";
}
if (!empty($new_style_2 = AuthorBoxesStyles::getAvatarFieldStyles($args, ''))) {
    $generated_styles .= $new_style_2 . "\n";
}
if (!empty($new_style_3 = AuthorBoxesStyles::getNameFieldStyles($args, ''))) {
    $generated_styles .= $new_style_3 . "\n";
}
if (!empty($new_style_4 = AuthorBoxesStyles::getBioFieldStyles($args, ''))) {
    $generated_styles .= $new_style_4 . "\n";
}
if (!empty($new_style_5 = AuthorBoxesStyles::getMetaFieldStyles($args, ''))) {
    $generated_styles .= $new_style_5 . "\n";
}
if (!empty($new_style_6 = AuthorBoxesStyles::getRProfileFieldStyles($args, ''))) {
    $generated_styles .= $new_style_6 . "\n";
}
if (!empty($new_style_7 = AuthorBoxesStyles::getRecentPostsFieldStyles($args, ''))) {
    $generated_styles .= $new_style_7 . "\n";
}
if (!empty($new_style_8 = AuthorBoxesStyles::getBoxLayoutFieldStyles($args, ''))) {
    $generated_styles .= $new_style_8 . "\n";
}
if (!empty($new_style_9 = AuthorBoxesStyles::getCustomCssFieldStyles($args, ''))) {
    $generated_styles .= $new_style_9 . "\n";
}
?>
<style>
    <?php echo $generated_styles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</style>
            <?php 
            $response['content'] = ob_get_clean();
        }

        wp_send_json($response);
        exit;
    }
}
