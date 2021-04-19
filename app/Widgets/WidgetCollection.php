<?php

namespace App\Widgets;

use Iterator;
use App\Widgets\Models\BaseWidget;

class WidgetCollection implements Iterator
{
    private $storage = [];

    public function set(BaseWidget $widget)
    {
        $this->storage[] = $widget;
    }

    public function get($key)
    {
        return $this->storage[$key];
    }

    public function current()
    {
        return current($this->storage);
    }

    public function key()
    {
        return key($this->storage);
    }

    public function next(): void
    {
        next($this->storage);
    }

    public function rewind(): void
    {
        reset($this->storage);
    }

    public function valid(): bool
    {
        return null !== key($this->storage);
    }

    public function toArray(): array
    {
        $res = [];

        foreach($this->storage as $widget) {
            $res[] = $widget->toArray();
        }

        return $res;
    }


}