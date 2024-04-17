<?php

namespace App\Services;

class UpdateCustomerService
{
    public function updateCustomer($data)
    {
        $stripe = new \Stripe\StripeClient(config("app.stripe_secret"));
        $stripe->customers->update(
            $data[1],
            ['name' => $data[0]]
        );
    }
}
