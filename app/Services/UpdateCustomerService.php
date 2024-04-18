<?php

namespace App\Services;

class UpdateCustomerService
{
    public function updateCustomer($data)
    {
        $stripe = new \Stripe\StripeClient(config("app.stripe_secret"));
        return $stripe->customers->update(
            $data[1],
            ['name' => $data[0]]
        );
    }
}
