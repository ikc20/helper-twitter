<?php

namespace App\TwitterHelpers;

use Abraham\TwitterOAuth\TwitterOAuth;
use Exception;

class TwitterPoster
{
    public const VERSION_1 = '1';
    public const VERSION_2 = '2';

    private TwitterAPI $api;
    private TwitterOAuth $apiV2;
    private string $apiVersion;

    public function __construct(
        string $oauthAccessToken,
        string $oauthAccessTokenSecret,
        string $consumerKey,
        string $consumerSecret,
        string $version = self::VERSION_1 // Default API version set to VERSION_1
    )
    {
        $this->api = new TwitterAPI([
            'oauth_access_token' => $oauthAccessToken,
            'oauth_access_token_secret' => $oauthAccessTokenSecret,
            'consumer_key' => $consumerKey,
            'consumer_secret' => $consumerSecret
        ]);

        $this->apiV2 = new TwitterOAuth($consumerKey, $consumerSecret, $oauthAccessToken, $oauthAccessTokenSecret);
        $this->apiV2->setApiVersion('2');

        $this->setApiVersion($version);
    }

    /**
     * Set the API version to be used (either VERSION_1 or VERSION_2).
     *
     * @param string $version
     * @throws Exception
     */
    public function setApiVersion(string $version): void
    {
        if (!in_array($version, [self::VERSION_1, self::VERSION_2])) {
            throw new Exception('Invalid API version.');
        }

        $this->apiVersion = $version;
    }

    /**
     * Get the current API version.
     *
     * @return string
     */
    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }

    /**
     * Post a tweet based on the current API version.
     *
     * @param string $status
     * @return string
     * @throws Exception
     */
    public function updateStatus(string $status): string
    {
        if ($this->apiVersion === self::VERSION_1) {
            return $this->api->setPostfields(['status' => $status])
                ->buildOauth('https://api.twitter.com/1.1/statuses/update.json', 'POST')
                ->performRequest();
        }

        if ($this->apiVersion === self::VERSION_2) {
            return $this->postToApiV2($status);
        }

        throw new Exception('Bad version');
    }

    /**
     * Post a tweet using Twitter API version 2.
     *
     * @param string $status
     * @return string
     * @throws Exception
     */
    private function postToApiV2(string $status): string
    {
        $tweetParams = [
            'text' => $status
        ];

        $response = $this->apiV2->post('tweets', $tweetParams);

        return $this->handleApiResponse($response);
    }

    /**
     * Handle the API response and throw exceptions if any error occurs.
     *
     * @param mixed $response
     * @return string
     * @throws Exception
     */
    private function handleApiResponse($response): string
    {
        if (isset($response->status) && $response->status >= 400) {
            throw new Exception('API' . (isset($response->title) ? (' ' . $response->title) : '') . ' error ' . $response->status . ' : ' . $response->detail);
        }

        if (!isset($response->data)) {
            throw new Exception('No data in API Response. Response: ' . json_encode($response));
        }

        return json_encode($response->data);
    }
}
