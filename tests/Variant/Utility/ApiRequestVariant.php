<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Variant\Utility;

use Ifrost\ApiBundle\Utility\ApiRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ApiRequestVariant extends ApiRequest
{
    public function __construct(?Request $request = null)
    {
        $requestStack = new RequestStack();
        $requestStack->push($request ?? new Request());

        parent::__construct($requestStack);
    }
}
