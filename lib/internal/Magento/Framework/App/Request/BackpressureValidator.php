<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Framework\App\Request;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Backpressure\BackpressureExceededException;
use Magento\Framework\App\BackpressureEnforcerInterface;
use Magento\Framework\App\Request\Backpressure\ContextFactory;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\LocalizedException;

/**
 * Enforces backpressure for non-webAPI requests.
 */
class BackpressureValidator implements ValidatorInterface
{
    /**
     * @var ContextFactory
     */
    private ContextFactory $contextFactory;

    /**
     * @var BackpressureEnforcerInterface
     */
    private BackpressureEnforcerInterface $enforcer;

    /**
     * @var AppState
     */
    private AppState $appState;

    /**
     * @param ContextFactory $contextFactory
     * @param BackpressureEnforcerInterface $enforcer
     * @param AppState $appState
     */
    public function __construct(
        ContextFactory $contextFactory,
        BackpressureEnforcerInterface $enforcer,
        AppState $appState
    ) {
        $this->contextFactory = $contextFactory;
        $this->enforcer = $enforcer;
        $this->appState = $appState;
    }

    /**
     * @inheritDoc
     */
    public function validate(RequestInterface $request, ActionInterface $action): void
    {
        try {
            $areaCode = $this->appState->getAreaCode();
        } catch (LocalizedException $exception) {
            $areaCode = null;
        }
        if ($request instanceof HttpRequest
            && in_array(
                $areaCode,
                [Area::AREA_FRONTEND, Area::AREA_ADMINHTML],
                true
            )
        ) {
            $context = $this->contextFactory->create($action);
            if ($context) {
                try {
                    $this->enforcer->enforce($context);
                } catch (BackpressureExceededException $exception) {
                    throw new LocalizedException(__('Something went wrong, please try again later'), $exception);
                }
            }
        }
    }
}
