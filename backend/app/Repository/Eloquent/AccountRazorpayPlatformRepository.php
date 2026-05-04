<?php

namespace HiEvents\Repository\Eloquent;

use HiEvents\DomainObjects\AccountRazorpayPlatformDomainObject;
use HiEvents\Models\AccountRazorpayPlatform;
use HiEvents\Repository\Interfaces\AccountRazorpayPlatformRepositoryInterface;

class AccountRazorpayPlatformRepository extends BaseRepository implements AccountRazorpayPlatformRepositoryInterface
{
    protected function getModel(): string
    {
        return AccountRazorpayPlatform::class;
    }

    public function getDomainObject(): string
    {
        return AccountRazorpayPlatformDomainObject::class;
    }

    public function findByAccountId(int $accountId): ?AccountRazorpayPlatformDomainObject
    {
        $results = $this->findWhere(['account_id' => $accountId]);
        return $results->first() ?: null;
    }

    public function updateById(int $id, array $data): bool
    {
        return (bool) $this->model
            ->newQuery()
            ->where('id', $id)
            ->update($data);

    }
}