<?php

namespace HiEvents\Repository\Interfaces;

use HiEvents\DomainObjects\AccountRazorpayPlatformDomainObject;

interface AccountRazorpayPlatformRepositoryInterface extends RepositoryInterface
{
    public function findByAccountId(int $accountId): ?AccountRazorpayPlatformDomainObject;
}