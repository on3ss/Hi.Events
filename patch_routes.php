<?php
$file = 'backend/routes/api.php';
$content = file_get_contents($file);

$imports = <<<IMPORTS
use HiEvents\Http\Actions\Accounts\Razorpay\CreateRazorpayLinkedAccountAction;
use HiEvents\Http\Actions\Accounts\Razorpay\GetRazorpayLinkedAccountsAction;
IMPORTS;

$routes = <<<ROUTES
        \$router->get('/accounts/{account_id}/stripe/connect_accounts', GetStripeConnectAccountsAction::class);
        \$router->post('/accounts/{account_id}/stripe/connect', CreateStripeConnectAccountAction::class);

        \$router->get('/accounts/{account_id}/razorpay/linked_accounts', GetRazorpayLinkedAccountsAction::class);
        \$router->post('/accounts/{account_id}/razorpay/connect', CreateRazorpayLinkedAccountAction::class);
ROUTES;

$content = str_replace('use HiEvents\Http\Actions\Accounts\Stripe\GetStripeConnectAccountsAction;', "use HiEvents\Http\Actions\Accounts\Stripe\GetStripeConnectAccountsAction;\n" . $imports, $content);

$content = str_replace("        \$router->get('/accounts/{account_id}/stripe/connect_accounts', GetStripeConnectAccountsAction::class);\n        \$router->post('/accounts/{account_id}/stripe/connect', CreateStripeConnectAccountAction::class);", $routes, $content);

file_put_contents($file, $content);
