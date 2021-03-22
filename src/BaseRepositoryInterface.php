<?php

namespace Darkness\Repository;

interface BaseRepositoryInterface
{
    public function getByQuery($params = [], $size = 25);
    public function getById($id);
    public function getByIdInTrash($id);
    public function store(array $data);
    public function storeArray(array $datas);
    public function update($id, array $data, array $excepts = [], array $only = []);
    public function delete($id);
    public function destroy($id);
    public function restore($id);
}
