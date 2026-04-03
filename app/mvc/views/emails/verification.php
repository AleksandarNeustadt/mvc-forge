<?php
$subject = __('email.verification.subject', 'Verify Your Email Address');
$userName = $user->first_name . ' ' . $user->last_name;
$content = "
    <h2>" . __('email.verification.title', 'Verify Your Email Address') . "</h2>
    <p>" . __('email.verification.greeting', 'Hello') . " {$userName},</p>
    <p>" . __('email.verification.message', 'Please click the button below to verify your email address:') . "</p>
    <div style='text-align: center;'>
        <a href='{$verification_url}' class='button'>" . __('email.verification.button', 'Verify Email') . "</a>
    </div>
    <p style='font-size: 14px; color: #94a3b8;'>" . __('email.verification.alternative', 'Or copy and paste this link into your browser:') . "</p>
    <p style='font-size: 12px; color: #64748b; word-break: break-all;'>{$verification_url}</p>
    <p style='font-size: 14px; color: #94a3b8;'>" . __('email.verification.expires', 'This link will expire in') . " {$expires_in}.</p>
    <p style='font-size: 14px; color: #94a3b8;'>" . __('email.verification.ignore', 'If you did not create an account, please ignore this email.') . "</p>
";
include __DIR__ . '/layout.php';
?>

