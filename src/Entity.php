<?php

namespace Darkness\Repository;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Darkness\Repository\Cache\ModelCacheTrait;

class Entity extends Model implements EntityInterface
{
    use ModelCacheTrait;
    /**
     * acl groups allow action
     * example:
     * [
     *   'view' => ['admin', 'accountance'],
     *   'create' => ['admin', 'saler'],
     *   'update' => ['admin'],
     *   'delete' => ['admin']
     * ]
     * @var array
     */
    public static $permissions = [];

    public function getAllAllowColumns()
    {
        $timestampColumns = [];
        if ($this->usesTimestamps()) {
            $timestampColumns[] = $this->getCreatedAtColumn();
            $timestampColumns[] = $this->getUpdatedAtColumn();
        }
        return array_merge([$this->getKeyName()], $this->getFillable(), $timestampColumns);
    }

    /**
     * order by query
     * @author  
     * @param  [type]     $query [description]
     * @param  string     $sort  [created_at:-1,id:-1]
     * @return [type]            [description]
     */
    public function scopeSort($query, $sort = null)
    {
        if (is_null($sort)) {
            $sort = $this->usesTimestamps() ? 'created_at:-1' : 'id:-1';
        }
        $columns = $this->getAllAllowColumns();
        $sorts = explode(',', $sort);
        foreach ($sorts as $sort) {
            $sort = explode(':', $sort);
            list($field, $type) = [Arr::get($sort, '0', 'created_at'), Arr::get($sort, '1', 1)];
            if (in_array($field, $columns)) {
                $query->orderBy($this->getTable() . '.' . $field, $type == 1 ? 'ASC' : 'DESC');
            }
        }
        return $query;
    }

    protected function generateCode($prefix = null, $attributes = 'code')
    {
        $this->$attributes = Code::generate($this->id, $prefix = $prefix);
        $this->save();
    }

    public static function getName()
    {
        preg_match('@\\\\([\w]+)$@', get_called_class(), $matches);
        return $matches[1];
    }

    public static function lazyloadInclude(array $includes)
    {
        $with = [];
        foreach ($includes as $include) {
            if (isset(static::$mapLazyLoadInclude[$include])) {
                $with[] = static::$mapLazyLoadInclude[$include];
            }
        }
        return $with;
    }
}
