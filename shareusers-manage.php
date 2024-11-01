<?php
    global $wpdb;

    // URL to remove items
    $forbidden_keys = array('id', 'op', 'updated');
    $remove_url = $_SERVER['PHP_SELF'];
    if (strlen($_SERVER['QUERY_STRING'])) {
        $query = '';
        $params = explode('&', $_SERVER['QUERY_STRING']);
        foreach ($params as $param) {
            list($key, $val) = explode('=', $param);
            if (!in_array($key, $forbidden_keys)) {
                $query .= (strlen($query) ? '&' : '?'). $key. '='. $val;
            }
        }
        $remove_url .= $query. '&op=remove&id=';
    }
    
    switch ($_GET['op']) {
        case 'remove': $id = (int) $_GET['id'];
                       $this->removeKeyword($id);
                       break;
    }

    echo '<div class="wrap">';
    echo '<h2>'. __('ShareUsers: Keywords Management', 'shareusers'). '</h2>';
    if ($_GET['updated'] == 'true') {
        echo '<div id="message" class="updated fade"><p>';
        if (isset($this->update_res)) echo ($this->update_res ? __('Keywords updated', 'shareusers') : __('Keywords not updated', 'shareusers'));
        if (isset($this->remove_res)) echo ($this->remove_res ? __('Keywords removed', 'shareusers') : __('Keywords not removed', 'shareusers'));
        echo '</p></div>';
    }
    echo '<table cellpadding="5">';
    // Get keywords from database
    $sql = 'SELECT * FROM `'. $this->table_name. '`;';
    if ($results = $wpdb->get_results($sql)) {
        echo '<tr>';
        echo '<th width="150">'. __('Keyword', 'shareusers'). '</th>';
        echo '<th width="600">'. __('Description', 'shareusers'). '</th>';
        echo '<th width="100">'. __('Language', 'shareusers'). '</th>';
        echo '</tr>';
        foreach ($results as $result) {
            echo "<tr>\n";
            echo '<td style="border-top: 1px dashed grey;">'. $result->main. (empty($result->second) ? '' : ' ('. $result->second. ')'). '</td>';
            echo '<td style="border-top: 1px dashed grey;">'. $result->description. '</td>';
            echo '<td style="border-top: 1px dashed grey; text-align: center;">'. $result->language. '</td>';
            echo '<td style="border-top: 1px dashed grey; text-align: center;"><a href="'. $remove_url. $result->id. '" onclick="return confirm(\''. __('The keyword will be removed. Are you sure?', 'shareusers'). '\')">'. __('Remove', 'shareusers'). '</a></td>';
            echo "</tr>\n";
        }
    } else {
        echo '<tr>';
        echo '<td>'. __('No keywords found', 'shareusers'). '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo "<br />\n";
    echo '<form method="post" action="options.php">';
    wp_nonce_field('update-options');
    echo '<input type="hidden" name="action" value="update" />';
    echo '<input type="hidden" name="page_options" value="shareusers_update_now,shareusers_remove_now" />';
    echo '<input type="submit" name="shareusers_update_now" value="'. __('Update keywords', 'shareusers'). '"';
    if (count($results)) echo ' onclick="return confirm(\''. __('All the keywords will be deleted and loaded again from ShareUsers. Do you want to continue?', 'shareusers'). '\')"';
    echo ' />';
    echo '<input type="submit" name="shareusers_remove_now" value="'. __('Remove keywords', 'shareusers'). '" onclick="return confirm(\''. __('All the keywords will be deleted. Do you want to continue?', 'shareusers'). '\')" />';
    echo '</form>';
    echo '</div>';
