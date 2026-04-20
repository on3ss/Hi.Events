<?php
$file = 'backend/app/Http/Actions/Accounts/Razorpay/CreateRazorpayLinkedAccountAction.php';
$content = file_get_contents($file);

$content = str_replace('namespace HiEvents\Http\Actions\Accounts\Stripe;', 'namespace HiEvents\Http\Actions\Accounts\Razorpay;', $content);
$content = str_replace('use HiEvents\DomainObjects\Enums\StripePlatform;', '', $content);
$content = str_replace('use HiEvents\Exceptions\CreateStripeConnectAccountFailedException;', 'use HiEvents\Exceptions\CreateRazorpayLinkedAccountFailedException;', $content);
$content = str_replace('use HiEvents\Exceptions\CreateStripeConnectAccountLinksFailedException;', '', $content);
$content = str_replace('use HiEvents\Resources\Account\Stripe\StripeConnectAccountResponseResource;', 'use HiEvents\Resources\Account\Razorpay\RazorpayLinkedAccountResponseResource;', $content);
$content = str_replace('use HiEvents\Services\Application\Handlers\Account\Payment\Stripe\CreateStripeConnectAccountHandler;', 'use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\CreateRazorpayLinkedAccountHandler;', $content);
$content = str_replace('use HiEvents\Services\Application\Handlers\Account\Payment\Stripe\DTO\CreateStripeConnectAccountDTO;', 'use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\CreateRazorpayLinkedAccountDTO;', $content);
$content = str_replace('CreateStripeConnectAccountAction', 'CreateRazorpayLinkedAccountAction', $content);
$content = str_replace('CreateStripeConnectAccountHandler', 'CreateRazorpayLinkedAccountHandler', $content);
$content = str_replace('createStripeConnectAccountHandler', 'createRazorpayLinkedAccountHandler', $content);
$content = str_replace('CreateStripeConnectAccountDTO', 'CreateRazorpayLinkedAccountDTO', $content);
$content = str_replace('StripeConnectAccountResponseResource', 'RazorpayLinkedAccountResponseResource', $content);
$content = str_replace('CreateStripeConnectAccountLinksFailedException|CreateStripeConnectAccountFailedException', 'CreateRazorpayLinkedAccountFailedException', $content);

$content = str_replace(<<<'PHP'
                'accountId' => $this->getAuthenticatedAccountId(),
                'platform' => $request->has('platform')
                    ? StripePlatform::from($request->get('platform'))
                    : null,
PHP, <<<'PHP'
                'accountId' => $this->getAuthenticatedAccountId(),
PHP, $content);

file_put_contents($file, $content);
