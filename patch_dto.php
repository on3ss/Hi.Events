<?php
$files = [
    'backend/app/Services/Application/Handlers/Account/Payment/Razorpay/DTO/CreateRazorpayLinkedAccountDTO.php',
    'backend/app/Services/Application/Handlers/Account/Payment/Razorpay/DTO/CreateRazorpayLinkedAccountResponse.php',
    'backend/app/Services/Application/Handlers/Account/Payment/Razorpay/DTO/GetRazorpayLinkedAccountsResponseDTO.php',
    'backend/app/Services/Application/Handlers/Account/Payment/Razorpay/DTO/RazorpayLinkedAccountDTO.php'
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $content = str_replace('namespace HiEvents\Services\Application\Handlers\Account\Payment\Stripe\DTO;', 'namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO;', $content);
    $content = str_replace('StripeConnect', 'RazorpayLinked', $content);
    $content = str_replace('stripeConnect', 'razorpayLinked', $content);
    $content = str_replace('stripePlatform', 'razorpayPlatform', $content);
    $content = str_replace('StripePlatform', 'RazorpayPlatform', $content);
    $content = str_replace('stripeAccountId', 'razorpayAccountId', $content);
    $content = str_replace('StripeAccount', 'RazorpayAccount', $content);
    $content = str_replace('primaryStripeAccountId', 'primaryRazorpayAccountId', $content);

    // Fix imports
    $content = str_replace('use HiEvents\DomainObjects\Enums\RazorpayPlatform;', '', $content);

    file_put_contents($file, $content);
}
