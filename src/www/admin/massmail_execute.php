<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//

require_once('pre.php');

require_once('common/mail/Mail.class.php');
require_once('common/mail/Codendi_Mail.class.php');
require_once('common/include/Tuleap_Template.class.php');


session_require(array('group'=>1,'admin_flags'=>'A'));

$request = HTTPRequest::instance();
if ($request->isPost() && $request->exist('Submit') &&  $request->existAndNonEmpty('destination')) {

    $validDestination = new Valid_WhiteList('destination' ,array('preview', 'comm', 'sf', 'all', 'admin', 'sfadmin', 'devel'));
    $destination = $request->getValidated('destination', $validDestination);

    // LJ The to_name variable has been added here to be used
    // LJ in the mail command later in this script
    switch ($destination) {
        case 'preview':
            break;
        case 'comm':
            $res_mail = db_query("SELECT email,user_name FROM user WHERE ( status='A' OR status='R' ) AND mail_va=1 GROUP BY lcase(email)");
            $to_name = 'Additional Community Mailings Subcribers';
            break;
        case 'sf':
            $res_mail = db_query("SELECT email,user_name FROM user WHERE ( status='A' OR status='R' ) AND mail_siteupdates=1 GROUP BY lcase(email)");
            $to_name = 'Site Updates Subcribers';
            break;
        case 'all':
            $res_mail = db_query("SELECT email,user_name FROM user WHERE ( status='A' OR status='R' ) GROUP BY lcase(email)");
            $to_name = 'All Users';
            break;
        case 'admin':
            $res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name "
            ."FROM user,user_group WHERE "
            ."user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' ) AND user_group.admin_flags='A' "
            ."GROUP by lcase(email)");
            $to_name = 'Project Administrators';
            break;
        case 'sfadmin':
            $res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name "
            ."FROM user,user_group WHERE "
            ."user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' ) AND user_group.group_id=1 "
            ."GROUP by lcase(email)");
            $to_name = $GLOBALS['sys_name'].' Administrators';
            break;
        case 'devel':
            $res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name "
            ."FROM user,user_group WHERE "
            ."user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' ) GROUP BY lcase(email)");
            $to_name = 'Project Developers';
            break;
        default:
            exit_error('Unrecognized Post','cannot execute');
    }

    $validFormat = new Valid_WhiteList('body_format' ,array(0, 1));
    $bodyFormat = $request->getValidated('body_format', $validFormat, 0);

    $validMessage = new Valid_Text('mail_message');
    if($request->valid($validMessage)) {
        $mailMessage = $request->get('mail_message');
    }

    $mailSubject = '';
    $validSubject = new Valid_String('mail_subject');
    if($request->valid($validSubject)) {
        $mailSubject = $request->get('mail_subject');
    }

    if ($bodyFormat) {
        $hp = Codendi_HTMLPurifier::instance();
        $mail = new Codendi_Mail();
        $tpl = new Tuleap_Template($GLOBALS['Language']->getContent('mail/html_template', 'en_US', null, '.php'));
        $tpl->set('txt_display_not_correct', $GLOBALS['Language']->getText('mail_html_template', 'display_not_correct'));
        $tpl->set('txt_update_prefs', $GLOBALS['Language']->getText('mail_html_template', 'update_prefs'));
        $tpl->set('txt_can_update_prefs', $GLOBALS['Language']->getText('mail_html_template', 'can_update_prefs'));
        $tpl->set('http_url', 'http://'. $GLOBALS['sys_default_domain']);
        $tpl->set('img_path', 'http://'. $GLOBALS['sys_default_domain'] . $GLOBALS['HTML']->getImagePath(''));
        $tpl->set('title', $hp->purify($mailSubject, CODENDI_PURIFIER_CONVERT_HTML));
        $tpl->set('body', $mailMessage);
        $mail->setBodyHtml($tpl->fetch());
    } else {
        $mail = new Mail();
        $mail->setBody($mailMessage);
    }
    $mail->setFrom($GLOBALS['sys_noreply']);
    $mail->setSubject($mailSubject);

    if ($destination != 'preview') {
        site_header(array('title'=>$Language->getText('admin_massmail','title')));
        print '<h2>'.$Language->getText('admin_massmail','header',array($GLOBALS['sys_name'])).'</h2>';

        print $Language->getText('admin_massmail_execute','mailing',array(db_numrows($res_mail)))." ($to_name)<br><br>";
        flush();

        $rows=db_numrows($res_mail);

        $tolist = '';

        for ($i=1; $i<=$rows; $i++) {
            $tolist .= db_result($res_mail,$i-1,'email').', ';
            if ($i % 25 == 0) {
                //spawn sendmail for 25 addresses at a time
                $mail->setBcc($tolist, true);
                $mail->setTo($GLOBALS['sys_noreply'], true);
                if ($mail->send()) {
                    print "<br>".$Language->getText('admin_massmail_execute','sending').": ".$tolist;
                } else {
                    print "<br>".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])).": ".$tolist;
                }
                flush();
                usleep(2000000);
                $tolist='';
            }
        }

        //send the last of the messages.
        if (strlen($tolist) > 0) {
            $mail->setBcc($tolist, true);
            $mail->setTo($GLOBALS['sys_noreply'], true);
            if ($mail->send()) {
                print "<br><br>".$Language->getText('admin_massmail_execute','sending').": ".$tolist."<br><br>";
            } else {
                print "<br><br>".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])).": ".$tolist."<br><br>";
            }
        }
        print "<br>".$Language->getText('admin_massmail_execute','done')."<br>";
        flush();
        $HTML->footer(array());
    } else {
        // This part would send a preview email, parameters are retrieved within the function sendPreview() in MassMail.js
        $validMails = array();
        $addresses  = array_map('trim', preg_split('/[,;]/', $request->get('preview_destination')));
        $rule       = new Rule_Email();
        $um         = UserManager::instance();
        foreach ($addresses as $address) {
            if ($rule->isValid($address)) {
                $validMails[] = $address;
            } else {
                $user = $um->findUser($address);
                if ($user) {
                    $address = $user->getEmail();
                    if ($address) {
                        $validMails[] = $address;
                    } else {
                        print "\n".$Language->getText('admin_massmail_execute','no_user_mail', array($user->getUserName()))."\n";
                    }
                } else {
                    print "\n".$Language->getText('admin_massmail_execute','no_user', array($address))."\n";
                }
            }
        }
        $previewDestination = implode(', ', $validMails);
        $mail->setTo($previewDestination, true);
        if ($mail->send()) {
            print "\n".$Language->getText('admin_massmail_execute','sending').": ".$previewDestination;
        } else {
            print "\n".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])).": ".$previewDestination;
        }
        print "\n".$Language->getText('admin_massmail_execute','done')."\n";
        flush();
    }
}

?>