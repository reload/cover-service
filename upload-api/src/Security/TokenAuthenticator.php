<?php
/**
 * @file
 * Token authentication using adgangsplatform introspection end-point.
 */

namespace App\Security;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class TokenAuthenticator
 */
class TokenAuthenticator extends AbstractGuardAuthenticator
{
    private $params;
    private $client;

    /**
     * TokenAuthenticator constructor.
     *
     * @param ParameterBagInterface $params
     * @param HttpClientInterface $httpClient
     */
    public function __construct(ParameterBagInterface $params, HttpClientInterface $httpClient)
    {
        $this->params = $params;
        $this->client = $httpClient;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(Request $request)
    {
        return $request->headers->has('authorization');
    }

    /**
     * {@inheritDoc}
     */
    public function getCredentials(Request $request)
    {
        return $request->headers->get('authorization');
    }

    /**
     * {@inheritDoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (null === $credentials) {
            // The token header was empty, authentication fails with HTTP Status
            // Code 401 "Unauthorized"
            return null;
        }

        // Parse token information from the bearer authorization header.
        preg_match('/Bearer\s(\w+)/', $credentials, $matches);
        if (2 !== count($matches)) {
            return null;
        }

        $token = $matches[1];
        $response = $this->client->request('POST', 'https://login.bib.dk/oauth/introspection?access_token='.$token, [
            'auth_basic' => ['CLIENT_ID', 'SECRECT'],
        ]);

        if (200 !== $response->getStatusCode()) {
            return null;
        } else {
            $content = $response->getContent();
            $data = json_decode($content);

            // Token not valid, hence not active at the introspection end-point.
            if (true == !$data->active) {
                return null;
            }
        }

        // Create user object.
        $user = new User();
        $user->setPassword($token);
        $user->setExpires(new \DateTime($data->expires, new \DateTimeZone('Europe/Copenhagen')));
        $user->setAgency($data->agency);
        $user->setAuthType($data->type);
        $user->setClientId($data->clientId);

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        // In case of an token, no credential check is needed.
        // Return `true` to cause authentication success
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            'message' => 'Authentication failed',
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * {@inheritDoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            'message' => 'Authentication Required',
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsRememberMe()
    {
        return false;
    }
}
