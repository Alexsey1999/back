<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
*/

/**
 * Create a new widget document
 */
$router->post('/create', 'WidgetController@create');


/**
 * Get all widgets by given group id
 */
$router->post('/get-all/{group_id}', 'WidgetController@getAll');


/**
 * Get one widget by given ID
 */
$router->post('/get/{widget_id}', 'WidgetController@get');


/**
 * Update widget body by given ID
 */
$router->post('/update/{widget_id}', 'WidgetController@update');


/**
 * Update widget audience by given ID
 */
$router->post('/update-audience/{widget_id}', 'WidgetController@updateAudience');


/**
 * Update widget name by given ID
 */
$router->post('/update-name/{widget_id}', 'WidgetController@updateName');


/**
 * Discard unpublished changes
 */
$router->post('/discard/{widget_id}', 'WidgetController@discard');


/**
 * Delete widget by given ID
 */
$router->post('/delete', 'WidgetController@delete');


/**
 * Sort widgets in group collection in given order
 */
$router->post('/sort', 'WidgetController@sort');



/**
 * Publish widgets
 */

$router->post('/publish', 'WidgetController@publish');

/**
 * Clone widget
 */
$router->post('/clone', 'WidgetController@clone');

$router->post('/widgets/copy/community', 'WidgetController@copyCommunity');

$router->post('/widgets/share', 'WidgetController@share');

/**
 * Upload image via VK api
 */

$router->post('/image', 'FilesController@image');


/**
 * Upload document as image via VK api
 */
$router->post('/document', 'FilesController@document');


/**
 * Guide api actions
 */

// Get guide for grouop
$router->post('/guide/get/{group_id}', 'GuideController@get');

// Update guide data for group
$router->post('/guide/update/{group_id}', 'GuideController@update');

// Delete guide data for group
$router->post('/guide/delete/{group_id}', 'GuideController@delete');


/**
 * Share api actions
 */
$router->post('/share/create-collection', 'ShareController@createCollection');

$router->post('/share/get-collection', 'ShareController@getCollection');

$router->post('/share/copy-collection', 'ShareController@copyCollection');


/**
 * Logger api
 */

 $router->post('/log/error/{group_id}', 'LoggerController@error');


 /**
  * VKApp subscriptions proxy api
  */
$router->post('/ajax/vkapp/GetSubscriptions', 'VkAppSubscriptionsController@getSubscriptions');
$router->post('/ajax/vkapp/GetAdminSubscriptions', 'VkAppSubscriptionsController@getAdminSubscriptions');
$router->post('/ajax/vkapp/Subscribe', 'VkAppSubscriptionsController@subscribe');
$router->post('/ajax/vkapp/UnSubscribe', 'VkAppSubscriptionsController@unSubscribe');
$router->post('/ajax/vkapp/UnSubscribeAll', 'VkAppSubscriptionsController@unSubscribeAll');
$router->post('/ajax/vkapp/CreateOrder', 'VkAppSubscriptionsController@createOrder');
$router->post('/ajax/vkapp/CheckOrder', 'VkAppSubscriptionsController@CheckOrder');
$router->post('/ajax/vkapp/GetPromoPageData', 'VkAppSubscriptionsController@getPromoPageData');
$router->post('/ajax/vkapp/AddToBot', 'VkAppSubscriptionsController@addToBot');

/**
 * VKApp settings proxy api
 */
$router->post('/ajax/vkapp/SaveAppSettings', 'VkAppSettingsController@saveAppSettings');
$router->post('/ajax/vkapp/SaveAppMetrics', 'VkAppSettingsController@saveAppMetrics');
$router->post('/ajax/vkapp/SaveAppBannerFile', 'VkAppSettingsController@saveAppBannerFile');
$router->post('/ajax/vkapp/UpdateSubscriptionsList', 'VkAppSettingsController@updateSubscriptionsList');


/*
|--------------------------------------------------------------------------
| Pages api routes
|--------------------------------------------------------------------------
|
*/

$router->post('/pages/create', 'PagesController@create');
$router->post('/pages/get', 'PagesController@get');
$router->post('/pages/get-one', 'PagesController@get');
$router->post('/pages/get-all', 'PagesController@getAll');
$router->post('/pages/get-list', 'PagesController@getList');
$router->post('/pages/get-list-external', 'PagesController@getList');
$router->post('/pages/change-status', 'PagesController@changeStatus');
$router->post('/pages/rename', 'PagesController@rename');
$router->post('/pages/copy', 'PagesController@copy');
$router->post('/pages/copy-to-other-group', 'PagesController@copyToGroup');
$router->post('/pages/delete', 'PagesController@delete');
$router->post('/pages/add-block', 'PagesController@addBlock');
$router->post('/pages/add-template', 'PagesController@addTemplate');
$router->post('/pages/delete-block', 'PagesController@deleteBlock');
$router->post('/pages/insert-block', 'PagesController@insertBlock');
$router->post('/pages/sort-blocks', 'PagesController@sortBlocks');
$router->post('/pages/update-block', 'PagesController@updateBlock');
$router->post('/pages/publish', 'PagesController@publish');
$router->post('/pages/save-state', 'PagesController@saveState');
$router->post('/pages/save-lead', 'PagesController@saveLead');
$router->post('/pages/save-lead-trigger', 'PagesController@saveLeadTrigger');

$router->post('/stat/get', 'PageStatisticController@get');
$router->post('/stat/goal', 'PageStatisticController@goal');
$router->post('/stat/hit', 'PageStatisticController@hit');

/*
|--------------------------------------------------------------------------
| Files api routes
|--------------------------------------------------------------------------
|
*/
$router->post('/files/image', 'FilesController@uploadImage');



$router->post('/test/test', 'TestController@test');
