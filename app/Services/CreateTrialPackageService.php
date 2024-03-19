<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;

class CreateTrialPackageService
{
    public function createPackage()
    {

        // return HTTP::withHeaders([
        //     'Authorization' => 'Bearer ' . config("app.stripe_secret"),
        //     'Content-Type' => 'application/x-www-form-urlencoded',
        // ])->post("https://api.stripe.com/v1/subscriptions");
        $stripe = new \Stripe\StripeClient(config("app.stripe_secret"));
return $stripe->subscriptions->create([
  'customer' => 'cus_PlRjQNwaFso8yP',
  'items' => [['price' => 'price_1OvZEkGeh9PhcWp49mwj2QAM']],
  'trial_period_days'=>30
]);
    }
}
