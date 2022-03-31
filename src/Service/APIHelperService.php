<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Service;

use Closure;
use GuzzleHttp\Exception\GuzzleException;
use Shopware\Core\Framework\Context;
use GuzzleHttp\Client;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Throwable;
use Exception;
use Wexo\EasyTranslate\WexoEasyTranslate;

/**
 *
 */
class APIHelperService
{
    private const CONFIG_PREFIX = 'WexoEasyTranslate.config.';

    private Client $client;
    private string $apiUri;
    private string $accessToken;
    private string $teamIdentifier;

    private SystemConfigService $systemConfigService;
    private LogService $logService;

    /**
     * @param SystemConfigService $systemConfigService
     * @param LogService $logService
     * @throws GuzzleException
     */
    public function __construct(SystemConfigService $systemConfigService, LogService $logService)
    {
        $this->systemConfigService = $systemConfigService;
        $this->logService = $logService;

        $this->client = new Client();

        $apiUri = $this->systemConfigService->get(self::CONFIG_PREFIX . 'apiUri');
        if (!str_ends_with($apiUri, '/')) {
            $apiUri .= '/';
        }
        $this->apiUri = $apiUri;

        // Set access token from config or get new from EasyTranslate
        $accessToken = $this->systemConfigService->get(self::CONFIG_PREFIX . 'accessToken');
        if (!$accessToken) {
            $this->getNewAccessToken();
        } else {
            $this->accessToken = $accessToken;
        }

        $teamIdentifier = $this->systemConfigService->get(self::CONFIG_PREFIX . 'teamIdentifier');
        if (!$teamIdentifier) {
            $this->getTeamIdentifier();
        } else {
            $this->teamIdentifier = $teamIdentifier;
        }
    }

    /**
     * Set access token and refresh token in the plugin config.
     *
     * @param string $accessToken
     * @param string $refreshToken
     * @return void
     */
    private function setAccessAndRefreshToken(string $accessToken, string $refreshToken): void
    {
        $this->accessToken = $accessToken;

        $this->systemConfigService->set(self::CONFIG_PREFIX . 'accessToken', $accessToken);
        $this->systemConfigService->set(self::CONFIG_PREFIX . 'refreshToken', $refreshToken);
    }

    /**
     * Get new access token from EasyTranslate and sets it in the plugin config.
     *
     * @param array $config
     * @return void
     * @throws Exception|GuzzleException
     */
    public function getNewAccessToken(array $config = []): void
    {
        if (!empty($config)) {
            $clientID = $config['clientID'];
            $clientSecret = $config['clientSecret'];
            $username = $config['username'];
            $password = $config['password'];
        } else {
            $clientID = $this->systemConfigService->get(self::CONFIG_PREFIX . 'clientId');
            if (!$clientID) {
                $this->logService->logError('Missing client ID in config');
                throw new Exception('Missing client ID in config');
            }

            $clientSecret = $this->systemConfigService->get(self::CONFIG_PREFIX . 'clientSecret');
            if (!$clientSecret) {
                $this->logService->logError('Missing client secret in config');
                throw new Exception('Missing client secret in config');
            }

            $username = $this->systemConfigService->get(self::CONFIG_PREFIX . 'username');
            if (!$username) {
                $this->logService->logError('Missing username in config');
                throw new Exception('Missing username in config');
            }

            $password = $this->systemConfigService->get(self::CONFIG_PREFIX . 'password');
            if (!$password) {
                $this->logService->logError('Missing password in config');
                throw new Exception('Missing password in config');
            }
        }

        // Get access token from EasyTranslate
        $uri = $this->apiUri . 'oauth/token';

        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'client_id' => $clientID,
                'client_secret' => $clientSecret,
                'grant_type' => 'password',
                'username' => $username,
                'password' => $password,
                'scope' => 'dashboard'
            ]
        ];

        try {
            $response = $this->client->request('POST', $uri, $options);

            $body = json_decode($response->getBody()->getContents(), true);
            $accessToken = $body['access_token'];
            $refreshToken = $body['refresh_token'];

            // Set access token on helper and update config
            $this->setAccessAndRefreshToken($accessToken, $refreshToken);
        } catch (Exception $e) {
            $this->logService->logError(
                'Unable to get new access token',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
            throw $e;
        }
    }

    /**
     * Refresh access token using the refresh token in config.
     *
     * If no refresh token exists, or it ends up in a 401,
     * get new access token using login credentials.
     *
     * @return void
     * @throws GuzzleException
     * @throws Exception
     */
    public function refreshAccessToken(): void
    {
        // Get auth info from config
        $refreshToken = $this->systemConfigService->get(self::CONFIG_PREFIX . 'refreshToken');
        if (!$refreshToken) {
            $this->getNewAccessToken();
            return;
        }

        $clientID = $this->systemConfigService->get(self::CONFIG_PREFIX . 'clientId');
        if (!$clientID) {
            $this->logService->logError('Missing client ID in config');
            throw new Exception('Missing client ID in config');
        }

        $clientSecret = $this->systemConfigService->get(self::CONFIG_PREFIX . 'clientSecret');
        if (!$clientSecret) {
            $this->logService->logError('Missing client secret in config');
            throw new Exception('Missing client secret in config');
        }

        // Refresh access token from EasyTranslate
        $uri = $this->apiUri . 'oauth/token';

        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'client_id' => $clientID,
                'client_secret' => $clientSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'scope' => 'dashboard',
            ],
        ];

        try {
            $response = $this->client->request('POST', $uri, $options);

            $body = json_decode($response->getBody()->getContents(), true);
            $accessToken = $body['access_token'];
            $refreshToken = $body['refresh_token'];

            // Set access token on helper and update config
            $this->setAccessAndRefreshToken($accessToken, $refreshToken);
        } catch (Exception $e) {
            $this->logService->logError(
                'Unable to refresh access token',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
            // Refresh token might be wrong, try to get
            // new access in the original way
            if ($e->getCode() == 401) {
                $this->getNewAccessToken();
            } else {
                throw $e;
            }
        }
    }

    /**
     * Run the given function, if it fails with a 401 or 404,
     * either refresh access token or team identifier and try once again.
     *
     * If it fails more than once or with other errors, it throws that error.
     *
     * @param Closure $fun
     * @return mixed|void
     * @throws GuzzleException
     * @throws Throwable
     */
    private function retryOnceWrapper(Closure $fun)
    {
        $counter = 0;

        do {
            try {
                return $fun();
            } catch (GuzzleException $e) {
                if ($counter == 0) {
                    if ($e->getCode() == 401) {
                        $counter++;
                        $this->refreshAccessToken();
                    } elseif ($e->getCode() == 404) {
                        $counter++;
                        $this->getTeamIdentifier();
                    } else {
                        throw $e;
                    }
                } else {
                    throw $e;
                }
            }
        } while ($counter < 2);
    }

    /**
     * Get team identifier from EasyTranslate and sets it in the config.
     *
     * @return void
     * @throws GuzzleException
     * @throws Exception
     */
    public function getTeamIdentifier(): void
    {
        $uri = $this->apiUri . 'api/v1/user';

        try {
            $this->retryOnceWrapper(function () use ($uri) {
                $options = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->accessToken,
                        'Accept' => 'application/json'
                    ],
                ];

                $response = $this->client->request('GET', $uri, $options);
                $body = json_decode($response->getBody()->getContents(), true);

                $this->teamIdentifier = $body['included'][0]['attributes']['team_identifier'];
                $this->systemConfigService->set(self::CONFIG_PREFIX . 'teamIdentifier', $this->teamIdentifier);
            });
        } catch (Exception $e) {
            $this->logService->logError(
                'Unable to get team identifier',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
            throw $e;
        }
    }


    /**
     * Get and return API settings from EasyTranslate
     *
     * @return array
     * @throws GuzzleException
     * @throws Exception
     */
    public function getApiSettings(): array
    {
        $uri = $this->apiUri . "api/v1/settings";

        try {
            return $this->retryOnceWrapper(function () use ($uri) {
                $options = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->accessToken,
                        'Accept' => 'application/json',
                    ],
                ];

                $response = $this->client->request('GET', $uri, $options);

                return json_decode($response->getBody()->getContents(), true);
            });
        } catch (Exception $e) {
            $this->logService->logError(
                'Unable to get API settings from EasyTranslate',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
            throw $e;
        }
    }

    /**
     * Get and return team details for the authenticated user
     *
     * @return array
     * @throws GuzzleException
     * @throws Throwable
     */
    public function getTeamDetails(): array
    {
        $uri = $this->apiUri . "api/v2/teams/" . $this->teamIdentifier;

        try {
            return $this->retryOnceWrapper(function () use ($uri) {
                $options = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->accessToken,
                        'Accept' => 'application/json',
                    ],
                ];

                $response = $this->client->request('GET', $uri, $options);

                return json_decode($response->getBody()->getContents(), true);
            });
        } catch (Exception $e) {
            $this->logService->logError(
                'Unable to get team details from EasyTranslate',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
            throw $e;
        }
    }

    /**
     * Create a new translation project in EasyTranslate.
     *
     * @param array $data
     * @return array
     * @throws GuzzleException
     * @throws Exception
     */
    public function createNewProject(array $data): array
    {
        $uri = $this->apiUri . "api/v2/teams/$this->teamIdentifier/projects";

        try {
            return $this->retryOnceWrapper(function () use ($uri, $data) {
                $options = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->accessToken,
                        'Accept' => 'application/json',
                    ],
                    'json' => ['data' => $data]
                ];

                $response = $this->client->request('POST', $uri, $options);

                return json_decode($response->getBody()->getContents(), true);
            });
        } catch (Exception $e) {
            $this->logService->logError(
                'Unable to create new project at EasyTranslate',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
            throw $e;
        }
    }

    /**
     * Handles price approval according to supplied $approve parameter.
     *
     * If true, accept the price, otherwise decline the price.
     *
     * @param string $projectId
     * @param bool $approve
     * @return array
     * @throws GuzzleException
     * @throws Exception
     */
    public function handlePriceApproval(string $projectId, bool $approve = false): array
    {
        $uri = $this->apiUri . "api/v1/teams/$this->teamIdentifier/projects/$projectId/";

        if ($approve) {
            $uri .= 'accept-price';
        } else {
            $uri .= 'decline-price';
        }

        try {
            return $this->retryOnceWrapper(function () use ($uri) {
                $options = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->accessToken,
                        'Accept' => 'application/json',
                    ],
                ];

                $response = $this->client->request('POST', $uri, $options);

                return json_decode($response->getBody()->getContents(), true);
            });
        } catch (Exception $e) {
            $this->logService->logError(
                'Unable to handle price approval',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
            throw $e;
        }
    }

    /**
     * Download task content from the supplied $taskURI.
     *
     * @param string $taskURI
     * @return array
     * @throws GuzzleException
     * @throws Exception
     */
    public function downloadTaskContent(string $taskURI): array
    {
        try {
            return $this->retryOnceWrapper(function () use ($taskURI) {
                $options =  [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->accessToken,
                        'Accept' => 'application/json',
                    ]
                ];

                $response = $this->client->request('GET', $taskURI, $options);

                return json_decode($response->getBody()->getContents(), true);
            });
        } catch (Exception $e) {
            $this->logService->logError(
                'Unable to download task content from EasyTranslate',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
            throw $e;
        }
    }
}
