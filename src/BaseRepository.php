<?php

namespace Darkness\Repository;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Darkness\Repository\Cache\QueryCacheTrait;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements BaseRepositoryInterface
{
    use QueryCacheTrait;
    /**
     * Eloquent model
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;
    private $reflection;

    protected function getReflection()
    {
        if ($this->reflection) {
            return $this->reflection;
        }
        $this->reflection = new \ReflectionClass($this->getModel());
        return $this->reflection;
    }

    protected function getModel()
    {
        return $this->model;
    }

    public function getInstance($object)
    {
        if (is_a($object, get_class($this->getModel()))) {
            return $object;
        } else {
            return $this->getById($object);
        }
    }

    /**
     * Lấy tất cả bản ghi có phân trang
     * @author KingDarkness <lekhang2512@gmail.com>
     * @param array $params
     * @param integer $size Số bản ghi mặc định 25
     * @throws \ReflectionException
     * @return Illuminate\Pagination\Paginator
     */
    public function getByQuery($params = [], $size = 25)
    {
        $sort = Arr::get($params, 'sort', 'created_at:-1');

        $params['sort'] = $sort;
        $lModel = $this->getModel();
        $query = Arr::except($params, ['page', 'limit']);
        if (count($query)) {
            $lModel = $this->applyFilterScope($lModel, $query);
        }

        switch ($size) {
            case -1:
                $callback = function ($query, $size) {
                    return $query->get();
                };
                break;
            case 0:
                $callback = function ($query, $size) {
                    return $query->first();
                };
                break;
            default:
                $callback = function ($query, $size) {
                    return $query->paginate($size);
                };
                break;
        }
        $records =  $this->callWithCache(
            $callback,
            [$lModel, $size],
            $this->getCacheKey(env('APP_NAME'), $this->getModel()->getName() . '.getByQuery', Arr::dot($params)),
            $this->getModel()->defaultCacheKeys('list')
        );
        return $this->lazyLoadInclude($records);
    }

    protected function applyFilterScope($lModel, array $params)
    {
        foreach ($params as $funcName => $funcParams) {
            $funcName = \Illuminate\Support\Str::studly($funcName);
            if ($this->getReflection()->hasMethod('scope' . $funcName)) {
                $funcName = lcfirst($funcName);
                $lModel = $lModel->$funcName($funcParams);
            }
        }
        return $lModel;
    }

    protected function getIncludes()
    {
        $query = app()->make(Request::class)->query();
        $includes = Arr::get($query, 'include', []);
        if (!is_array($includes)) {
            $includes = array_map('trim', explode(',', $includes));
        }
        return $includes;
    }

    protected function lazyLoadInclude($objects)
    {
        if ($this->getReflection()->hasProperty('mapLazyLoadInclude')) {
            $includes = $this->getIncludes();
            $with = call_user_func($this->getReflection()->name . '::lazyloadInclude', $includes);
            if (get_class($objects) == LengthAwarePaginator::class) {
                return $objects->setCollection($objects->load($with));
            }
            return $objects->load($with);
        }
        return $objects;
    }

    /**
     * Lấy thông tin 1 bản ghi xác định bởi ID
     * @author KingDarkness <lekhang2512@gmail.com>
     *
     * @param  integer $id ID bản ghi
     * @return Eloquent
     */
    public function getById($id, $key = 'id')
    {
        if ($key == 'id' && is_numeric($id)) {
            $id = (int) $id;
        }

        $callback = function ($id, $static, $key) {
            if ($key != $static->getModel()->getKeyName()) {
                return $static->getModel()->where($key, $id)->firstOrFail();
            }
            return $static->getModel()->findOrFail($id);
        };
        $record =  $this->callWithCache(
            $callback,
            [$id, $this, $key],
            $this->getCacheKey(env('APP_NAME'), $this->getModel()->getName() . '.getById', [$key => $id])
        );

        return $this->lazyLoadInclude($record);
    }

    /**
     * Lấy thông tin 1 bản ghi đã bị xóa softDelete được xác định bởi ID
     * @author KingDarkness <lekhang2512@gmail.com>
     *
     * @param  integer $id ID bản ghi
     * @return Eloquent
     */
    public function getByIdInTrash($id, $key = 'id')
    {
        if (is_numeric($id)) {
            $id = (int) $id;
        }
        $callback = function ($id, $static, $key) {
            if ($key != $static->getModel()->getKeyName()) {
                return $static->getModel()->withTrashed()->where($key, $id)->firstOrFail();
            }
            return $static->getModel()->withTrashed()->findOrFail($id);
        };
        $record = $this->callWithCache(
            $callback,
            [$id, $this, $key],
            $this->getCacheKey(env('APP_NAME'), $this->getModel()->getName() . '.getByIdInTrash', [$key => $id])
        );
        return $this->lazyLoadInclude($record);
    }

    /**
     * Lưu thông tin 1 bản ghi mới
     * @author KingDarkness <lekhang2512@gmail.com>
     *
     * @param  array $data
     * @return Eloquent
     */
    public function store(array $data)
    {
        return $this->getModel()->create(Arr::only($data, $this->getModel()->getFillable()));
    }

    /**
     * Lưu thông tin nhiều bản ghi
     * @author KingDarkness <lekhang2512@gmail.com>
     * @param  [type]     $datas [description]
     * @return Eloquent [type]            [description]
     */
    public function storeArray(array $datas)
    {
        if (count($datas) && is_array(reset($datas))) {
            $fillable = $this->getModel()->getFillable();
            $now = \Carbon\Carbon::now();

            foreach ($datas as $key => $data) {
                $datas[$key] = Arr::only($data, $fillable);
                if ($this->getModel()->usesTimestamps()) {
                    $datas[$key]['created_at'] = $now;
                    $datas[$key]['updated_at'] = $now;
                }
            }
            $result = $this->getModel()->insert($datas);
            if ($result) {
                \Cache::tags($this->getModel()->listCacheKeys('list'))->flush();
            }
            return $result;
        }

        return $this->store($datas);
    }

    /**
     * Cập nhật thông tin 1 bản ghi theo ID
     * @author KingDarkness <lekhang2512@gmail.com>
     *
     * @param  integer $id ID bản ghi
     * @return Eloquent
     */
    public function update($id, array $data, array $excepts = [], array $only = [])
    {
        $data = Arr::except($data, $excepts);
        if (count($only)) {
            $data = Arr::only($data, $only);
        }
        $record = $this->getInstance($id);

        $record->fill($data)->save();
        return $record;
    }

    /**
     * Xóa 1 bản ghi. Nếu model xác định 1 SoftDeletes
     * thì method này chỉ đưa bản ghi vào trash. Dùng method destroy
     * để xóa hoàn toàn bản ghi.
     * @author KingDarkness <lekhang2512@gmail.com>
     *
     * @param  integer $id ID bản ghi
     * @return bool|null
     */
    public function delete($id)
    {
        $record = $this->getInstance($id);
        return $record->delete();
    }

    /**
     * Xóa hoàn toàn một bản ghi
     * @author KingDarkness <lekhang2512@gmail.com>
     * @param  integer $id ID bản ghi
     * @return bool|null
     */
    public function destroy($id)
    {
        $record = $this->getInstance($id);

        return $record->forceDelete();
    }

    /**
     * Khôi phục 1 bản ghi SoftDeletes đã xóa
     * @author KingDarkness <lekhang2512@gmail.com>
     * @param  integer $id ID bản ghi
     * @return bool|null
     */
    public function restore($id)
    {
        $record = $this->getInstance($id);
        return $record->restore();
    }
}
