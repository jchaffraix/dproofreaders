<?php
$relPath="./../../pinc/";
include_once($relPath.'base.inc');
include_once($relPath.'misc.inc');
include_once($relPath.'theme.inc');
include_once($relPath.'metarefresh.inc');
include_once('../includes/team.inc');

$order = get_enumerated_param(
        $_GET, 'order', 'id', array('id', 'teamname', 'member_count') );
$direction = get_enumerated_param(
        $_GET, 'direction', 'asc', array('asc', 'desc') );
$tname = array_get($_REQUEST, 'tname', null);
$texact = array_get($_REQUEST, 'texact', null);

$tstart = get_integer_param( $_GET, 'tstart', 0, 0, null );

if ($tname) {
    if ($texact)
        $where_body = "teamname='$tname'";
    else
        $where_body = "teamname LIKE '%$tname%'";

    $tResult = select_from_teams($where_body, "ORDER BY $order $direction LIMIT $tstart,20");
    $tRows = mysql_num_rows($tResult);
    if ($tRows == 1) { metarefresh(0,"tdetail.php?tid=".mysql_result($tResult,0,"id")."",'',''); exit; }
    $tname = "tname=" . urlencode($tname) . "&";
} else {
    $tResult=select_from_teams("", "ORDER BY $order $direction LIMIT $tstart,20");
    $tRows=mysql_num_rows($tResult);
    $tname = "";
}

$name = _("Team List");

output_header($name);
echo "<h1>$name</h1>\n";

//Display of user teams
echo "<table class='themed striped'>\n";
echo "<tr>";
    echo "<th>"._("Icon")."</th>";
    if ($order == "id" && $direction == "asc") { $newdirection = "desc"; } else { $newdirection = "asc"; }
        echo "<th><a href='tlist.php?".$tname."tstart=$tstart&order=id&direction=$newdirection'>"._("ID")."</a></th>";
    if ($order == "teamname" && $direction == "asc") { $newdirection = "desc"; } else { $newdirection = "asc"; }
        echo "<th><a href='tlist.php?".$tname."tstart=$tstart&order=teamname&direction=$newdirection'>"._("Team Name")."</a></th>";
    if ($order == "member_count" && $direction == "desc") { $newdirection = "asc"; } else { $newdirection = "desc"; }
        echo "<th><a href='tlist.php?".$tname."tstart=$tstart&order=member_count&direction=$newdirection'>"._("Total Members")."</a></th>";
    echo "<th>"._("Options")."</th>";
echo "</tr>\n";
if (!empty($tRows)) {
    while ($row = mysql_fetch_assoc($tResult)) {
        echo "<tr>";
        echo "<td align='center'><a href='tdetail.php?tid=".$row['id']."'><img src='$team_icons_url/".$row['icon']."' width='25' height='25' alt='".attr_safe($row['teamname'])."' border='0'></a></td>";
        echo "<td align='center'><b>".$row['id']."</b></td>";
        echo "<td>".$row['teamname']."</td>";
        echo "<td align='center'>".$row['member_count']."</td>";
        echo "<td align='center'><b><a href='tdetail.php?tid=".$row['id']."'>"._("View")."</a>&nbsp;";
        if ($userP['team_1'] != $row['id'] && $userP['team_2'] != $row['id'] && $userP['team_3'] != $row['id']) {
            echo "<a href='../members/jointeam.php?tid=".$row['id']."'>"._("Join")."</a></b></td>";
        } else {
            echo "<a href='../members/quitteam.php?tid=".$row['id']."'>"._("Quit")."</a></b></td>";
        }
        echo "</tr>\n";
    }
} else {
    echo "<tr><td colspan='5' align='center'><b>"._("No more teams available.")."</b></td></tr>\n";
}

echo "<tr><td colspan='3' align='left'>";
if (!empty($tstart)) {
    echo "<b><a href='tlist.php?".$tname."order=$order&direction=$direction&tstart=".($tstart-20)."'>"._("Previous")."</a></b>";
}
echo "&nbsp;</td><td colspan='2' align='right'>&nbsp;";
if ($tRows == 20) {
    echo "<b><a href='tlist.php?".$tname."order=$order&direction=$direction&tstart=".($tstart+20)."'>"._("Next")."</a></b>";
}
echo "</td></tr>\n";
echo "<tr><th colspan='5' align='center'><b><a href='new_team.php'>"._("Create a New Team")."</a></b></th></tr>\n";
echo "</table>";

// vim: sw=4 ts=4 expandtab
