<?
$relPath="./../pinc/";
include($relPath.'site_vars.php');
include($relPath.'pg.inc');
include($relPath.'username.inc');
include($relPath.'email_address.inc');
include($relPath.'new_user_mails.inc');
include($relPath.'connect.inc');
$db_Connection=new dbConnect();
$db_link=$db_Connection->db_lk;
include($relPath.'theme.inc');

// This function takes any error message given to it, displays it & terminates the page

function abort_registration( $error )
{
    $header = _("Registration Incomplete");
    theme($header, "header");
    echo "$error<br>\n";
    $please = _("Please hit 'Back' and try again.");
    echo "$please<br>\n";
    $back = _("Back");
    echo "<a href=\"addproofer.php\" onClick=\"history.go(-1); return false;\">".$back."</a>";
    theme("", "footer");
    exit;
}

$password = isset($_POST['password'])? $_POST['password']: '';
if ($password=="proofer") {

    // From the form filled out at the end of this file

    $real_name = $_POST['real_name'];
    $username = $_POST['userNM'];
    $userpass = $_POST['userPW'];
    $email = $_POST['email'];
    $userpass2 = $_POST['userPW2'];
    $email2 = $_POST['email2'];
    $email_updates = $_POST['email_updates'];

    // Make sure that password and confirmed password are equal.
    if ($userpass != $userpass2)
    {
        abort_registration( _("The passwords you entered were not equal.") );
    }

    // Make sure that email and confirmed email are equal.
    if ($email != $email2)
    {
        abort_registration( _("The e-mail addresses you entered were not equal.") );
    }

    // Do some validity-checks on inputted username, password, e-mail and real name

    $err = check_username( $username );
    if ( $err != '' )
    {
        abort_registration( $err );
    }

    $err = check_email_address( $email );
    if ( $err != '' )
    {
        abort_registration( $err );
    }

    if (empty($userpass) || empty($real_name))
    {
        $error = _("You did not completely fill out the form.");
        abort_registration($error);
    }

    $todaysdate = time();

    $ID = uniqid("userID");

    // Make sure that the username is not taken by a non-registered user.
    $query = sprintf("
        SELECT username
        FROM users
        WHERE username='%s'
        ", mysql_real_escape_string($username));
    $result = mysql_query ($query);
    if (mysql_num_rows($result) > 0) {
        $error = _("That user name already exists, please try another.");
        abort_registration($error);
    }

    $digested_password = md5($userpass);

    $query = sprintf("INSERT INTO non_activated_users (id, real_name, username, email, date_created, email_updates, u_intlang, user_password) VALUES ('%s', '%s', '%s', '%s', $todaysdate, '%s', '%s', '%s')", mysql_real_escape_string($ID), mysql_real_escape_string($real_name), mysql_real_escape_string($username), mysql_real_escape_string($email), mysql_real_escape_string($email_updates), mysql_real_escape_string($intlang), mysql_real_escape_string($digested_password));

    $result = mysql_query ($query);

    if (!$result) {
        if ( mysql_errno() == 1062 ) // ER_DUP_ENTRY
        {
            // The attempted INSERT violated a uniqueness constraint.
            // The non_activated_users table has only one such constraint,
            // the PRIMARY KEY on the 'username' column.
            // Thus, $username duplicates a username value in non_activated_users.
            $error = _("That username has already been requested. Please try another.");
        }
        else
        {
            $error = _("Can not initiate user registration.");
        }
        abort_registration($error);

    } else {
        // Send them an activation e-mail
        maybe_activate_mail($email, $real_name, $ID, stripslashes($username), $intlang);

        // Page shown when account is successfully created

        $header = sprintf(_("User %s Registered Successfully"), $username);
	theme($header, "header");

        echo sprintf(
               _("User %s registered successfully. Please check the e-mail being sent to you for further information about activating your account.".
                 "This extra step is taken so that no-one can register you to the site without your knowledge."),
               $username);

    }
} else {

// This is the portion that shows up when no parameters are given to the file
//
// When users fill the form out below, it will submit the information back
// to this file & run the above commands.

    $header = _("Create An Account");
    theme($header, "header");

    echo "<h1>" . _("Account Registration") . "</h1>";
    echo sprintf(_("Thank you for your interest in %s. Fill out the form below to create an account."), $site_name);

    echo "<h2>" . _("Registration Hints") . "</h2>";
    echo "<ul>";
    echo "<li>" . sprintf(_("Please choose your User Name carefully. Your User Name will be visible to other %s users and cannot be changed."), $site_abbreviation) . "</li>";
    echo "<li>" . sprintf(_("Make sure your E-mail Address is correct. You will be e-mailed a confirmation link which you will need to follow in order for your %s account to be activated."), $site_abbreviation) . "</li>";
    echo "<li>" . _("If your e-mail provider uses a challenge/response, greylist, or other anti-spam measures, please add our site to the list of permitted senders <i>before</i> submitting this form so that the activation e-mail will reach you.") . "</li>";
    echo "</ul>";

    if ( $testing )
    {
	    echo "<p style='color: red'>";
	    echo _("Because this is a test site, that message won't be sent to you. Instead, when you hit the 'Send E-mail' button, the message will be displayed on the next screen. At that point, you can copy and paste the confirmation link into your browser's location field. <b>Your account won't be created until you access the confirmation link.</b>");
	    echo "</p>";
    }

    echo "<center>";
    echo "<form method='post' action='addproofer.php'>\n";
    echo "<input type='hidden' name='password' value='proofer'>\n";
    echo "<table class='register'>";
    echo "<tr>";
    echo "  <td class='label'>" . _("Real Name") . ":</td>";
    echo "  <td class='field'><input type='text' maxlength='70' name='real_name' size='20'></td>";
    echo "</tr>\n<tr>";
    echo "  <td class='label'>" . _("User Name") . ":</td>";
    echo "  <td class='field'><input type='text' maxlength='70' name='userNM' size='20'></td>";
    echo "</tr>\n<tr>";
    echo "  <td class='label'>" . _("Password") . ":</td>";
    echo "  <td class='field'><input type='password' maxlength='70' name='userPW' size='20'></td>";
    echo "</tr>\n<tr>";
    echo "  <td class='label'>" . _("Confirm Password") . ":</td>";
    echo "  <td class='field'><input type='password' maxlength='70' name='userPW2' size='20'></td>";
    echo "</tr>\n<tr>";
    echo "  <td class='label'>" . _("E-mail Address") . ":</td>";
    echo "  <td class='field'><input type='text' maxlength='70' name='email' size='20'></td>";
    echo "</tr>\n<tr>";
    echo "  <td class='label'>" . _("Confirm E-mail Address") . ":</td>";
    echo "  <td class='field'><input type='text' maxlength='70' name='email2' size='20'></td>";
    echo "</tr>\n<tr>";
    echo "  <td class='label'><b>" . _("E-mail Updates") . ":</td>";
    echo "  <td class='field'>";
    echo "    <input type='radio' name='email_updates' value='1' checked>" . _("Yes") . "&nbsp;&nbsp;";
    echo "    <input type='radio' name='email_updates' value='0'>" . _("No");
    echo "  </td>";
    echo "</tr>\n<tr>";
    echo "  <td bgcolor='#336633' colspan='2' align='center'><input type='submit' value='" . _("Send E-Mail required to activate account") . "'>&nbsp;&nbsp;<input type='reset'></td>";
    echo "</td></tr></table></form>";
    echo "</center>";

    if(file_exists($code_dir.'/faq/'.lang_dir().'privacy.php')) {
        include($code_dir.'/faq/'.lang_dir().'privacy.php');
    } else {
        include($code_dir.'/faq/privacy.php');
    }

}
theme("", "footer");

// vim: sw=4 ts=4 expandtab
?>
