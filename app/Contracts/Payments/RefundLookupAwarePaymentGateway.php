<?php

namespace App\Contracts\Payments;

interface RefundLookupAwarePaymentGateway extends PaymentGateway
{
    public function retrieveRefund(RefundRetrieveRequest $request): RefundRetrieveResult;
}
