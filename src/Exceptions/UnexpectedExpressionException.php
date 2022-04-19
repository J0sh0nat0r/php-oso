<?php

namespace J0sh0nat0r\Oso\Exceptions;

class UnexpectedExpressionException extends PolarException
{
    public function __construct()
    {
        parent::__construct(
            'Received Expression from Polar VM. The Expression type is only supported when
  using data filtering features. Did you perform an
  operation over an unbound variable in your policy?
  To silence this error and receive an Expression result, pass the
  `acceptExpression: true` option to `Oso::query` or `Oso::queryRule`.'
        );
    }
}