<?php
$subject = __('email.welcome.subject', 'Welcome to ' . Env::get('BRAND_NAME', 'aleksandar.pro'));
$userName = $user->first_name . ' ' . $user->last_name;
$brandName = Env::get('BRAND_NAME', 'aleksandar.pro');
$content = "
    <h2>" . __('email.welcome.title', 'Welcome to') . " {$brandName}!</h2>
    <p>" . __('email.welcome.greeting', 'Hello') . " {$userName},</p>
    <p>" . __('email.welcome.message', 'Thank you for joining us! We are excited to have you on board.') . "</p>
    <p>" . __('email.welcome.ready', 'Your account is ready to use. You can now log in and start exploring.') . "</p>
    <div style='text-align: center; margin: 30px 0;'>
        <a href='" . Env::get('APP_URL', 'https://aleksandar.pro') . "/" . ($lang ?? 'sr') . "/login' class='button'>" . __('email.welcome.button', 'Log In') . "</a>
    </div>
    <p>" . __('email.welcome.support', 'If you have any questions, feel free to contact our support team.') . "</p>
";
include __DIR__ . '/layout.php';
?>

