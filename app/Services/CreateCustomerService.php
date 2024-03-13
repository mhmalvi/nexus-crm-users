<?php

namespace App\Services;

use App\Interfaces\CreateInterface;

class CreateCustomerService implements CreateInterface{
    public function create($data){
        $stripe = new \Stripe\StripeClient(config("app.stripe_secret"));
        return $stripe->customers->create([
        'name' => $data[0],
        'email' => $data[1],
        ]);
    }
}