<?php

namespace App\Traits;

use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToBusiness
{
    protected static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope('business', function (Builder $builder) {
            $currentBusinessId = session('current_business_id');

            if ($currentBusinessId) {
                $builder->where($builder->getModel()->getTable() . '.business_id', $currentBusinessId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->business_id) && session('current_business_id')) {
                $model->business_id = session('current_business_id');
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
