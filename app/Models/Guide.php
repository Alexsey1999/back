<?php

namespace App\Models;

use App\Dto\GuideUpdateData;
use DomainException;

class Guide 
{

    public $seen_context_tooltip;
    public $seen_title_tooltip;
    public $visited_initial_settings;
    public $group_id;

    public function __construct($data)
    {
        if (!isset($data['group_id'])) {
            throw new DomainException('Guide - group_id not provided');
        }

        $this->group_id = (int) $data['group_id'];
        $this->seen_context_tooltip = isset($data['seen_context_tooltip']) ? $data['seen_context_tooltip'] : true;
        $this->seen_title_tooltip = isset($data['seen_title_tooltip']) ? $data['seen_title_tooltip'] : true;
        $this->visited_initial_settings = isset($data['visited_initial_settings']) ? $data['visited_initial_settings'] : true;

    }

    public function update($id, GuideUpdateData $data)
    {
        $this->seen_context_tooltip = $data->seen_context_tooltip;
        $this->seen_title_tooltip = $data->seen_title_tooltip;
        $this->visited_initial_settings = $data->visited_initial_settings;
    }

    public function delete($id)
    {

    }

    public function toArray(): array
    {
        return [
            'group_id' => $this->group_id,
            'seen_context_tooltip' => $this->seen_context_tooltip,
            'seen_title_tooltip' => $this->seen_title_tooltip,
            'visited_initial_settings' => $this->visited_initial_settings
        ];
    }
}