<?php
$relPath = '../pinc/';
include_once($relPath.'base.inc');
include_once($relPath.'user_is.inc');
include_once($relPath.'stages.inc');
include_once($relPath.'maybe_mail.inc');
include_once($relPath.'access_log.inc');
include_once($relPath.'SettingsClass.inc');
include_once($relPath.'User.inc');
include_once($relPath.'slim_header.inc');

require_login();

$subject_username = array_get($_POST, 'subject_username', null);
$notify_user = array_get($_POST, 'notify_user', null);

if (check_username($subject_username) != '')
    die("Invalid parameter subject_username.");

list($can_grant,$can_revoke) = user_can_modify_access_of($subject_username);
if ( !$can_grant && !$can_revoke )
    die( "Error: you are not permitted to modify the access of user '$subject_username'." );

slim_header(_("Modify Access"));

echo "subject_username='$subject_username'<br>\n";

// so we don't hit them in the following loop
unset($_POST['subject_username']);
unset($_POST['notify_user']);

$actions = array();
foreach ( $_POST as $name => $value )
{
    echo "<br>\n";
    echo "Considering '$name' => '$value'<br>\n";

    if ( $value != 'on' ) die( "Error: unexpected value in parameter '$name' => '$value'" );

    $a = explode( '|', $name );
    if ( count($a) != 2 ) die( "Error: bad parameter name '$name'" );

    list( $activity_id, $action_type ) = $a;

    if ( $action_type != 'grant' && $action_type != 'revoke' && $action_type != 'deny_request_for' )
    {
        die( "Error: bad parameter name '$name'" );
    }

    @$activity = $Activity_for_id_[$activity_id];
    if ( is_null($activity) )
    {
        die( "Error: no activity named '$activity_id'" );
    }

    // Okay, it's a meaningful action.
    echo "    i.e., $action_type access to/from $activity_id<br>\n";

    if ( array_key_exists( $activity_id, $actions ) )
    {
        die( "Error: You have more than one modification for $activity_id" );
    }

    if ( $action_type == 'grant' && !$can_grant )
    {
        die( "Error: You are not permitted to grant access." );
    }
    elseif ( $action_type == 'revoke' && !$can_revoke )
    {
        die( "Error: You are not permitted to revoke access." );
    }
    elseif ( $action_type == 'deny_request_for' && !$can_revoke )
    {
        die( "Error: You are not permitted to deny requests." );
    }

    // And it's an action that the current user is permitted to take.

    $uao = $activity->user_access($subject_username);

    if ( $action_type == 'grant' )
    {
        if ( $uao->can_access )
        {
            die( "Error: The user already has access to $activity_id" );
        }
        if ( $uao->request_status == 'sat-denied' || $uao->request_status == 'unsat-denied' )
        {
            die( "Error: The user has been denied access to $activity_id" );
        }
   } 
    elseif ( $action_type == 'revoke' )
    {
        if ( !$uao->can_access )
        {
            die( "Error: The user does not have access to $activity_id" );
        }

        if ($uao->request_status == 'sat-unneeded' )
        {
            die( "Error: you can't revoke access when it's IMMEDIATE" );
        }

        if ($activity->after_satisfying_minima == 'REQ-AUTO')
        {
            echo "Warning: you can revoke access, but it can just be auto-granted again.<br>\n";
        }
    }
    elseif ( $action_type == 'deny_request_for' )
    {
        if ( $uao->can_access )
        {
            die( "Error: The user already has access to $activity_id" );
        }

        if ( $uao->request_status != 'sat-requested'
          && $uao->request_status != 'unsat-requested' )
        {
            die( "Error: The user hasn't requested access to $activity_id" );
        }
    }
    else
    {
        assert( 0 );
    }

    // And it's an action hat is valid for the subject user at the current time.

    // So put it on the queue.
    // (Don't execute any actions unless they're all valid.)

    $actions[$activity_id] = $action_type;
}

if ( count($actions) == 0 )
{
    die( "Warning: you did not specify any modifications" );
}

echo "<br>\n";
echo "Those modifications appear to be valid.<br>\n";
echo "Performing them now...<br>\n";

foreach ( $actions as $activity_id => $action_type )
{
    echo "<br>\n";
    echo "$action_type $activity_id ...<br>\n";
    $yesno = ( $action_type == 'grant' ? 'yes' : 'no' );

    $userSettings =& Settings::get_Settings($subject_username);
    $userSettings->set_value("$activity_id.access", $yesno);

    log_access_change( $subject_username, $pguser, $activity_id, $action_type );
}

echo "<br>\n";
echo "Done<br>\n<br>\n";
if ($notify_user)
    echo "Notifying user... ".notify_user($subject_username,$actions)."<br>\n<br>\n";
echo "Hit 'Back' to return to user's detail page. (And you may need to reload.)<br>\n";

// -----------------------------------------------------------------------------

function notify_user($username,$actions)
{
    global $site_name, $site_signoff;
    $user = new User($username);
    if ((count($actions) == 1) && (array_search('grant',$actions) !== false))
    {
        // Special case: If the user has been granted access to
        // a single round, send a congratulations! email.
        list($activity_id) = array_keys($actions);
        $subject = "DP: You have been granted access to $activity_id!";
        $message = "Hello $username,\n\nThis is a message from the $site_name website.\n\n" .
                   "Congratulations, you have been granted access to $activity_id projects!\n" .
                   "You can access this stage by following the link to it at the Activity Hub.\n\n" .
                   "$site_signoff";
        // XXX: Note that this wording works when the activity is a stage (round or pool),
        // but not otherwise.
        maybe_mail($user->email,$subject,$message);
        return "congratulated user.";
    }
    else
    {
        $subject =  "DP: Your access has been modified";
        $message =  "Hello $username,\n\nThis is a message from the $site_name website.\n\n" .
                    "The following modifications have been made to the stages in which you can work:\n";
        foreach ( $actions as $activity_id => $action_type )
        {
            $message .= "\n";
            $message .= "  $activity_id: ";
            $message .=
                ( $action_type == 'deny_request_for' ? 'Your request for access has not been approved. Please try again in a few weeks.' :
                ( $action_type == 'grant' ? 'Access granted.' :
                'Access revoked.' ) );
        }
        $message .= "\n\n$site_signoff";
        maybe_mail($user->email,$subject,$message);
        return "notified user.";
    }
}
// vim: sw=4 ts=4 expandtab
