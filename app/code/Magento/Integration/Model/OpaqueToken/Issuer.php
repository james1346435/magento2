<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Integration\Model\OpaqueToken;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Integration\Api\Data\UserTokenParametersInterface;
use Magento\Integration\Api\Exception\UserTokenException;
use Magento\Integration\Api\UserTokenIssuerInterface;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;

/**
 * Issues opaque tokens (legacy).
 */
class Issuer implements UserTokenIssuerInterface
{
    /**
     * @var TokenModelFactory
     */
    private $tokenFactory;

    /**
     * @param TokenModelFactory $tokenFactory
     */
    public function __construct(TokenModelFactory $tokenFactory)
    {
        $this->tokenFactory = $tokenFactory;
    }

    /**
     * @inheritDoc
     */
    public function create(UserContextInterface $userContext, UserTokenParametersInterface $params): string
    {
        /** @var Token $token */
        $token = $this->tokenModelFactory->create();

        if ($userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
            $token = $token->createCustomerToken($userContext->getUserId())->getToken();
        } elseif ($userContext->getUserType() === UserContextInterface::USER_TYPE_ADMIN) {
            $token = $token->createAdminToken($userContext->getUserId())->getToken();
        } else {
            throw new UserTokenException('Can only create tokens for customers and admin users');
        }

        if ($params->getForcedIssuedTime()) {
            if ($params->getForcedIssuedTime()->getTimezone()->getName() !== 'UTC') {
                throw new UserTokenException('Invalid forced issued time provided');
            }
            $token->setCreatedAt($params->getForcedIssuedTime()->format('Y-m-d H:i:s'));
            $token->save();
        }

        return $token->getToken();
    }
}
