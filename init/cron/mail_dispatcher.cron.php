<?php

function cron_mail_dispatcher()
{
    GLOBAL $App,$Db;
    $items = $Db->Query('SELECT * FROM sys_email_queue ORDER BY id LIMIT 0,20');
    if (!Utils::IsArray($items)) return;
    
    $App->Load('settings', 'class');
    $App->Load('mail', 'utils');
    
    $settings = Settings::Get('mail_dispatcher');
    $mailerType = Email::MAILER_NATIVE;
    if (isset($settings['mailer_type'])) $mailerType = $settings['mailer_type'];
    
    $email = new EMail();
    $email->instantSend = true;
    $email->mailerType = $mailerType;
    
    foreach ($items as $item)
    {
        //select mail recipients
        $recipients =  $Db->Query('SELECT * FROM sys_email_queue_recipients WHERE parent_id='.$item['id'].' ORDER BY id LIMIT 0,20');
        if (!Utils::IsArray($recipients))
        {
            $Db->Delete('id='.$item['id'], 'sys_email_queue');
            continue;
        }

        foreach ($recipients as $recipient)
        {
            $email->ClearRecipients();
            $email->AddRecipient($recipient['email']);
            
            $email->subject = $item['subject'];
            $email->message = $item['message'];
            $email->body = $item['body'];
            $email->header = $item['header'];
            
            //parse additional data for recipient, if any
            $params = @unserialize($recipient['params']);
            if (Utils::IsArray($params)) $email->ParseVariables($params);
            
            $email->Send(false, false);
            
            $Db->Delete('id='.$recipient['id'], 'sys_email_queue_recipients');
        }
        
        //check if this mail sent for all recipients
        $recipients =  $Db->Query('SELECT * FROM sys_email_queue_recipients WHERE parent_id='.$item['id'].' LIMIT 1');
        if (!Utils::IsArray($recipients))
        {
            $Db->Delete('id='.$item['id'], 'sys_email_queue');
        }
    }
}

?>