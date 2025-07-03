<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 只处理JSON响应
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);

            // 如果响应已经有标准格式，直接返回
            if (isset($data['success'])) {
                return $response;
            }

            // 标准化响应格式
            $standardData = [
                'success' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300,
                'data' => $data,
                'message' => $this->getStatusMessage($response->getStatusCode()),
                'timestamp' => time(),
                'version' => 'v1',
            ];

            $response->setData($standardData);
        }

        // 添加CORS头
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        return $response;
    }

    /**
     * 根据状态码获取默认消息
     *
     * @param int $statusCode
     * @return string
     */
    protected function getStatusMessage(int $statusCode): string
    {
        return match ($statusCode) {
            200 => 'Success',
            201 => 'Created',
            204 => 'No Content',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Validation Error',
            500 => 'Internal Server Error',
            default => 'Unknown Status',
        };
    }
}
