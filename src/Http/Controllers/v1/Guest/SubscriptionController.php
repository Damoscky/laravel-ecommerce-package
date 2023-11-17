<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Guest;

use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use SbscPackage\Ecommerce\Helpers\UserMgtHelper;
use SbscPackage\Ecommerce\Models\EcommerceNewsletterSubscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends BaseController
{

    public function newsletterSubscription(Request $request)
    {
        $validate = $this->validateSubscriber($request);

        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all());
        }

        try {

            $subscription = EcommerceNewsletterSubscriber::create([
                "email" => $request->email,
                "is_active" => 1
            ]);

            $data = [
                "id" => $subscription->id,
                "email" => $request->email
            ];

            Mail::to($request->email)->send(new SubscriptionEmail($data));

            return JsonResponser::send(false, "You've sucessfully subscribed to our newsletter", $subscription, 200);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    public function validateSubscriber(Request $request)
    {
        $rules = [
            'email' => 'required|unique:ecommerce_newsletter_subscribers|email:rfc,dns'
        ];

        return Validator::make($request->all(), $rules);
    }
}