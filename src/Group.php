<?php

declare(strict_types=1);
/**
 * This file is part of youdusdk/youdu-php.
 *
 * @link     https://github.com/youdusdk/youdu-php
 * @document https://github.com/youdusdk/youdu-php/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */
namespace YouduSdk\Youdu;

use YouduSdk\Youdu\Exceptions\ErrorCode;
use YouduSdk\Youdu\Exceptions\Exception;
use YouduSdk\Youdu\Http\ClientInterface;

class Group
{
    protected ClientInterface $client;

    protected Config $config;

    public function __construct(protected App $app)
    {
        $this->client = $app->client();
        $this->config = $app->config();
    }

    /**
     * 获取群列表.
     * @param int|string $userId
     */
    public function lists($userId = ''): array
    {
        $parameters = [];

        if ($userId) {
            $parameters['userId'] = $userId;
        }

        $resp = $this->client->get($this->app->buildUrl('/cgi/group/list'), $parameters);
        $decoded = json_decode($resp['body'], true, 512, JSON_THROW_ON_ERROR);

        if ($decoded['errcode'] !== 0) {
            throw new Exception($decoded['errmsg'], 1);
        }

        $decrypted = $this->config->decryptMsg($decoded['encrypt'] ?? '');

        return json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR)['groupList'] ?? [];
    }

    /**
     * 创建群.
     */
    public function create(string $name): int|string
    {
        $parameters = [
            'buin' => $this->config->getBuin(),
            'appId' => $this->config->getAppId(),
            'encrypt' => $this->config->encryptMsg(json_encode([
                'name' => $name,
            ], JSON_THROW_ON_ERROR)),
        ];

        $resp = $this->client->post($this->app->buildUrl('/cgi/group/create'), $parameters);

        if ($resp['httpCode'] != 200) {
            throw new Exception('http request code ' . $resp['httpCode'], ErrorCode::$IllegalHttpReq);
        }

        $body = json_decode($resp['body'], true, 512, JSON_THROW_ON_ERROR);

        if ($body['errcode'] !== 0) {
            throw new Exception($body['errmsg'], $body['errcode']);
        }

        $decrypted = $this->config->decryptMsg($body['encrypt']);
        $decoded = json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);

        return $decoded['id'];
    }

    /**
     * 删除群.
     */
    public function delete(string $groupId): bool
    {
        $resp = $this->client->get($this->app->buildUrl('/cgi/group/delete'), ['groupId' => $groupId]);
        $decoded = json_decode($resp['body'], true, 512, JSON_THROW_ON_ERROR);

        if ($decoded['errcode'] !== 0) {
            throw new Exception($decoded['errmsg'], 1);
        }

        return true;
    }

    /**
     * 修改群名称.
     */
    public function update(string $groupId, string $name): bool
    {
        $parameters = [
            'buin' => $this->config->getBuin(),
            'appId' => $this->config->getAppId(),
            'encrypt' => $this->config->encryptMsg(json_encode([
                'id' => $groupId,
                'name' => $name,
            ], JSON_THROW_ON_ERROR)),
        ];

        $resp = $this->client->post($this->app->buildUrl('/cgi/group/update'), $parameters);

        if ($resp['httpCode'] != 200) {
            throw new Exception('http request code ' . $resp['httpCode'], ErrorCode::$IllegalHttpReq);
        }

        $body = json_decode($resp['body'], true, 512, JSON_THROW_ON_ERROR);

        if ($body['errcode'] !== 0) {
            throw new Exception($body['errmsg'], $body['errcode']);
        }

        return true;
    }

    /**
     * 查看群信息.
     */
    public function info(string $groupId): array
    {
        $resp = $this->client->get($this->app->buildUrl('/cgi/group/info'), ['id' => $groupId]);
        $decoded = json_decode($resp['body'], true, 512, JSON_THROW_ON_ERROR);

        if ($decoded['errcode'] !== 0) {
            throw new Exception($decoded['errmsg'], 1);
        }

        $decrypted = $this->config->decryptMsg($decoded['encrypt'] ?? '');

        return json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR) ?? [];
    }

    /**
     * 添加群成员.
     */
    public function addMember(string $groupId, array $members = []): bool
    {
        $parameters = [
            'buin' => $this->config->getBuin(),
            'appId' => $this->config->getAppId(),
            'encrypt' => $this->config->encryptMsg(json_encode([
                'id' => $groupId,
                'userList' => $members,
            ], JSON_THROW_ON_ERROR)),
        ];

        $resp = $this->client->post($this->app->buildUrl('/cgi/group/addmember'), $parameters);

        if ($resp['httpCode'] != 200) {
            throw new Exception('http request code ' . $resp['httpCode'], ErrorCode::$IllegalHttpReq);
        }

        $body = json_decode($resp['body'], true, 512, JSON_THROW_ON_ERROR);

        if ($body['errcode'] !== 0) {
            throw new Exception($body['errmsg'], $body['errcode']);
        }

        return true;
    }

    /**
     * 删除群成员.
     */
    public function delMember(string $groupId, array $members = []): bool
    {
        $parameters = [
            'encrypt' => $this->config->encryptMsg(json_encode([
                'id' => $groupId,
                'userList' => $members,
            ], JSON_THROW_ON_ERROR)),
        ];

        $resp = $this->client->post($this->app->buildUrl('/cgi/group/delmember'), $parameters);

        if ($resp['httpCode'] != 200) {
            throw new Exception('http request code ' . $resp['httpCode'], ErrorCode::$IllegalHttpReq);
        }

        $body = json_decode($resp['body'], true, 512, JSON_THROW_ON_ERROR);

        if ($body['errcode'] !== 0) {
            throw new Exception($body['errmsg'], $body['errcode']);
        }

        return true;
    }

    /**
     * 查询用户是否是群成员.
     * @param int|string $userId
     */
    public function isMember(string $groupId, $userId): bool
    {
        $resp = $this->client->get($this->app->buildUrl('/cgi/group/ismember'), ['id' => $groupId, 'userId' => $userId]);
        $decoded = json_decode($resp['body'], true, 512, JSON_THROW_ON_ERROR);

        if ($decoded['errcode'] !== 0) {
            throw new Exception($decoded['errmsg'], 1);
        }

        $decrypted = $this->config->decryptMsg($decoded['encrypt'] ?? '');

        return json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR)['belong'] ?? false;
    }
}
