<?php

namespace Darkness\Repository;

interface EntityInterface
{
    public function scopeSort($query, $sort = null);
}
