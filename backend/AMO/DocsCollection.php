<?php


namespace AMO;


class DocsCollection
{
    private $docsArray;
    private $docsModels;

    public function __construct(array $docsArray = []) {
        $this->docsArray = $docsArray;
        $this->docsModels = array_map(function ($docArray) {
            return Document::makeFromArray($docArray);
        }, $docsArray);
    }

    public function getDocsForUser($userId) {
        $docsOfUser = array_filter($this->docsModels, function ($docModel) use ($userId) {
            return $docModel->getUserId() == $userId;
        });

        $docsAsArray = array_map(function ($docModel) {
            return $docModel->asArray();
        }, $docsOfUser);

        return array_values($docsAsArray);
    }

    public function getDocsForGroup($groupId) {
        $docsOfGroup = array_filter($this->docsModels, function ($docModel) use ($groupId) {
            return $docModel->getGroupId() == $groupId;
        });

        $docsAsArray = array_map(function ($docModel) {
            return $docModel->asArray();
        }, $docsOfGroup);

        return array_values($docsAsArray);
    }

    public function getDoc($index = false) {
        return $index === false ? $this->docsModels : $this->docsModels[$index];
    }

    public static function from(array $docsArray) {
        return new self($docsArray);
    }
}