<?php

namespace App\Workers\PageBlock;

class ImageReplacer
{
    private string $pattern;
    private string $replacement;

    /**
     * ImageReplacer constructor.
     * @param string $pattern
     * @param string $replacement
     */
    public function __construct(string $pattern, string $replacement)
    {
        $this->pattern = $pattern;
        $this->replacement = $replacement;
    }

    /**
     * @param array $page_block
     * @return array
     */
    public function replace(array $page_block): array
    {
        switch ($page_block['sub_type']) {
            case 'image_base':
                $replaced_page_block = $this->replaceGallery($page_block);
                break;

            case 'image_single':
                $replaced_page_block = $this->replaceSingle($page_block);
                break;

            case 'cover_base':
                $replaced_page_block = $this->replaceCover($page_block);
                break;

            case 'products_base':
                $replaced_page_block = $this->replaceProduct($page_block);
                break;

            default:
                $replaced_page_block = $page_block;
                break;
        }

        return $replaced_page_block;
    }

    /**
     * @param array $page_block
     * @return array
     */
    private function replaceGallery(array $page_block): array
    {
        $block = $page_block;
        foreach ($block['items'] as &$item) {
            if (preg_match($this->pattern, $item['url'])) {
                $item['url'] = $this->replacement;
            }
        }

        return $block;
    }

    /**
     * @param array $page_block
     * @return array
     */
    private function replaceSingle(array $page_block): array
    {
        if (preg_match($this->pattern, $page_block['content']['desktop_img'])) {
            $page_block['content']['desktop_img'] = $this->replacement;
        }
        if (preg_match($this->pattern, $page_block['content']['mobile_img'])) {
            $page_block['content']['mobile_img'] = $this->replacement;
        }

        return $page_block;
    }

    /**
     * @param array $page_block
     * @return array
     */
    private function replaceCover(array $page_block): array
    {
        if (preg_match($this->pattern, $page_block['background']['url'])) {
            $page_block['background']['url'] = $this->replacement;
        }
        return $page_block;
    }

    /**
     * @param array $page_block
     * @return array
     */
    private function replaceProduct(array $page_block): array
    {
        foreach ($page_block['items'] as &$item) {
            if (preg_match($this->pattern, $item['img']['url'])) {
                $item['img']['url'] = $this->replacement;
            }
        }
        return $page_block;
    }
}
