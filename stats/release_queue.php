<?php
$relPath='../pinc/';
include_once($relPath.'base.inc');
include_once($relPath.'dpsql.inc');
include_once($relPath.'misc.inc');
include_once($relPath.'project_states.inc');
include_once($relPath.'stages.inc');
include_once($relPath.'user_is.inc');
include_once($relPath.'theme.inc');
include_once($relPath.'release_queue.inc');

require_login();

if ( $ordinary_users_can_see_queue_settings )
{
    $user_can_see_queue_settings = TRUE;
}
else
{
    // Only privileged users can see queue settings
    $user_can_see_queue_settings = user_is_a_sitemanager() || user_is_proj_facilitator();
}

$round_id = get_enumerated_param($_GET, 'round_id', null, array_keys($Round_for_round_id_), true);
$name     = @$_GET['name'];

if (is_null($round_id))
{
    $title = _("Release Queues");
    output_header($title);
    echo "<br><h2>$title</h2>";

    echo _("Each round has its own set of release queues."), "\n";
    echo _("Please select the round that you're interested in:"), "\n";
    echo "<ul>\n";
    foreach ( array_keys($Round_for_round_id_) as $round_id )
    {
        echo "<li><a href='release_queue.php?round_id=$round_id'>$round_id</a></li>\n";
    }
    echo "</ul>\n";
    return;
}

$round = get_Round_for_round_id($round_id);

if (!isset($name))
{
    $title = sprintf( _("Release Queues for Round '%s'"), $round_id);
    output_header($title);
    echo "<br><h2>$title</h2>";
    echo "<table border='1' cellspacing='0' cellpadding='2' style='border: 1px solid #111; border-collapse: collapse' width='99%'>\n";
    echo "<tr bgcolor='".$theme['color_headerbar_bg']."'>";
    echo "<td colspan='7'><center><font color='".$theme['color_headerbar_font']."'><b>".$title."</b></font></center></td></tr>\n";
    {
        echo "<tr bgcolor='".$theme['color_navbar_bg']."'>";
        echo "<th>", _("ordering"), "</th>\n";
        echo "<th>", _("enabled"), "</th>\n";
        echo "<th>", _("name"), "</th>\n";
        echo "<th>", _("current<br>length"), "</th>\n";
        if ($user_can_see_queue_settings)
        {
            echo "<th>", _("project_selector"), "</th>\n";
            echo "<th>", _("release_criterion"), "</th>\n";
            echo "<th>", _("comment"), "</th>\n";
        }
        echo "</tr>\n";
    }

    $rownum = 0;
    $listing_bgcolors = array($theme['color_listing_bg_1'], $theme['color_listing_bg_2']);

    $q_res = mysql_query("
        SELECT *
        FROM queue_defns
        WHERE round_id='$round_id'
        ORDER BY ordering
    ") or die(mysql_error());
    while ( $qd = mysql_fetch_object($q_res) )
    {
        $cooked_project_selector = cook_project_selector($qd->project_selector);
        $c_res = mysql_query("
            SELECT COUNT(*)
            FROM projects
            WHERE ($cooked_project_selector)
                AND state='{$round->project_waiting_state}'
        ");
        if ($c_res)
        {
            $current_length = mysql_result($c_res,0);
        }
        else
        {
            $current_length = '???';
            $msg = sprintf(
                _('Warning: there is a syntax error in the project selector for #%d "%s"'),
                $qd->ordering,
                $qd->name);
            echo "$msg<br>";
            // It's lazy to simply echo the warning message here,
            // in the midst of generating a table.  Presumably the
            // result is invalid HTML, since the text is not within
            // a <td> element. However, it seems that most browsers
            // render it above the table, which is what we want.
        }

        $ename = urlencode( $qd->name );
        echo "<tr bgcolor='" . $listing_bgcolors[$rownum % 2] . "'>";
        echo "<td>$qd->ordering</td>\n";
        echo "<td>$qd->enabled</td>\n";
        echo "<td><a href='release_queue.php?round_id=$round_id&amp;name=$ename'>$qd->name</a></td>\n";
        echo "<td>$current_length</td>\n";
        if ($user_can_see_queue_settings)
        {
            echo "<td>$qd->project_selector</td>\n";
            echo "<td>$qd->release_criterion</td>\n";
            echo "<td>$qd->comment</td>\n";
        }
        echo "</tr>\n";
        $rownum++;
    }
    echo "</table>\n";
    echo "<br>\n";
}
else
{
    $qd = mysql_fetch_object( mysql_query("
        SELECT *
        FROM queue_defns
        WHERE round_id='$round_id' AND name='$name'
    "));
    if (!$qd) {
        die(htmlspecialchars("No such release queue '$name' in $round_id."));
    }
    $cooked_project_selector = cook_project_selector($qd->project_selector);
    $comment = $qd->comment;

    //// TRANSLATORS: %s is the name of this release queue.
    $title = sprintf(_("\"%s\" Release Queue"), htmlspecialchars($name));
    $title = preg_replace('/(\\\\)/', "", $title); // Unescape apostrophes, etc.
    // Suppress stats since this page is very wide
    output_header($title, NO_STATSBAR);
    echo "<br><h2>$title</h2>";

    if ($user_can_see_queue_settings)
    {
        echo "<h4>", _("project_selector"), ": $qd->project_selector</h4>\n\n";
        if ( $cooked_project_selector != $qd->project_selector )
        {
            echo "($cooked_project_selector)\n\n";
        }
        echo "<h4>$comment</h4>\n";
    }

    // Add Back to to Release Queues link
    echo "<p><a href='".$code_url."/stats/release_queue.php?round_id=$round_id'>"._("Back to Release Queues")."</a></p>\n";

    $comments_url1 = mysql_escape_string("<a href='$code_url/project.php?id=");
    $comments_url2 = mysql_escape_string("'>");
    $comments_url3 = mysql_escape_string("</a>");

    dpsql_dump_themed_query("
        SELECT

            concat('$comments_url1',projectID,'$comments_url2', nameofwork, '$comments_url3') as '" 
                . mysql_real_escape_string(_("Name of Work")) . "',
            authorsname as '" . mysql_real_escape_string(_("Author's Name")) . "',
            language    as '" . mysql_real_escape_string(_("Language")) . "',
            genre       as '" . mysql_real_escape_string(_("Genre")) . "',
            difficulty  as '" . mysql_real_escape_string(_("Difficulty")) . "',
            username    as '" . mysql_real_escape_string(_("Project Manager")) . "',
            FROM_UNIXTIME(modifieddate) as '" 
                . mysql_real_escape_string(_("Date Last Modified")) . "',
            IF(ISNULL(project_holds.state),'&nbsp;','Y') AS '" . mysql_real_escape_string(_("Hold?")) . "'
        FROM projects
            LEFT OUTER JOIN project_holds USING (projectid, state)
        WHERE ($cooked_project_selector)
            AND state='{$round->project_waiting_state}'
        ORDER BY modifieddate, nameofwork
    ");
    echo "<br>\n";
}

// vim: sw=4 ts=4 expandtab