<?php

/**
 * This file is part of the AppleApnPush package
 *
 * (c) Vitaliy Zhuk <zhuk2205@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace Apple\ApnPush\Notification;

/**
 * Default payload factory
 */
class PayloadFactory implements PayloadFactoryInterface
{
    /**
     * @var boolean
     */
    protected $jsonUnescapedUnicode = false;

    /**
     * {@inheritDoc}
     */
    public function setJsonUnescapedUnicode($status)
    {
        // Check PHP version
        if (!version_compare(PHP_VERSION, '5.4.0', '>=')) {
            throw new \LogicException(sprintf(
                'Can\'t use JSON_UNESCAPED_UNICODE option on PHP %s. Support PHP >= 5.4.0',
                PHP_VERSION
            ));
        }

        $this->jsonUnescapedUnicode = (bool) $status;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonUnescapedUnicode()
    {
        return $this->jsonUnescapedUnicode;
    }

    /**
     * {@inheritDoc}
     */
    public function createPayload(MessageInterface $message)
    {
        $payload = pack('CNNnH*',
            1, // Command
            $message->getIdentifier(),
            $message->getExpires()->format('U'),
            32, // Token length
            $message->getDeviceToken()
        );

        $jsonData = $this->createJsonPayload($message);

        $payload .= pack('n', mb_strlen($jsonData));
        $payload .= $jsonData;

        return $payload;
    }

    /**
     * {@inheritDoc}
     */
    public function createJsonPayload(MessageInterface $message)
    {
        if ($this->jsonUnescapedUnicode && version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return json_encode($message->getPayloadData(), JSON_FORCE_OBJECT ^ JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode($message->getPayloadData(), JSON_FORCE_OBJECT);
        }
    }
}
