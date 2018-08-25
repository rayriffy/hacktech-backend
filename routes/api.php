<?php

use App\BANK;
use App\SHOP;
use App\TRANSACTION;
use App\USER;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('login', function (Request $request) {
    $request = $request->json()->all();
    $data = $request['data'];

    if (empty($data['user']['id'])) {
        return [
        'response' => 'error',
        'remark'   => 'missing some/all payload',
    ];
    }

    if (USER::where('id', $data['user']['id'])->exists()) {
        return [
      'response' => 'success',
    ];
    } else {
        $user = new USER();
        $user->id = $data['user']['id'];
        $user->save();

        return [
      'response' => 'success',
      'remark'   => 'new user created',
    ];
    }
});

Route::post('bank', function (Request $request) {
    $request = $request->json()->all();
    $data = $request['data'];

    if (empty($data['user']['id']) || empty($data['bank']['name']) || empty($data['bank']['provider']) || empty($data['bank']['accountid'])) {
        return [
      'response' => 'error',
      'remark'   => 'missing some/all payload',
    ];
    }

    if (BANK::where('owner_id', $data['user']['id'])->where('bank_provider', $data['bank']['provider'])->where('bank_accountid', $data['bank']['accountid'])->exists()) {
        return [
      'response' => 'error',
      'remark'   => 'bank account is already exists',
    ];
    } else {
        $bank = new BANK();
        $bank->id = str_random(32);
        $bank->owner_id = $data['user']['id'];
        $bank->bank_name = $data['bank']['name'];
        $bank->bank_provider = $data['bank']['provider'];
        $bank->bank_accountid = $data['bank']['accountid'];
        $bank->save();

        return [
      'response' => 'success',
    ];
    }
});

Route::post('money', function (Request $request) {
    $request = $request->json()->all();
    $data = $request['data'];

    if (empty($data['user']['id']) || empty($data['bank']['id']) || empty($data['bank']['amount'])) {
        return [
      'response' => 'error',
      'remark'   => 'missing some/all payload',
    ];
    }

    if (!(USER::where('id', $data['user']['id'])->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'user does not exist',
    ];
    }

    if (!(BANK::where('owner_id', $data['user']['id'])->where('id', $data['bank']['id'])->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'bank does not exist',
    ];
    }

    $bank_account = BANK::select('amount')->where('owner_id', $data['user']['id'])->where('id', $data['bank']['id'])->first();
    $new_amount = $bank_account['amount'] + $data['bank']['amount'];
    if (BANK::where('owner_id', $data['user']['id'])->where('id', $data['bank']['id'])->update(['amount' => $new_amount])) {
        return [
      'response' => 'success',
      'remark'   => $data['bank']['amount'].' added to account '.$data['bank']['id'].' of '.$data['user']['id'],
    ];
    } else {
        return [
      'response' => 'error',
      'remark'   => "that's an unexpected one...",
    ];
    }
});

Route::post('shop', function (Request $request) {
    $request = $request->json()->all();
    $data = $request['data'];

    if (empty($data['user']['id']) || empty($data['shop']['name'])) {
        return [
      'response' => 'error',
      'remark'   => 'missing some/all payload',
    ];
    }

    if (SHOP::where('id', $data['user']['id'])->exists()) {
        if (SHOP::where('id', $data['user']['id'])->update(['name' => $data['shop']['name']])) {
            return [
        'response' => 'success',
      ];
        } else {
            return [
        'response' => 'error',
        'remark'   => "that's an unexpected one...",
      ];
        }
    } else {
        $shop = new SHOP();
        $shop->id = $data['user']['id'];
        $shop->name = $data['shop']['name'];
        $shop->save();

        return [
      'response' => 'success',
    ];
    }
});

Route::post('transaction', function (Request $request) {
    $request = $request->json()->all();
    $data = $request['data'];

    if (empty($data['sender']['id']) || empty($data['sender']['bank']) || empty($data['sender']['amount']) || empty($data['reciver']['id']) || empty($data['reciver']['bank'])) {
        return [
      'response' => 'error',
      'remark'   => 'missing some/all payload',
    ];
    }

    if ($data['sender']['amount'] < 0) {
        return [
      'response' => 'error',
      'remark'   => 'amount could not be lower than 0',
    ];
    }

    if (!(USER::where('id', $data['sender']['id'])->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'sender id not found',
    ];
    } elseif (!(USER::where('id', $data['reciver']['id'])->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'reciver id not found',
    ];
    }

    if (!(BANK::where('id', $data['sender']['bank'])->where('owner_id', $data['sender']['id'])->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'sender bank account not found',
    ];
    } elseif (!(BANK::where('id', $data['reciver']['bank'])->where('owner_id', $data['sender']['id'])->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'reciver bank account not found',
    ];
    }

    $sender = BANK::select('amount')->where('id', $data['sender']['bank'])->where('owner_id', $data['sender']['id'])->first();

    if ($sender['amount'] - $data['sender']['amount'] < 0) {
        return [
      'response' => 'error',
      'remark'   => 'insufficient funds',
    ];
    }

    $reciver = BANK::select('amount')->where('id', $data['reciver']['bank'])->where('owner_id', $data['reciver']['id'])->first();

    $last_transaction = TRANSACTION::where('status', true)->last();

    $transaction_id = str_random(128);

    if (BANK::where('id', $data['sender']['bank'])->where('owner_id', $data['sender']['id'])->update(['amount', $sender['amount'] - $data['sender']['amount']])) {
        $trans = new TRANSACTION();
        $trans->id = $transaction_id;
        $trans->relationship = $last_transaction['id'];
        $trans->sender_id = $data['sender']['id'];
        $trans->sender_bank = $data['sender']['bank'];
        $trans->sender_amount = $data['sender']['amount'];
        $trans->sender_id = $data['sender']['id'];
        $trans->reciver_id = $data['reciver']['id'];
        $trans->reciver_bank = $data['reciver']['bank'];
        $trans->save();

        if (BANK::where('id', $data['reciver']['bank'])->where('owner_id', $data['reciver']['id'])->update(['amount', $reciver['amount'] + $data['reciver']['amount']])) {
            TRANSACTION::where('id', $transaction_id)->update(['status', true]);

            return [
        'response' => 'success',
        'remark'   => 'transaction saved at '.$transaction_id,
      ];
        } else {
            return [
        'response' => 'error',
        'remark'   => 'could not change reciver amount, transaction '.$transaction_id.' status set as incomplete',
      ];
        }
    } else {
        return [
      'response' => 'error',
      'remark'   => 'could not change sender amount, transaction not created',
    ];
    }
});

Route::get('bank/{user_id}', function ($user_id) {
    if (empty($user_id)) {
        return [
      'response' => 'error',
      'remark'   => 'missing some/all payload',
    ];
    }

    if (!(BANK::where('owner_id', $user_id)->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'bank not found',
    ];
    }

    $banks = BANK::select('id', 'bank_name', 'bank_provider', 'bank_accountid')->where('owner_id', $user_id)->get();

    foreach ($banks as $dat) {
        $bank[] = [
      'id'        => $dat['bank'],
      'name'      => $dat['bank_name'],
      'provider'  => $dat['bank_provider'],
      'accountid' => $dat['bank_accountid'],
    ];
    }

    return [
    'response' => 'success',
    'data'     => $bank,
  ];
});

Route::get('amount/{user_id}/{bank_id}', function ($user_id, $bank_id) {
    if (empty($user_id) || empty($bank_id)) {
        return [
      'response' => 'error',
      'remark'   => 'missing some/all payload',
    ];
    }

    if (!(USER::where('id', $user_id)->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'user not found',
    ];
    }

    if (!(BANK::where('owner_id', $user_id)->where('id', $bank_id)->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'bank account not found',
    ];
    }

    $bank = BANK::select('amount')->where('owner_id', $user_id)->where('id', $bank_id)->first();

    return [
    'response' => 'success',
    'data'     => $bank['amount'],
  ];
});

Route::get('shop/{user_id}', function ($user_id) {
    if (empty($user_id)) {
        return [
      'response' => 'error',
      'remark'   => 'missing some/all payload',
    ];
    }

    if (!(USER::where('id', $user_id)->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'user not found',
    ];
    }

    $shop = SHOP::select('name')->where('id', $user_id)->first();

    return [
    'response' => 'success',
    'data'     => $shop['name'],
  ];
});

Route::get('user/{user_id}', function ($user_id) {
    if (empty($user_id)) {
        return [
      'response' => 'error',
      'remark'   => 'missing some/all payload',
    ];
    }

    if (!(USER::where('id', $user_id)->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'user not found',
    ];
    }

    $user = USER::select('id', 'name', 'email', 'phone', 'citizenid')->where('id', $user_id)->first();

    return [
    'response' => 'success',
    'data'     => [
      'id'        => $user['id'],
      'name'      => $user['name'],
      'email'     => $user['email'],
      'phone'     => $user['phone'],
      'citizenid' => $user['citizenid'],
    ],
  ];
});

// TODO
Route::get('transaction/{trans_id}', function ($trans_id) {
    if (empty($trans_id)) {
        return [
      'response' => 'error',
      'remark'   => 'missing some/all payload',
    ];
    }
});

Route::put('user', function (Request $request) {
    $request = $request->json()->all();
    $data = $request['data'];
    $count = 0;

    if (empty($data['user']['id'])) {
        return [
      'response' => 'error',
      'remark'   => 'missing some/all payload',
    ];
    }

    if (!(USER::where('id', $data['user']['id'])->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'user not found',
    ];
    }

    if (!empty($data['user']['name'])) {
        if (!(USER::where('id', $data['user']['id'])->update(['name' => $data['user']['name']]))) {
            return [
        'response' => 'error',
        'remark'   => "that's an unexpected one...",
      ];
        } else {
            $count++;
        }
    }

    if (!empty($data['user']['email'])) {
        if (!(USER::where('id', $data['user']['id'])->update(['email' => $data['user']['email']]))) {
            return [
        'response' => 'error',
        'remark'   => "that's an unexpected one...",
      ];
        } else {
            $count++;
        }
    }

    if (!empty($data['user']['phone'])) {
        if (!(USER::where('id', $data['user']['id'])->update(['phone' => $data['user']['phone']]))) {
            return [
        'response' => 'error',
        'remark'   => "that's an unexpected one...",
      ];
        } else {
            $count++;
        }
    }

    if (!empty($data['user']['citizenid'])) {
        if (!(USER::where('id', $data['user']['id'])->update(['citizenid' => $data['user']['citizenid']]))) {
            return [
        'response' => 'error',
        'remark'   => "that's an unexpected one...",
      ];
        } else {
            $count++;
        }
    }

    return [
    'response' => 'success',
    'remark'   => $count.' fields updated',
  ];
});

Route::put('bank', function (Request $request) {
    $request = $request->json()->all();
    $data = $request['data'];

    if (empty($data['user']['id']) || empty($data['bank']['id'])) {
        return [
      'response' => 'error',
      'remark'   => 'missing some/all payload',
    ];
    }

    if (!(USER::where('id', $data['user']['id'])->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'user not found',
    ];
    }

    if (!(BANK::where('owner_id', $data['user']['id'])->where('id', $data['bank']['id'])->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'bank account not found',
    ];
    }

    if (empty($data['bank']['name'])) {
        return [
      'response' => 'success',
      'remark'   => 'nothing to do',
    ];
    } else {
        if (BANK::where('owner_id', $data['user']['id'])->where('id', $data['bank']['id'])->update(['name' => $data['bank']['name']])) {
            return [
        'response' => 'success',
        'remark'   => '1 field updated',
      ];
        } else {
            return [
        'response' => 'error',
        'remark'   => "that's an unexpected one...",
      ];
        }
    }
});

Route::delete('bank', function (Request $request) {
    $request = $request->json()->all();
    $data = $request['data'];

    if (empty($data['user']['id']) || empty($data['bank']['id'])) {
        return [
      'response' => 'error',
      'remark'   => 'missing some/all payload',
    ];
    }
});
