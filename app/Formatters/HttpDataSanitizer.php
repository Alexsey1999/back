<?php

namespace App\Formatters;

class HttpDataSanitizer 
{   

    /**
     * Очистка данных блоков
     */
    public function clearBlockData(array $block_data): array
    {

        if (isset($block_data['content'])) {
            if (isset($block_data['content']['title'])) {
                $block_data['content']['title'] = strip_tags($block_data['content']['title']);
            }
            if (isset($block_data['content']['text'])) {
                $block_data['content']['text'] = strip_tags($block_data['content']['text']);
            }
            if (isset($block_data['content']['desktop_img'])) {
                $block_data['content']['desktop_img'] = strip_tags($block_data['content']['desktop_img']);
            }
            if (isset($block_data['content']['mobile_img'])) {
                $block_data['content']['mobile_img'] = strip_tags($block_data['content']['mobile_img']);
            }

            /**
             * Ссылка для баннера
             */
            if (isset($block_data['content']['address_url'])) {
                $block_data['content']['address_url'] = strip_tags($block_data['content']['address_url']);
            }

            /**
             * Дата и время истечения для таймера
             */
            if (isset($block_data['content']['datetime_end'])) {
                $block_data['content']['datetime_end'] = strip_tags($block_data['content']['datetime_end']);
            }
        }

        if (isset($block_data['meta'])) {
            if (isset($block_data['meta']['alignment'])) {
                $block_data['meta']['alignment'] = strip_tags($block_data['meta']['alignment']);
            }
        }

        if (isset($block_data['meta'])) {
            if (isset($block_data['meta']['alignment'])) {
                $block_data['meta']['alignment'] = strip_tags($block_data['meta']['alignment']);
            }
        }

        if (isset($block_data['background'])) {
            $block_data['background']['url'] = strip_tags($block_data['background']['url']);
        }

        if (isset($block_data['video'])) {
            $block_data['video']['url'] = strip_tags($block_data['video']['url']);
            $block_data['video']['autoplay'] = intval($block_data['video']['autoplay']);
            $block_data['video']['disable_audio'] = intval($block_data['video']['disable_audio']);
            $block_data['video']['repeat'] = intval($block_data['video']['repeat']);
        }

        if (isset($block_data['vk_group_id'])) {
            $block_data['vk_group_id'] = intval($block_data['vk_group_id']);
        }

        if (isset($block_data['items'])) {
            foreach($block_data['items'] as $index => $item) {
                if (isset($block_data['items'][$index]['url'])) {
                    $block_data['items'][$index]['url'] = strip_tags($block_data['items'][$index]['url']);
                }

                if (isset($block_data['items'][$index]['title'])) {
                    $block_data['items'][$index]['title'] = strip_tags($block_data['items'][$index]['title']);
                }

                if (isset($item['text'])) {
                    $block_data['items'][$index]['text'] = strip_tags($block_data['items'][$index]['text']);
                }

                if (isset($item['category'])) {
                    $block_data['items'][$index]['category'] = strip_tags($block_data['items'][$index]['category']);
                }

                if (isset($item['price'])) {
                    $block_data['items'][$index]['price'] = strip_tags($block_data['items'][$index]['price']);
                }

                if (isset($item['price_old'])) {
                    $block_data['items'][$index]['price_old'] = strip_tags($block_data['items'][$index]['price_old']);
                }

                if (isset($item['name'])) {
                    $block_data['items'][$index]['name'] = strip_tags($block_data['items'][$index]['name']);
                }

                if (isset($item['button'])) {
                    $block_data['items'][$index]['button']['url'] = strip_tags($block_data['items'][$index]['button']['url']);
                    $block_data['items'][$index]['button']['text'] = strip_tags($block_data['items'][$index]['button']['text']);
                }

                if (isset($item['img'])) {
                    $block_data['items'][$index]['img']['url'] = strip_tags($block_data['items'][$index]['img']['url']);
                }
            }
        }

        if (isset($block_data['button'])) {
            if (isset($block_data['button']['text'])) {
                $block_data['button']['text'] = strip_tags($block_data['button']['text']);
            }
            if (isset($block_data['button']['url'])) {
                $block_data['button']['url'] = strip_tags($block_data['button']['url']);
            }
            if (isset($block_data['button']['action'])) {
                $block_data['button']['action'] = strip_tags($block_data['button']['action']);
            }
        }

        /**
         * Флаг - блок использует кнопку-действие
         */
        if (isset($block_data['has_button'])) {
            $block_data['has_button'] = (boolean) $block_data['has_button'];
        }

        /**
         * Флаг - блок использует заголовок и описание
         */
        if (isset($block_data['has_title'])) {
            $block_data['has_title'] = (boolean) $block_data['has_title'];
        }

        /**
         * Флаг - блок использует фоновое изображение
         */
        if (isset($block_data['has_background'])) {
            $block_data['has_background'] = (boolean) $block_data['has_background'];
        }

        return $block_data;
    }

    /**
     * Очистка данных при отправке заявки от пользователя
     * Данные берутся из вк, то есть пользователь ничего не вводит самостоятельно
     * Но очистить все равно нужно, так как запрос можно всега подделать и отправить любые данные
     * А заявки мы храним в бд и в перспективе где-нибудь выводим 
     */
    public function clearLeadData(array $lead_data): array
    {
        /**
         * Добавить необходимые поля если необходимо (email, адрес)
         */

        if (isset($lead_data['phone_number'])) {
            $lead_data['phone_number'] = strip_tags($lead_data['phone_number']);
        }

        if (isset($lead_data['page_id'])) {
            $lead_data['page_id'] = strip_tags($lead_data['page_id']);
        }

        if (isset($lead_data['lead_admin'])) {
            $lead_data['lead_admin'] = intval($lead_data['lead_admin']);
        }

        return $lead_data;
    }

    /**
     * Приведение массива значений к строковым значениям
     */
    public function arrayStringValues(array $values): array
    {
        return array_map(function ($item) {
            return (string)$item;
        }, $values);
    }
}