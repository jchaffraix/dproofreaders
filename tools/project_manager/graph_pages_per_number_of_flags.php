<?php
$relPath="../../pinc/";
include_once($relPath.'base.inc');
include_once($relPath.'Project.inc');
include_once('../../stats/jpgraph_files/common.inc');

require_login();

$projectid = validate_projectID("projectid", @$_GET["projectid"]);

// data for this graph is generated in show_wordcheck_page_stats.php

draw_simple_bar_graph(
   init_simple_bar_graph(600, 300, -1),
   $_SESSION["graph_pages_per_number_of_flags"][$projectid]["graph_x"],
   $_SESSION["graph_pages_per_number_of_flags"][$projectid]["graph_y"],
   ceil(count($_SESSION["graph_pages_per_number_of_flags"][$projectid]["graph_x"])/40),
   _("Number of flags on a page"),
   _("Pages with that many flags")
);

// unsetting graph_pages_per_number_of_flags variable in the session
// to prevent it from getting large
// consider keeping this data if calling this
// image multiple times is needed in future code changes
unset($_SESSION["graph_pages_per_number_of_flags"][$projectid]);

// vim: sw=4 ts=4 expandtab