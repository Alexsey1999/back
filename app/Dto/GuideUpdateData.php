<?php

namespace App\Dto;

class GuideUpdateData
{
    public $seen_context_tooltip;
    public $seen_title_tooltip;
    public $visited_initial_settings;

    public function __construct(array $params = [])
    {
        $this->seen_context_tooltip = $params['seen_context_tooltip'] ? $params['seen_context_tooltip'] : true;
        $this->seen_title_tooltip = $params['seen_title_tooltip'] ? $params['seen_title_tooltip'] : true;
        $this->visited_initial_settings = $params['visited_initial_settings'] ? $params['visited_initial_settings'] : true;
    }
}