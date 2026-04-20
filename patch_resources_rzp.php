<?php
$file = 'backend/app/Resources/Account/Razorpay/RazorpayLinkedAccountsResponseResource.php';
$content = file_get_contents($file);
$content = str_replace("'razorpay_platform' => \$this->account->getActiveStripePlatform()?->value,", '', $content);
$content = preg_replace("/'platform' => \\\$account->platform\?->value,/", "", $content);
file_put_contents($file, $content);
