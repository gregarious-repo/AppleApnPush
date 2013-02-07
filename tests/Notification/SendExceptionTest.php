<?php

/**
 * This file is part of the AppleApnPush package
 *
 * (c) Vitaliy Zhuk <zhuk2205@gmail.com>
 *
 * For the full copyring and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace Apple\ApnPush\Notification;

use Apple\ApnPush\Connection\Connection;
use Apple\ApnPush\PayloadFactory\PayloadFactory;
use Apple\ApnPush\Messages\DefaultMessage;
use Apple\ApnPush\Messages\MessageInterface;

/**
 * Control errors
 */
class SendExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Notificaiton
     */
    protected $notification;

    /**
     * Create new mock connection for testing exceptions
     */
    protected function createNotification($responseData)
    {
        $socketMock = $this->getMock(
            'RequestStream\\Stream\\Socket\\SocketClient',
            array('create', 'write', 'read', 'selectRead', 'setBlocking', 'is')
        );

        $socketMock->expects($this->any())->method('selectRead')->will($this->returnValue(true));
        $socketMock->expects($this->any())->method('read')->will($this->returnCallback(function($size) use ($responseData){
            return $responseData;
        }));

        $notification = new Notification;
        $payload = new PayloadFactory;
        $connection = new Connection(__FILE__);

        $notification->setConnection($connection);
        $notification->setPayloadFactory($payload);

        $ref = new \ReflectionProperty($connection, 'socketConnection');
        $ref->setAccessible(true);
        $ref->setValue($connection, $socketMock);

        return $notification;
    }

    /**
     * @dataProvider notificationProvider
     */
    public function testNotificationException($response, $statusCode)
    {
        $notification = $this->createNotification($response);

        $message = new DefaultMessage;
        $message->setDeviceToken(str_repeat('af', 32));
        $message->setBody('foo');

        try {
            $notification->sendMessage($message);
            $this->fail('Must be throws');
        }
        catch (SendExceptionInterface $exception) {
            $this->assertEquals($exception->getStatusCode(), $statusCode);
        }
    }

    /**
     * Provider
     */
    public function notificationProvider()
    {
        return array(
            array(pack('CCN', 1, 1, 0), SendExceptionInterface::ERROR_PROCESSING),
            array(pack('CCN', 1, 2, 0), SendExceptionInterface::ERROR_MISSING_DEVICE_TOKEN),
            array(pack('CCN', 1, 3, 0), SendExceptionInterface::ERROR_MISSING_TOPIC),
            array(pack('CCN', 1, 4, 0), SendExceptionInterface::ERROR_MISSING_PAYLOAD),
            array(pack('CCN', 1, 5, 0), SendExceptionInterface::ERROR_INVALID_TOKEN_SIZE),
            array(pack('CCN', 1, 6, 0), SendExceptionInterface::ERROR_INVALID_TOPIC_SIZE),
            array(pack('CCN', 1, 7, 0), SendExceptionInterface::ERROR_INVALID_PAYLOAD_SIZE),
            array(pack('CCN', 1, 8, 0), SendExceptionInterface::ERROR_INVALID_TOKEN),
            array(pack('CCN', 1, 255, 0), SendExceptionInterface::ERROR_UNKNOWN),
            array('', SendExceptionInterface::ERROR_UNPACK_RESPONSE)
        );
    }
}