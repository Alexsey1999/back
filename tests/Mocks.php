<?php

class Mocks 
{

    const ROOT_HOST = 'http://senler.loc:3005';

    public static function getAdminReferer()
    {
        $params = [
            'vk_access_token_settings' => 'docs',
            'vk_app_id' => 6747989,
            'vk_are_notifications_enabled' => 0,
            'vk_group_id' => 168143554,
            'vk_is_app_user' => 1,
            'vk_is_favorite' => 0,
            'vk_language' => 'ru',
            'vk_platform' => 'mobile_web',
            'vk_ref' => 'other',
            'vk_user_id' => '4871362',
            'vk_viewer_group_role' => 'admin',
            'sign' => 'U8DXQDFiIfXEnlS83wSZlzWEO6GNCd-L5lgZ8jNHB8A'
        ];

        return self::ROOT_HOST . '/?' . http_build_query($params);
    }

    public static function getFakeAdminReferer()
    {
        $params = [
            'vk_access_token_settings' => 'docs',
            'vk_app_id' => 6747989,
            'vk_are_notifications_enabled' => 0,
            'vk_group_id' => 999999999,
            'vk_is_app_user' => 1,
            'vk_is_favorite' => 0,
            'vk_language' => 'ru',
            'vk_platform' => 'mobile_web',
            'vk_ref' => 'other',
            'vk_user_id' => '4871362',
            'vk_viewer_group_role' => 'admin',
            'sign' => 'U8DXQDFiIfXEnlS83wSZlzWEO6GNCd-L5lgZ8jNHB8A'
        ];

        return self::ROOT_HOST . '/?' . http_build_query($params);
    }

    public static function getFakeReferer()
    {
        // Обычный пользователь пытается сделать запрос от имени этой же группы
        // Но подменил значение "vk_viewer_group_role" на "admin"
        // В таком случае подпись запроса не совпадет со сгенерированной при авторизации
        // И нет возможности сгенерировать новую подпись, так как нет secret ключа
        $params = [
            "vk_access_token_settings" => "notify",
            "vk_app_id" => "6747989",
            "vk_are_notifications_enabled" => "0",
            "vk_group_id" => "168143554",
            "vk_is_app_user" => "1",
            "vk_is_favorite" => "0",
            "vk_language" => "ru",
            "vk_platform" => "desktop_web",
            "vk_ref" => "other",
            "vk_user_id" => "574579599",
            // "vk_viewer_group_role" => "member",
            "vk_viewer_group_role" => "admin",
            "sign" => "2BLQ-ZqzhUb4ZSaYY2K8QAaKMRQia9xltBfRaYVRCDE"
        ];

        return self::ROOT_HOST . '/?' . http_build_query($params);
    }

    public static function getTestGroupBody() {
        return [
            'vk_group_id' => 168143554
        ];
    }

    public static function getParams()
    {
        return [
            'vk_access_token_settings' => 'docs',
            'vk_app_id' => 6747989,
            'vk_are_notifications_enabled' => 0,
            'vk_group_id' => 168143554,
            'vk_is_app_user' => 1,
            'vk_is_favorite' => 0,
            'vk_language' => 'ru',
            'vk_platform' => 'mobile_web',
            'vk_ref' => 'other',
            'vk_user_id' => '4871362',
            'vk_viewer_group_role' => 'admin',
            'sign' => 'U8DXQDFiIfXEnlS83wSZlzWEO6GNCd-L5lgZ8jNHB8A'
        ];
    }

    public static function getFakeParams()
    {
        return [
            'vk_access_token_settings' => 'docs',
            'vk_app_id' => 6747989,
            'vk_are_notifications_enabled' => 0,
            'vk_group_id' => 999999999,
            'vk_is_app_user' => 1,
            'vk_is_favorite' => 0,
            'vk_language' => 'ru',
            'vk_platform' => 'mobile_web',
            'vk_ref' => 'other',
            'vk_user_id' => '4871362',
            'vk_viewer_group_role' => 'admin',
            'sign' => 'U8DXQDFiIfXEnlS83wSZlzWEO6GNCd-L5lgZ8jNHB8A'
        ];
    }

    public static function getPageHitData()
    {
        return [
            "vk_group_id" => 168143554,
            "vk_user_id" => 574579599,
            "vk_user_role" => "member",
            "vk_ref" => "other",
            "vk_platform" => "desktop_web",
            "page_id" => "5f9a6e2bcce1ad6f9e19287c",
            "time" => 1605095804,
            "hit_id" => "453dc985126c016d5257638cc6c1af5c"
        ];
    }

    public static function getPageGoalData()
    {
        return [
            "hit_id" => "453dc985126c016d5257638cc6c1af5c",
            "type" => "lead",
            "vk_group_id" => 168143554,
            "vk_user_id" => 574579599,
            "vk_user_role" => "member",
            "vk_ref" => "other",
            "vk_platform" => "desktop_web",
            "button_id" => "5f9a6e2fa74db90d5547f0090",
            "block_id" => "5faa60ff8411a434df6eaae8",
            "page_id" => "5f9a6e2bcce1ad6f9e19287c"
        ];
    }

    public static function getSaveLeadData()
    {
        return [
            "phone_number" => "79151468694",
            "sign" => "WlceMdVivJYWrYdsYhaJuwKzKUdJGAbkUgTkqnEpSBI",
            "is_verified" => true,
            "page_id" => "5f9a6e2bcce1ad6f9e19287c",
            "lead_admin" => 223323
        ];
    }
}

?>