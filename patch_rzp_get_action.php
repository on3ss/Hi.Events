<?php
$file = 'backend/app/Http/Actions/Accounts/Razorpay/GetRazorpayLinkedAccountsAction.php';
$content = file_get_contents($file);

$content = str_replace('namespace HiEvents\Http\Actions\Accounts\Stripe;', 'namespace HiEvents\Http\Actions\Accounts\Razorpay;', $content);
$content = str_replace('use HiEvents\Resources\Account\Stripe\StripeConnectAccountsResponseResource;', 'use HiEvents\Resources\Account\Razorpay\RazorpayLinkedAccountsResponseResource;', $content);
$content = str_replace('use HiEvents\Services\Application\Handlers\Account\Payment\Stripe\GetStripeConnectAccountsHandler;', 'use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\GetRazorpayLinkedAccountsHandler;', $content);
$content = str_replace('GetStripeConnectAccountsAction', 'GetRazorpayLinkedAccountsAction', $content);
$content = str_replace('GetStripeConnectAccountsHandler', 'GetRazorpayLinkedAccountsHandler', $content);
$content = str_replace('getStripeConnectAccountsHandler', 'getRazorpayLinkedAccountsHandler', $content);
$content = str_replace('StripeConnectAccountsResponseResource', 'RazorpayLinkedAccountsResponseResource', $content);

file_put_contents($file, $content);
