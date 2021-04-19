<?php

namespace App\Widgets\Models;

use App\Widgets\Models\BaseWidget;

class CompactListWidget extends BaseWidget
{
    public function updateBody(array $code): void
    {

        $this->code['title'] = htmlspecialchars($code['title'], ENT_NOQUOTES);
        $this->code['title_url'] = htmlspecialchars($code['title_url'], ENT_NOQUOTES);
        $this->code['more'] = htmlspecialchars($code['more'], ENT_NOQUOTES);
        $this->code['more_url'] = htmlspecialchars($code['more_url'], ENT_NOQUOTES);
        $this->code['rows'] = $code['rows'];

        if (isset($code['has_images'])) {
            $this->code['has_images'] = $code['has_images'];
        }

        if (isset($code['has_buttons'])) {
            $this->code['has_buttons'] = $code['has_buttons'];
        }

        if (isset($code['has_text'])) {
            $this->code['has_text'] = $code['has_text'];
        }
    }
}