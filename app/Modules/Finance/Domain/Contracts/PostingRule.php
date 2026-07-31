<?php

namespace App\Modules\Finance\Domain\Contracts;

use App\Modules\Finance\Domain\JournalLineData;
use Illuminate\Database\Eloquent\Model;

interface PostingRule
{
    /**
     * @return list<JournalLineData>
     */
    public function resolveLines(Model $document): array;
}
