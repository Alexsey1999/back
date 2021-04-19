<?php

namespace App\Widgets;

use App\Widgets\Models\BaseWidget;
use App\Widgets\Models\CompactListWidget;
use App\Widgets\Models\TextWidget;
use App\Widgets\Models\TilesWidget;
use App\Widgets\Models\ListWidget;
use App\Widgets\Models\CoverListWidget;
use App\Widgets\Models\TableWidget;

use Exception;

class WidgetFactory
{

    const TYPE_TEXT         = 'text';
    const TYPE_LIST         = 'list';
    const TYPE_TABLE        = 'table';
    const TYPE_TILES        = 'tiles';
    const TYPE_COMPACT_LIST = 'compact_list';
    const TYPE_COVER_LIST   = 'cover_list';
    const TYPE_MATCH        = 'match';
    const TYPE_MATCHES      = 'matches';
    const TYPE_DONATION     = 'donation';

    public function getTypes(): array
    {
        return [
            self::TYPE_TEXT,
            self::TYPE_LIST,
            self::TYPE_TABLE,
            self::TYPE_TILES,
            self::TYPE_COMPACT_LIST,
            self::TYPE_COVER_LIST,
            self::TYPE_MATCH,
            self::TYPE_MATCHES,
            self::TYPE_DONATION 
        ];
    }

    public function createWidget(string $type): BaseWidget 
    {
        
        if (array_search($type, self::getTypes()) === false) {
            throw new Exception('No such type of widget');
        }

        switch($type)
        {
            case self::TYPE_TEXT:
                return self::createTextWidget();
            case self::TYPE_TILES:
                return self::createTilesWidget();
            case self::TYPE_LIST:
                return self::createListWidget();
            case self::TYPE_COVER_LIST:
                return self::createCoverListWidget();
            case self::TYPE_COMPACT_LIST:
                return self::createCompactListWidget();
            case self::TYPE_TABLE:
                return self::createTableWidget();
        }

    }

    protected function createTextWidget(): TextWidget
    {
        $widget = new TextWidget([
            'type' => self::TYPE_TEXT
        ]);

        return $widget;
    }

    protected function createTilesWidget(): TilesWidget
    {
        $widget = new TilesWidget([
            'type' => self::TYPE_TILES
        ]);

        return $widget;
    }

    protected function createListWidget(): ListWidget
    {
        $widget = new ListWidget([
            'type' => self::TYPE_LIST
        ]);

        return $widget;
    }

    protected function createCoverListWidget(): CoverListWidget
    {
        $widget = new CoverListWidget([
            'type' => self::TYPE_COVER_LIST
        ]);

        return $widget;
    }

    protected function createCompactListWidget(): CompactListWidget
    {
        $widget = new CompactListWidget([
            'type' => self::TYPE_COMPACT_LIST
        ]);

        return $widget;
    }

    protected function createTableWidget(): TableWidget
    {
        $widget = new TableWidget([
            'type' => self::TYPE_TABLE
        ]);

        return $widget;
    }
}