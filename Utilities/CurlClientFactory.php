<?php

namespace CzarTheory\Utilities;

/**
 * Provides functions for generating cURL clients.
 * @copyright   Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 * @author      Andrew Wheelwright <wheelwright.tech@gmail.com>
 * @static
 */
class CurlClientFactory
{
	/**
	 * Default configuration for clients which need to retry requests.
	 * @var array
	 */
	private static $_defaultRetryConfig = null;

	/**
	 * Gets a cURL client configured to handle retrying requests.
	 *
	 * @param string $uri The URI to which the client will connect.
	 * @param array $config The configuration array.
	 * @return \Zend_Http_Client The configured client.
	 */
	public static function getCurlClient($uri, array $config = null)
	{
		if (null === $config)
		{
			$config = self::getDefaultRetryConfig();
		}

		return new \Zend_Http_Client($uri, $config);
	}

	/**
	 * Gets an array of default settings for a client needing to handle multiple requests.
	 * @return array The cURL configuration.
	 */
	private static function getDefaultRetryConfig()
	{
		if (null === self::$_defaultRetryConfig)
		{
			self::$_defaultRetryConfig = array(
				'adapter' => 'Zend_Http_Client_Adapter_Curl',
				'keepalive' => true,
				'curloptions' => array(
					\CURLOPT_TIMEOUT => \Zend_Registry::get('curlRequestTimeout'),
					\CURLOPT_POST => true,
				),
			);
		}

		return self::$_defaultRetryConfig;
	}
}
