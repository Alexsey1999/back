<?php

namespace App\Widgets\Models;

use App\Widgets\Models\BaseWidget;

class TextWidget extends BaseWidget
{
    public function updateBody(array $code): void
    {
        $this->code['title'] = htmlspecialchars($code['title'], ENT_NOQUOTES);
        $this->code['title_url'] = htmlspecialchars($code['title_url'], ENT_NOQUOTES);
        $this->code['text'] = htmlspecialchars($code['text'], ENT_NOQUOTES);
        $this->code['descr'] = htmlspecialchars($code['descr'], ENT_NOQUOTES);
        $this->code['more'] = htmlspecialchars($code['more'], ENT_NOQUOTES);
        $this->code['more_url'] = htmlspecialchars($code['more_url'], ENT_NOQUOTES);
    }
}