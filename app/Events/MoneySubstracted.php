<?php

namespace App\Events;
namespace App\Events;

use Spatie\EventSourcing\ShouldBeStored;

class MoneySubtracted implements ShouldBeStored
{
    /** @var string */
    public $accountUuid;

    /** @var int */
    public $amount;

    public function __construct(string $accountUuid, int $amount)
    {
        $this->accountUuid = $accountUuid;

        $this->amount = $amount;
    }
}
