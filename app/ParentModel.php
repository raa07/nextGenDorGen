<?php

namespace App;

abstract class ParentModel
{
    protected $collection_name;
    public $collection;

    public function all() //получение всех записей из бд
    {
        return $this->collection->find();
    }

    protected function insert(array $data):bool //вставка данных в бд
    {
        return (bool) $this->collection->insertOne($data);
    }

    protected function insertMany(array $data):bool //вставка массива данных в бд
    {
        return (bool) $this->collection->insertMany($data);
    }

    protected function findOne(string $field, string $value) //получение записи из бд по полю и его значению
    {
        $result = $this->collection->findOne([$field => $value]);
        return (bool) $result ? $result : [];
    }

    public function getById($id)
    {
        if(is_string($id)) {
            $id = new \MongoDB\BSON\ObjectID($id);
        }
        return $this->collection->findOne(['_id' => $id]);
    }

    public function removeById($id)
    {
        if(is_string($id)) {
            $id = new \MongoDB\BSON\ObjectID($id);
        }
        return $this->collection->deleteOne(['_id' => $id]);
    }
}