<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['api_key'],
            'type' => User::TYPE_MERCHANT,
        ]);

        // Create a new merchant associated with the user
        $merchant = Merchant::create([
            'user_id' => $user->id,
            'domain' => $data['domain'],
            'display_name' => $data['name'],
        ]);

        return $merchant;
    }

    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        // Update the user information
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['api_key'],
        ]);

        // Update the associated merchant information
        $merchant = $user->merchant;
        if ($merchant) {
            $merchant->update([
                'domain' => $data['domain'],
                'display_name' => $data['name'],
            ]);
        }
    }

    /**
     * Find a merchant by their email.
     * Hint: You'll need to look up the user first.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        // Find the user with the given email
        $user = User::where('email', $email)->first();

        return $user ? $user->merchant : null;
    }

    /**
     * Pay out all of an affiliate's orders.
     * Hint: You'll need to dispatch the job for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        $unpaidOrders = $affiliate->orders()->where('payout_status', Order::STATUS_UNPAID)->get();

        foreach ($unpaidOrders as $order) {
            dispatch(new PayoutOrderJob($order));
        }
    }
}
