<?php

namespace App\Pages;

use Iterator;
use App\Pages\Page;

class PagesCollection implements Iterator
{
    private $storage = [];

    public function set(Page $page)
    {
        $this->storage[] = $page;
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

        foreach($this->storage as $page) {
            $res[] = $page->toArray();
        }

        return $res;
    }


}