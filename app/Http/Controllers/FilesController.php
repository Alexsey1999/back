<?php

namespace App\Http\Controllers;

use Error;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Workers\VkApi;
use App\Workers\ImgHelper;
use App\Interfaces\LoggerInterface;
use App\Interfaces\AppConfigProviderInterface;

class FilesController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private VkApi $vkApi;
    private LoggerInterface $logger;
    private AppConfigProviderInterface $globalConfigProvider;

    /**
     * vkApi - инжектится через сервис локатор
     */
    public function __construct(
        VkApi $vkApi, 
        LoggerInterface $logger,
        AppConfigProviderInterface $globalConfigProvider
    )
    {
        $this->vkApi = $vkApi;
        $this->logger = $logger;
        $this->globalConfigProvider = $globalConfigProvider;
    }


    public function uploadImage(Request $request): JsonResponse
    {

        try {
            $params = json_decode($request->input('params'), true);
            $size = $request->input('size');
            $token =  $request->input('token');

            if (!$token) {
                throw new BadRequestHttpException("Неверные параметры запроса");
            }

            $image_size = null;

            if ($size) {
                $image_size = explode('x', $size);

                if (!is_array($image_size) || count($image_size) < 2) {
                    throw new BadRequestHttpException("Некорректный размер изображения");
                }
            }

            $validationResult = $this->validateImageAction($request);

            if ($validationResult !== true) {
                return $this->jsonResponse([
                    'result' => 'error',
                    'response' => [],
                    'errors' => $validationResult
                ]);
            }

            $fileTempPath = base_path() . '/tmp';

            if (!\file_exists($fileTempPath)) {
                mkdir($fileTempPath, 0755);
            }

            $fileTempName = uniqid() . '.' . $request->file('image')->getClientOriginalExtension();

            $request->file('image')->move($fileTempPath, $fileTempName);

            $file_path = $fileTempPath . '/' . $fileTempName;

            if ($image_size) {
                try {
                    $file_path = ImgHelper::getCropedImage($file_path, (int)$image_size[0], (int)$image_size[1]);
                } catch (Error $e) {
                    unlink($file_path);
                    return $this->jsonResponse([
                        'result' => 'error',
                        'response' => [],
                        'errors' => [
                            'image' => $e->getMessage()
                        ]
                    ]);
                }  
            }

            $this->vkApi->setGroupToken($token);

            $result = $this->vkApi->uploadLandingImage(
                $file_path, 
                intval($params['vk_user_id'])
            );

            if (isset($result['filename'])) {
                // unlink($file_path);
                return $this->jsonResponse([
                    'result' => 'success',
                    'data' => $result
                ]);

            } else if(isset($result['error'])) {
                $error_message = '';
                $error_code = 0;

                if (isset($result['response']['error']) && isset($result['response']['error']['error_code'])) {
                    $error_message = $error_message . $result['response']['error']['error_code'] . ' ';
                    $error_code = $result['response']['error']['error_code'];
                }

                if (isset($result['response']['error']) && isset($result['response']['error']['error_msg'])) {
                    $error_message .= $result['response']['error']['error_msg'];
                }

                try {
                    $this->logger->save([
                        'vk_group_id' => $params['vk_group_id'],
                        'data' => json_encode([
                            'file' => 'FilesController.php',
                            'error_type' => 'image_upload_error',
                            'message' => $error_message,
                            'code' => $error_code
                        ]),
                        'params' => $params
                    ]);
                } catch (\Throwable $e) {
                    
                }
                unlink($file_path);
                return $this->jsonResponse([
                    'result' => 'error',
                    'response' => [],
                    'message' => $error_message,
                    'error_code' => $error_code
                ]);
            } else {
                unlink($file_path);
                return $this->jsonResponse([
                    'result' => 'error',
                    'message' => 'Неизвестная ошибка при загрузке изображения ' . json_encode($result, JSON_UNESCAPED_UNICODE)
                ]);
            }
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'result' => 'error',
                'message' => $e->getMessage(),
                'test' => $e->getLine(),
                'test2' => $e->getFile()
            ]);
        }

        
    }


    /**
     * Загрузка картинки в ВК через API
     */
    public function image(Request $request): JsonResponse
    {   

        $image_type = $request->input('image_type');
        $vk_group_id = $request->input('vk_group_id');
        $params = $request->input('params');
        $token = $request->input('token');
        $sizes = explode('x', $image_type);
        $size_count = count($sizes);

        if ($size_count !== 2) {
            throw new BadRequestHttpException("Size is incorrect $image_type");
        }

        if (!$token) {
            throw new BadRequestHttpException("Неверные параметры запроса");
        }

        $validationResult = $this->validateImageAction($request);

        if ($validationResult !== true) {
            return $this->jsonResponse([
                'result' => 'error',
                'response' => [],
                'errors' => $validationResult
            ]);
        }

        $fileTempPath = base_path() . '/tmp';

        if (!\file_exists($fileTempPath)) {
            mkdir($fileTempPath, 0755);
        }

        $fileTempName = uniqid() . '.' . $request->file('image')->getClientOriginalExtension();

        $request->file('image')->move($fileTempPath, $fileTempName);

        $filePath = $fileTempPath . '/' . $fileTempName;

        $resizedImg = '';
        
        try {
            $resizedImg = ImgHelper::getCropedImage($filePath, (int)$sizes[0] * 3, (int)$sizes[1] * 3);
        } catch (Error $e) {
            unlink($filePath);
            return $this->jsonResponse([
                'result' => 'error',
                'response' => [],
                'errors' => [
                    'image' => $e->getMessage()
                ]
            ]);
        }

        $this->vkApi->setGroupToken($token);
        $result = $this->vkApi->saveGroupWidgetImage($resizedImg, $image_type);

        if (isset($result['error'])) {

            $error_message = '';
            $error_code = 0;

            if (isset($result['response']['error']) && isset($result['response']['error']['error_code'])) {
                $error_message = $error_message . $result['response']['error']['error_code'] . ' ';
                $error_code = $result['response']['error']['error_code'];
            }

            if (isset($result['response']['error']) && isset($result['response']['error']['error_msg'])) {
                $error_message .= $result['response']['error']['error_msg'];
            }

            try {
                $this->logger->save([
                    'vk_group_id' => (int) $vk_group_id,
                    'data' => json_encode([
                        'file' => 'FilesController.php',
                        'error_type' => 'image_upload_error',
                        'message' => $error_message,
                        'code' => $error_code
                    ]),
                    'params' => $params
                ]);
            } catch (\Throwable $e) {
                
            }

            $resposne = [
                'result' => 'error',
                'response' => [],
                'errors' => [
                    'image' => [
                        $error_message
                    ],
                ],
                'error_code' => $error_code
            ];
        } else {
            $resposne = [
                'result' => 'success',
                'app_id' => $this->globalConfigProvider->getVKAppId(),
                'response' => $result['response'],
                'errors' => []
            ];
        }
            
        unlink($filePath);

        return $this->jsonResponse($resposne);
    }


    /**
     * 
     */
    public function document(Request $request): JsonResponse 
    {
        $image_type = $request->input('image_type');
        $ext = $request->input('ext');
        $url = $request->input('url');
        $sizes = explode('x', $image_type);
        $size_count = count($sizes);
        $params = $request->input('params');
        $vk_group_id = $request->input('vk_group_id');
        $token = $request->input('token');

        if ($size_count !== 2) {
            throw new BadRequestHttpException("Size is incorrect $image_type");
        }

        if (!$token) {
            throw new BadRequestHttpException("Неверные параметры запроса");
        }

        $validationResult = $this->validateDocumentAction($request);

        if ($validationResult !== true) {
            return $this->jsonResponse([
                'result' => 'error',
                'response' => [],
                'errors' => $validationResult
            ]);
        }

        $fileTempPath = base_path() . '/tmp';

        if (!\file_exists($fileTempPath)) {
            mkdir($fileTempPath, 0755);
        }

        $fileTempName = uniqid() . '.' . $ext;
        $filePath = $fileTempPath . '/' . $fileTempName;

        file_put_contents($filePath, file_get_contents($url));

        if (false === $this->checkFile($filePath)) {
            unlink($filePath);
            return $this->jsonResponse([
                'result' => 'error',
                'response' => [],
                'errors' => [
                    'document' => "Invalid file format"
                ]
            ]);
        };

        $resizedImg = '';

        try {
            $resizedImg = ImgHelper::getCropedImage($filePath, (int)$sizes[0] * 3, (int)$sizes[1] * 3);
        } catch (Error $e) {
            unlink($filePath);
            return $this->jsonResponse([
                'result' => 'error',
                'response' => [],
                'errors' => [
                    $e->getMessage()
                ]
            ]);
        }

        $this->vkApi->setGroupToken($token);
        $result = $this->vkApi->saveGroupWidgetImage($resizedImg, $image_type);

        if (isset($result['error'])) {

            $error_message = '';
            $error_code = 0;

            if (isset($result['response']['error']) && isset($result['response']['error']['error_code'])) {
                $error_message = $error_message . $result['response']['error']['error_code'] . ' ';
                $error_code = $result['response']['error']['error_code'];
            }

            if (isset($result['response']['error']) && isset($result['response']['error']['error_msg'])) {
                $error_message .= $result['response']['error']['error_msg'];
            }

            try {
                $this->logger->save([
                    'vk_group_id' => (int) $vk_group_id,
                    'data' => json_encode([
                        'file' => 'FilesController.php',
                        'error_type' => 'document_upload_error',
                        'message' => $error_message,
                        'code' => $error_code
                    ]),
                    'params' => $params
                ]);
            } catch (\Throwable $e) {}

            $resposne = [
                'result' => 'error',
                'response' => [],
                'errors' => [
                    0 => $error_message,
                ],
                'error_code' => $error_code
            ];
        } else {
            $resposne = [
                'result' => 'success',
                'response' => $result['response'],
                'errors' => []
            ];
        }
            
        unlink($filePath);

        return $this->jsonResponse($resposne);
    }

    /**
     * Validate image upload data
     */
    protected function validateImageAction(Request $request) 
    {
        try {

            $messages = [
                'image.image' => 'Разрешены изображения в формате JPG или PNG',
                'image.mimes' => 'Разрешены изображения в формате JPG или PNG'
            ];

            $this->validate($request, [
                'image' => 'image',
                'image' => 'mimes:jpeg,png,gif'
            ], $messages);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $e->errors();
        }

        return true;
    }

    protected function validateDocumentAction(Request $request)
    {
        try {

            $messages = [
                'ext.in' => 'Разрешены изображения в формате JPG или PNG',
                'url.url' => 'Разрешены ссылки только внутри vk.com',
                'url.regrex' => 'Разрешены ссылки только внутри vk.com'
            ];

            $this->validate($request, [
                'ext' => 'in:jpeg,png,gif,jpg',
                'url' => 'url',
                'url' => 'regex:/^https:\/\/vk\.com/'
            ], $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $e->errors();
        }

        return true;
    }

    protected function checkFile(string $file_path)
    {
        $mime = mime_content_type($file_path);
        return in_array($mime, ['image/jpeg', 'image/png', 'image/gif']);
    }
}
