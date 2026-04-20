<?php

namespace HiEvents\Repository\Eloquent;

use HiEvents\DomainObjects\AccountRazorpayPlatformDomainObject;
use HiEvents\Models\AccountRazorpayPlatform;
use HiEvents\Repository\Interfaces\AccountRazorpayPlatformRepositoryInterface;

/**
 * @extends BaseRepository<AccountRazorpayPlatformDomainObject>
 */
class AccountRazorpayPlatformRepository extends BaseRepository implements AccountRazorpayPlatformRepositoryInterface
{
    public function getModel(): string
    {
        return AccountRazorpayPlatform::class;
    }

    public function getDomainObject(): string
    {
        return AccountRazorpayPlatformDomainObject::class;
    }
}
