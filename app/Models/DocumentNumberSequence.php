<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $scope_type
 * @property int $scope_id
 * @property string $document_type
 * @property int $fiscal_year
 * @property int $next_number
 */
class DocumentNumberSequence extends Model
{
    protected $guarded = [];
}
