<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Service;

use Closure;
use DateTime;
use GuzzleHttp\Exception\GuzzleException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use GuzzleHttp\Client;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Throwable;
use Exception;
use Wexo\EasyTranslate\Core\Content\EasyTranslateClientConfig;
use Wexo\EasyTranslate\Helpers\JWTHelper;
use Wexo\EasyTranslate\WexoEasyTranslate;

/**
 *
 */
class APIHelperService
{
    protected const CONFIG_PREFIX = 'WexoEasyTranslate.config.';

    protected Client $client;
    protected JWTHelper $jwtHelper;
    protected EasyTranslateClientConfig $clientConfig;
    protected SystemConfigService $systemConfigService;
    protected LogService $logService;

    /**
     * @param JWTHelper $jwtHelper
     * @param SystemConfigService $systemConfigService
     * @param LogService $logService
     */
    public function __construct(
        JWTHelper $jwtHelper,
        EasyTranslateClientConfig $clientConfig,
        SystemConfigService $systemConfigService,
        LogService $logService
    ) {
        $this->jwtHelper = $jwtHelper;
        $this->clientConfig = $clientConfig;
        $this->systemConfigService = $systemConfigService;
        $this->logService = $logService;

        $this->client = new Client();

        try {
            $this->ensureClientConfig();
        } catch (GuzzleException|Throwable $e) {
            $this->logService->logError(
                "Error setting up EasyTranslateClient",
                [
                    "message" => $e->getMessage(),
                    "trace" => $e->getTraceAsString()
                ]
            );
        }
    }

    /**
     * Set access token and refresh token in the plugin config.
     *
     * @param string $accessToken
     * @param string $refreshToken
     * @return void
     */
    protected function setAccessAndRefreshToken(string $accessToken, string $refreshToken): void
    {
        $this->clientConfig->setAccessToken($accessToken);
        $this->clientConfig->setRefreshToken($refreshToken);

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
        // Get access token from EasyTranslate
        $uri = $this->clientConfig->getApiUri() . 'oauth/token';

        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'client_id' => $config['clientID'] ?? $this->clientConfig->getClientId(),
                'client_secret' => $config['clientSecret'] ?? $this->clientConfig->getClientSecret(),
                'grant_type' => 'password',
                'username' => $config['username'] ?? $this->clientConfig->getUsername(),
                'password' => $config['password'] ?? $this->clientConfig->getPassword(),
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
     * Checks JWT token if the token is expired. Requests new if it is.
     *
     * @return void
     * @throws GuzzleException
     */
    protected function refreshTokenIfExpired(): void
    {
        $expireDate = $this->jwtHelper
            ->parseJwtToken($this->clientConfig->getAccessToken())
            ->getExpiresAt()
            ->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $currentDate = new DateTime();
        $currentDate = $currentDate->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        if ($currentDate > $expireDate) {
            $this->refreshAccessToken();
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
        // Refresh access token from EasyTranslate
        $uri = $this->clientConfig->getApiUri() . 'oauth/token';

        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'client_id' => $this->clientConfig->getClientId(),
                'client_secret' => $this->clientConfig->getClientSecret(),
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->clientConfig->getRefreshToken(),
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
            if ($e->getCode() === 401) {
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
    protected function retryOnceWrapper(Closure $fun)
    {
        $counter = 0;

        do {
            try {
                return $fun();
            } catch (GuzzleException $e) {
                if ($counter === 0) {
                    if ($e->getCode() === 404) {
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
     * @throws Throwable
     */
    public function getTeamIdentifier(): void
    {
        $uri = $this->clientConfig->getApiUri() . 'api/v1/user';

        try {
            $this->retryOnceWrapper(function () use ($uri) {
                $options = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->clientConfig->getAccessToken(),
                        'Accept' => 'application/json'
                    ],
                ];

                $response = $this->client->request('GET', $uri, $options);
                $body = json_decode($response->getBody()->getContents(), true);

                $this->clientConfig->setTeamId($body['included'][0]['attributes']['team_identifier']);
                $this->systemConfigService->set(
                    self::CONFIG_PREFIX . 'teamIdentifier',
                    $this->clientConfig->getTeamId()
                );
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
     * @throws Throwable
     */
    public function getApiSettings(): array
    {
        $uri = $this->clientConfig->getApiUri() . "api/v1/settings";

        try {
            return $this->retryOnceWrapper(function () use ($uri) {
                $options = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->clientConfig->getAccessToken(),
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
        $uri = $this->clientConfig->getApiUri() . "api/v2/teams/" . $this->clientConfig->getTeamId();

        try {
            return $this->retryOnceWrapper(function () use ($uri) {
                $options = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->clientConfig->getAccessToken(),
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
     * @throws Throwable
     */
    public function createNewProject(array $data): array
    {
        $uri = $this->clientConfig->getApiUri() . "api/v2/teams/" . $this->clientConfig->getTeamId() . "/projects";

        try {
            return $this->retryOnceWrapper(function () use ($uri, $data) {
                $options = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->clientConfig->getAccessToken(),
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
     * @throws Throwable
     */
    public function handlePriceApproval(string $projectId, bool $approve = false): array
    {
        $uri = $this->clientConfig->getApiUri()
            . "api/v2/teams/" . $this->clientConfig->getTeamId() . "/projects/$projectId/";

        if ($approve) {
            $uri .= 'accept-price';
        } else {
            $uri .= 'decline-price';
        }

        try {
            return $this->retryOnceWrapper(function () use ($uri) {
                $options = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->clientConfig->getAccessToken(),
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
     * @throws Throwable
     */
    public function downloadTaskContent(string $taskURI): array
    {
        try {
            return $this->retryOnceWrapper(function () use ($taskURI) {
                $options =  [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->clientConfig->getAccessToken(),
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

    /**
     * Ensures the client is ready and configured to call EasyTranslate API
     *
     * @return void
     * @throws GuzzleException
     * @throws Exception|Throwable
     */
    protected function ensureClientConfig(): void
    {
        // API URI
        if (empty($this->clientConfig->getApiUri())) {
            $apiUri = $this->systemConfigService->get(self::CONFIG_PREFIX . 'apiUri');
            if (empty($apiUri)) {
                $this->logService->logError('Missing API URI in config');
                throw new Exception('Missing API URI in config');
            } else {
                $this->clientConfig->setApiUri($apiUri);
            }
        }

        // Client ID
        if (empty($this->clientConfig->getClientId())) {
            $clientId = $this->systemConfigService->get(self::CONFIG_PREFIX . 'clientId');
            if (empty($clientId)) {
                $this->logService->logError('Missing client ID in config');
                throw new Exception('Missing client ID in config');
            } else {
                $this->clientConfig->setClientId($clientId);
            }
        }

        // Client Secret
        if (empty($this->clientConfig->getClientSecret())) {
            $clientSecret = $this->systemConfigService->get(self::CONFIG_PREFIX . 'clientSecret');
            if (!$clientSecret) {
                $this->logService->logError('Missing client secret in config');
                throw new Exception('Missing client secret in config');
            } else {
                $this->clientConfig->setClientSecret($clientSecret);
            }
        }

        // Username
        if (empty($this->clientConfig->getUsername())) {
            $username = $this->systemConfigService->get(self::CONFIG_PREFIX . 'username');
            if (!$username) {
                $this->logService->logError('Missing username in config');
                throw new Exception('Missing username in config');
            } else {
                $this->clientConfig->setUsername($username);
            }
        }

        // Password
        if (empty($this->clientConfig->getPassword())) {
            $password = $this->systemConfigService->get(self::CONFIG_PREFIX . 'password');
            if (!$password) {
                $this->logService->logError('Missing password in config');
                throw new Exception('Missing password in config');
            } else {
                $this->clientConfig->setPassword($password);
            }
        }

        // Access Token (JWT)
        if (empty($this->clientConfig->getAccessToken())) {
            $accessToken = $this->systemConfigService->get(self::CONFIG_PREFIX . 'accessToken');
            if (empty($accessToken)) {
                $this->getNewAccessToken();
            } else {
                $this->clientConfig->setAccessToken($accessToken);
            }
        }

        // Refresh Token (JWT)
        if (empty($this->clientConfig->getRefreshToken())) {
            $refreshToken = $this->systemConfigService->get(self::CONFIG_PREFIX . 'refreshToken');
            if (empty($refreshToken)) {
                $this->getNewAccessToken();
            } else {
                $this->clientConfig->setRefreshToken($refreshToken);
            }
        }

        $this->refreshTokenIfExpired();

        // Team Identifier
        if (empty($this->clientConfig->getTeamId())) {
            $teamId = $this->systemConfigService->get(self::CONFIG_PREFIX . 'teamIdentifier');
            if (empty($teamId)) {
                $this->getTeamIdentifier();
            } else {
                $this->clientConfig->setTeamId($teamId);
            }
        }
    }
}
