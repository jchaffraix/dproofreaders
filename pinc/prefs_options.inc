<?php
define( 'BROWSER_DEFAULT_STR', _("Browser Default") );

# Care should be taken when changing the following arrays. The user
# preferences (stored in user_profiles) stores the array index
# and not the array value so changing the order of the array, such as
# by sorting or addition of a value other than at the end, would
# impact all users.
$f_f= array(BROWSER_DEFAULT_STR, 'Courier', 'Times', 'Arial', 'Lucida', 'monospace', 'DPCustomMono2', 'Lucida Console', 'Monaco');
$f_s= array(BROWSER_DEFAULT_STR,'8pt','9pt','10pt','11pt','12pt','13pt','14pt','15pt','16pt','18pt','20pt');
$u_n= array('0', '2', '4', '6', '8', '10', '12', '14', '16', '18', '20');

define('PRIVACY_PUBLIC',    0);
define('PRIVACY_ANONYMOUS', 1);
define('PRIVACY_PRIVATE',   2);
// See ../faq/pophelp/prefs/set_privacy.html
// for definitions of these privacy categories.