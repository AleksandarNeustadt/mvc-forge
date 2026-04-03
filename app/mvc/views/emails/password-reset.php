<?php
$subject = __('email.password_reset.subject', 'Reset Your Password');
$userName = $user->first_name . ' ' . $user->last_name;
$content = "
    <h2>" . __('email.password_reset.title', 'Reset Your Password') . "</h2>
    <p>" . __('email.password_reset.greeting', 'Hello') . " {$userName},</p>
    <p>" . __('email.password_reset.message', 'You requested to reset your password. Click the button below to reset it:') . "</p>
    <div style='text-align: center;'>
        <a href='{$reset_url}' class='button'>" . __('email.password_reset.button', 'Reset Password') . "</a>
    </div>
    <p style='font-size: 14px; color: #94a3b8;'>" . __('email.password_reset.alternative', 'Or copy and paste this link into your browser:') . "</p>
    <p style='font-size: 12px; color: #64748b; word-break: break-all;'>{$reset_url}</p>
    <p style='font-size: 14px; color: #94a3b8;'>" . __('email.password_reset.expires', 'This link will expire in') . " {$expires_in}.</p>
    <p style='font-size: 14px; color: #fbbf24;'>" . __('email.password_reset.warning', 'If you did not request a password reset, please ignore this email. Your password will remain unchanged.') . "</p>
";
include __DIR__ . '/layout.php';
?>

