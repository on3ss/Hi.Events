<?php
$file = 'backend/app/Services/Application/Handlers/Account/Payment/Razorpay/CreateRazorpayLinkedAccountHandler.php';
$content = file_get_contents($file);

$saasLogic = <<<PHP
        \$isConnectSetupComplete = true; // Assuming created accounts are immediately usable or we'll check status

        \$response = new CreateRazorpayLinkedAccountResponse(
            razorpayLinkedAccountType: 'route',
            razorpayAccountId: \$razorpayLinkedAccount->id,
            account: \$account,
            isConnectSetupComplete: \$isConnectSetupComplete,
        );

        // Fetch it again to get the updated local state if it was just created
        \$account = \$this->accountRepository
            ->loadRelation(AccountRazorpayPlatformDomainObject::class)
            ->findById(\$command->accountId);
        \$accountRazorpayPlatform = \$account->getPrimaryRazorpayPlatform();

        if (\$response->isConnectSetupComplete) {
            if (\$accountRazorpayPlatform && \$accountRazorpayPlatform->getRazorpaySetupCompletedAt() === null) {
                \$this->razorpayAccountSyncService->markAccountAsComplete(\$accountRazorpayPlatform, \$razorpayLinkedAccount);
            }
            return \$response;
        }

        return \$response;
PHP;

$content = preg_replace(
    '/\$isConnectSetupComplete = true;.*?return \$response;\n    \}/s',
    $saasLogic . "\n    }",
    $content
);

file_put_contents($file, $content);
