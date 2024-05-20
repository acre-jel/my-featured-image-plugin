<?php
/*
Plugin Name: My Featured Image Plugin
Description: Generate featured images with custom text overlays from media library.
Version: 1.2
Author: Your Name
*/

// Enqueue scripts and styles
function my_featured_image_plugin_enqueue_scripts($hook) {
    if ($hook != 'toplevel_page_featured-image-generator' && $hook != 'post.php' && $hook != 'post-new.php') {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_script('my-featured-image-plugin-script', plugins_url('/js/featured-image.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('my-featured-image-plugin-script', 'myPluginAjax', array('ajax_url' => admin_url('admin-ajax.php')));
}

add_action('admin_enqueue_scripts', 'my_featured_image_plugin_enqueue_scripts');

// Add admin page
function my_featured_image_plugin_add_admin_page() {
    add_menu_page('Featured Image Generator', 'Featured Image', 'manage_options', 'featured-image-generator', 'my_featured_image_plugin_create_page', 'dashicons-format-image', 110);
}

add_action('admin_menu', 'my_featured_image_plugin_add_admin_page');

// Create admin page
function my_featured_image_plugin_create_page() {
    ?>
    <div class="wrap">
        <h1>Generate Featured Image</h1>
        <button id="select-image" class="button button-primary">Select Background Image</button>
        <br><br>
        <textarea id="title-text" placeholder="Enter title text" style="width: 100%; padding: 10px; font-size: 16px; height: 100px;"></textarea>
        <br><br>
        <button id="generate-featured-image" class="button button-primary">Generate Image</button>
        <br><br>
        <img id="generated-image" src="" alt="Generated Image" style="max-width: 100%; height: auto; display: none;">
        <br><br>
        <button id="save-image" class="button button-primary" style="display: none;">Save to Media Library</button>
    </div>
    <?php
}

// Save image via AJAX
function my_featured_image_plugin_save_image() {
    if (!current_user_can('upload_files')) {
        wp_send_json_error('You do not have permission to upload files.');
        return;
    }

    $img = $_POST['image'];
    $img = str_replace('data:image/png;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);

    $upload_dir = wp_upload_dir();
    $upload_path = $upload_dir['path'] . '/' . uniqid() . '.png';
    $file_saved = file_put_contents($upload_path, $data);

    if ($file_saved) {
        $filetype = wp_check_filetype(basename($upload_path), null);
        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title' => sanitize_file_name(basename($upload_path)),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment($attachment, $upload_path);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload_path);
        wp_update_attachment_metadata($attach_id, $attach_data);

        if (isset($_POST['post_id'])) {
            set_post_thumbnail($_POST['post_id'], $attach_id);
        }

        wp_send_json_success('Image saved successfully and set as featured image.');
    } else {
        wp_send_json_error('Image saving failed.');
    }
}

add_action('wp_ajax_save_image', 'my_featured_image_plugin_save_image');

// Add meta box to post editor
function my_featured_image_plugin_add_meta_box() {
    add_meta_box(
        'my_featured_image_generator',
        'Generate Featured Image',
        'my_featured_image_plugin_meta_box_callback',
        'post',
        'side'
    );
}

add_action('add_meta_boxes', 'my_featured_image_plugin_add_meta_box');

function my_featured_image_plugin_meta_box_callback($post) {
    ?>
    <button id="select-image" class="button button-primary">Select Background Image</button>
    <br><br>
    <textarea id="title-text" placeholder="Enter title text" style="width: 100%; padding: 10px; font-size: 16px; height: 100px;"><?php echo esc_html($post->post_title); ?></textarea>
    <br><br>
    <button id="generate-featured-image" class="button button-primary">Generate Image</button>
    <br><br>
    <img id="generated-image" src="" alt="Generated Image" style="max-width: 100%; height: auto; display: none;">
    <br><br>
    <button id="save-image" class="button button-primary" style="display: none;" data-post-id="<?php echo $post->ID; ?>">Save to Media Library</button>
    <?php
}
?>
