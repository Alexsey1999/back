<?php

namespace App\Widgets\Models;

use App\Traits\Copyable;

class BaseWidget
{

    use Copyable;
    
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    public $id;
    public $type;
    public $type_api;
    public $name;
    public $status;
    public $sort;
    public $group_id;
    public $code;
    public $audience;
    public $last_published_state;
    public $created;
    public $updated;

    protected $errors = [];

    public function __construct($config)
    {
        $this->type = $config['type'];
    }

    public function setBody(array $body)
    {
        $this->code = $body;
        return $this;
    }

    public function setName($name)
    {   
        $this->name = $name;
        return $this;
    }

    public function discard() 
    {
        $last_published_state = json_decode($this->last_published_state, true);
        $this->code = $last_published_state['code'];
        $this->audience = $last_published_state['audience'];
        return $this;
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function enable()
    {
        $this->status = self::STATUS_ACTIVE;
        return $this;
    }

    public function disable()
    {
        $this->status = self::STATUS_INACTIVE;
        return $this;
    }

    public function setUpdatedAt(int $time, int $vk_user_id)
    {
        $this->updated['user_id'] = $vk_user_id; 
        $this->updated['timestamp'] = $time;
        return $this;
    }

    public function setCreatedAt(int $time, int $vk_user_id)
    {
        $this->created['user_id'] = $vk_user_id; 
        $this->created['timestamp'] = $time;
        return $this;
    }

    public function create(array $data)
    {

        $this->status   = self::STATUS_INACTIVE;
        $this->group_id = (int) $data['group_id'];
        $this->name     = htmlspecialchars($data['name'], ENT_NOQUOTES);
        $this->type_api = htmlspecialchars($data['type_api']);
        $this->code     = [
            'title' => ''
        ];
        $this->audience = [
            
        ];

        $this->setUpdatedAt(time(), (int)$data['vk_user_id']);
        $this->setCreatedAt(time(), (int)$data['vk_user_id']);

    } 

    public function loadFromDocument($document): void
    {
        $this->id = $document['id'];
        $this->group_id = $document['group_id'];
        $this->sort = $document['sort'];
        $this->name = $document['name'];
        $this->type = $document['type'];
        $this->status = $document['status'];
        $this->type_api = $document['type_api'];
        $this->code = $document['code'];
        $this->audience = $document['audience'];
        $this->last_published_state = isset($document['last_published_state']) ? $document['last_published_state'] : '';
        $this->created = isset($document['created']) ? $document['created'] : [];
        $this->updated = isset($document['updated']) ? $document['updated'] : [];
    }

    /**
     * Set initial sort value in widget set by Group_id
     */
    public function setInitialSort($sortValue)
    {
        $this->sort = $sortValue;
        return $this;
    }


    public function getErrors()
    {
        return $this->errors;
    }

    public function toArray()
    {
        $reflect = new \ReflectionClass($this);
        $props   = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);

        $array = [];

        foreach($props as $prop)
        {
            $array[$prop->getName()] = $prop->getValue($this);
        }

        return $array;
    }

    public function updateAudience(array $audience)
    {
        $this->audience = $audience;
        return $this;
    }

    public function clone() 
    {
        $clone = new self([
            'type' => $this->type
        ]);

        $clone->status = self::STATUS_INACTIVE;
        $clone->name = $this->getCopyName($this->name);
        $clone->group_id = $this->group_id;
        $clone->type_api = $this->type_api;
        $clone->code = $this->code;
        $clone->audience = $this->audience;
        $clone->last_published_state = $this->last_published_state;
        $clone->created = $this->created;
        $clone->updated = $this->updated;

        return $clone;
    }

    public function cloneToCommunity(int $community_id)
    {
        $clone = new self([
            'type' => $this->type
        ]);

        $clone->status = self::STATUS_INACTIVE;
        $clone->name = $this->name;
        $clone->group_id = $community_id;
        $clone->type_api = $this->type_api;
        $clone->code = $this->code;
        $clone->audience = $this->audience;
        $clone->last_published_state = '';
        $clone->created = $this->created;
        $clone->updated = $this->updated;

        return $clone;
    }

}