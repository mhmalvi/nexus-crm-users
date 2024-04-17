<?php

namespace App\Services;

class CreateSubscriptionService
{
    public function create_subscription($data, $priceId)
    {
        $stripe = new
            \Stripe\StripeClient(config("app.stripe_secret"));
            dd($data->id);
        return $response = $stripe->subscriptions->create([
            'customer' => $data->id,

            'items' => [['price' => $priceId]],
        ]);
    }
}
