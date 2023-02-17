<?php

if(!defined("IN_MYBB"))
    die("Direct initialization of this file is not allowed.");

$lang->load("forum_icons");

if(isset($_POST['save_images']) && !empty($mybb->input['image']) && is_array($mybb->input['image'])) {
    if($db->table_exists("forum_icons")) {
        foreach($mybb->input['image'] as $fid => $image) {
            $image = htmlspecialchars($db->escape_string($image));
            if($db->num_rows($db->simple_select("forum_icons", "*", "fid='{$fid}'")) > 0)
                $db->update_query("forum_icons", array("image" => $image), "fid={$fid}");
            elseif(!empty($image))
                $db->insert_query("forum_icons", array("fid" => $fid, "image" => $image));
        }

        log_admin_action($lang->ficons_log);

        flash_message($lang->ficons_saved, 'success');
        admin_redirect("index.php?module=forum-icons");
    }
    else {
        flash_message($lang->ficons_not_installed, 'error');
        admin_redirect("index.php?module=forum-icons");
    }
}

$page->add_breadcrumb_item($lang->ficons_title, "index.php?module=forum-icons");
$page->extra_header .= <<<EOT
<style>
.preview {
    max-width: 200px;
    max-height: 150px;
}
</style>
<script type="text/javascript">
    $(document).ready(function() {
        $("form#images_url").on("keyup", "input.imageurl", function () {
            var content = $(this).val();
            $(this).parent("td").parent("tr").find(".preview").attr("src", content);
        });
    });
</script>
EOT;
$page->output_header($lang->ficons_title);

$form = new Form("index.php?module=forum-icons", "post", "images_url");
$form_container = new FormContainer($lang->ficons_manage);

$form_container->output_row_header($lang->ficons_forum, array("style" => "width: 50%"));
$form_container->output_row_header($lang->ficons_image, array("style" => "width: 25%", "class" => "align_center"));
$form_container->output_row_header($lang->ficons_preview, array("style" => "width: 25%", "class" => "align_center"));

build_forums_list($form_container);

$submit_options = array();

if($form_container->num_rows() == 0){
    $form_container->output_cell($lang->ficons_no_forum, array('colspan' => 3));
    $form_container->construct_row();
    $submit_options = array('disabled' => true);
}

$form_container->end();

$buttons[] = $form->generate_submit_button($lang->ficons_save, array("name" => "save_images"));
$buttons[] = $form->generate_reset_button($lang->ficons_reset);

$form->output_submit_wrapper($buttons);

$form->end();

$page->output_footer();

function build_forums_list($form_container, $pid=0, $depth=1) {
    global $mybb, $lang, $db, $sub_forums, $form;
    static $forums_by_parent;

    if(!is_array($forums_by_parent)) {
        $forum_cache = cache_forums();

        foreach($forum_cache as $forum)
            $forums_by_parent[$forum['pid']][$forum['disporder']][$forum['fid']] = $forum;
    }

    if(!is_array($forums_by_parent[$pid]))
        return;

    foreach($forums_by_parent[$pid] as $children) {
        foreach($children as $forum) {
            $image = $db->simple_select("forum_icons", "*", "fid={$forum['fid']}");
            $image = $db->fetch_array($image);
            $forum['name'] = preg_replace("#&(?!\#[0-9]+;)#si", "&amp;", $forum['name']);

            if($forum['type'] == "c" && $depth == 1) {
                $form_container->output_cell("<div style=\"padding-left: ".(40*($depth-1))."px;\"><strong>{$forum['name']}</strong></div>", array("colspan" => "3", "style" => "height:33px"));
                $form_container->construct_row();

                if($forums_by_parent[$forum['fid']])
                    build_forums_list($form_container, $forum['fid'], $depth+1);
            }
            else {
                $image['image'] = isset($image['image']) ? $image['image'] : "";

                $form_container->output_cell("<div style=\"padding-left: ".(40*($depth-1))."px;\">{$forum['name']}</div>");
                $form_container->output_cell($form->generate_text_box("image[{$forum['fid']}]", $image['image'], array("class" => "imageurl")), array("class" => "align_center"));
                $form_container->output_cell("<img class=\"preview\" src=\"{$image['image']}\" alt=\"{$lang->ficons_no_preview}\">", array("class" => "align_center"));
                $form_container->construct_row();

                if(isset($forums_by_parent[$forum['fid']]))
                    build_forums_list($form_container, $forum['fid'], $depth+1);
            }
        }
    }
}