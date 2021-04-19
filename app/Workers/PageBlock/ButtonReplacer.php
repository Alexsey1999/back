<?php

namespace App\Workers\PageBlock;

class ButtonReplacer
{
    private array $replacement_button;

    /**
     * ButtonReplacer constructor.
     * @param array $replacement_button
     */
    public function __construct(array $replacement_button)
    {
        $this->replacement_button = $replacement_button;
    }

    /**
     * @param array $page_block
     * @return array
     */
    public function replace(array $page_block): array
    {
        $sub_type = $page_block['sub_type'];
        
        $replaced_block = $page_block;

        if (in_array($sub_type, ['cover_base', 'button_base'])) {

            $replaced_block['button'] = $this->replaceButton($page_block['button']);

        } else if ($sub_type == 'products_base') {
            
            foreach ($replaced_block['items'] as &$item) {
                $item['button'] = $this->replaceButton($item['button']);
            }
        }

        return $replaced_block;
    }

    private function replaceButton(array $button): array
    {
        $new_button = array_merge($button, $this->replacement_button);
        return $new_button;
    }
}
