<?php

declare(strict_types=1);

namespace App\Services\Msc\Contracts;

interface MscFileFinalizerRuleInterface extends MscRuleInterface
{
    public function finalizeFile(): ?string;
}
