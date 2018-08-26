<?php

use App\BANK;
use App\PROMPTPAY;
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

Route::post('register', function (Request $request) {
    $request = $request->json()->all();

    if (empty($request['user']['name']) || empty($request['user']['citizenid']) || empty($request['user']['phone']) || empty($request['account']['balance']) || empty($request['account']['fingerprint']) || empty($request['account']['signature']) || empty($request['account']['pin'])) {
        return [
      'response' => 'error',
      'remark'   => 'missing some / all payload',
    ];
    }

    if (USER::where('citizen_id', $request['user']['citizenid'])->exists()) {
        return [
      'response' => 'error',
      'remark'   => 'citizenid is already exists',
    ];
    } elseif (USER::where('phone', $request['user']['phone'])->exists()) {
        return [
      'response' => 'error',
      'remark'   => 'phone number is already exists',
    ];
    }

    $userid = str_random(32);

    $number = '1234567890';
    $numberLength = strlen($number);
    $account_id = '';
    for ($i = 0; $i < 10; $i++) {
        $account_id .= $number[rand(0, $numberLength - 1)];
    }

    $user = new USER();
    $user->user_id = $userid;
    $user->name = $request['user']['name'];
    $user->citizen_id = $request['user']['citizenid'];
    $user->phone = $request['user']['phone'];
    $user->account_id = $account_id;
    $user->balance = $request['account']['balance'];
    $user->fingerprint = $request['account']['fingerprint'];
    $user->signature = $request['account']['signature'];
    $user->pin = $request['account']['pin'];
    $user->save();

    return [
    'response' => 'success',
    'remark'   => 'user created at id '.$userid,
    'data'     => [
      'account' => [
        'id' => $account_id,
      ],
      'user' => [
        'id' => $userid,
      ],
    ],
  ];
});

Route::post('transaction/promptpay', function (Request $request) {
    $request = $request->json()->all();

    if (empty($request['note']) || empty($request['sender']['id']) || empty($request['sender']['amount']) || empty($request['reciver']['phone'])) {
        return [
      'response' => 'error',
      'remark'   => 'missing some / all payload',
    ];
    }

    if (!(USER::where('user_id', $request['sender']['id'])->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'sender id not found',
    ];
    } elseif ($request['sender']['amount'] <= 0) {
        return [
      'response' => 'error',
      'remark'   => 'amount must higher than 0',
    ];
    }

    $sender_account = USER::select('balance', 'account_id')->where('user_id', $request['sender']['id'])->first();
    $sender_new_balance = $sender_account['balance'] - $request['sender']['amount'];

    if ($sender_new_balance < 0) {
        return [
      'response' => 'error',
      'remark'   => 'insufficient funds',
    ];
    }

    if (!(USER::where('user_id', $request['sender']['id'])->update(['balance' => $sender_new_balance]))) {
        return [
      'response' => 'error',
      'remark'   => 'cannot update sender funds',
    ];
    }

    if (PROMPTPAY::where('phone', $request['reciver']['phone'])->exists()) {
        $reciver_account = PROMPTPAY::where('phone', $request['reciver']['phone'])->first();
        $reciver_new_balance = $reciver_account['balance'] + $request['sender']['amount'];

        if (!(PROMPTPAY::where('phone', $request['reciver']['phone'])->update(['balance' => $reciver_new_balance]))) {
            return [
        'response' => 'error',
        'remark'   => 'cannot update reciver funds',
      ];
        }
    } else {
        $prompt = new PROMPTPAY();
        $prompt->phone = $request['reciver']['phone'];
        $prompt->balance = $request['sender']['amount'];
        $prompt->save();
    }

    $transaction_id = str_random(256);

    $last_transaction = TRANSACTION::all()->last();

    $trans = new TRANSACTION();
    $trans->hash = $transaction_id;
    $trans->sender_id = $request['sender']['id'];
    $trans->sender_amount = $request['sender']['amount'];
    $trans->reciver_phone = $request['reciver']['phone'];
    $trans->note = $request['note'];
    $trans->type = 'promptpay';
    $trans->prevhash = $last_transaction['hash'];
    $trans->save();

    return [
    'response' => 'success',
    'remark'   => 'transaction created and being in process',
    'data'     => [
      'transaction' => [
        'id' => $transaction_id,
      ],
    ],
  ];
});

Route::post('transaction/bank', function (Request $request) {
    $request = $request->json()->all();

    if (empty($request['note']) || empty($request['sender']['id']) || empty($request['sender']['amount']) || empty($request['reciver']['account']['id']) || empty($request['reciver']['account']['bank']) || empty($request['reciver']['account']['name'])) {
        return [
      'response' => 'error',
      'remark'   => 'missing some / all payload',
    ];
    }

    if (!(USER::where('user_id', $request['sender']['id'])->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'sender id not found',
    ];
    } elseif ($request['sender']['amount'] <= 0) {
        return [
      'response' => 'error',
      'remark'   => 'amount must higher than 0',
    ];
    }

    $sender_account = USER::select('balance', 'account_id')->where('user_id', $request['sender']['id'])->first();
    $sender_new_balance = $sender_account['balance'] - $request['sender']['amount'];

    if ($sender_new_balance < 0) {
        return [
      'response' => 'error',
      'remark'   => 'insufficient funds',
    ];
    }

    if (!(USER::where('user_id', $request['sender']['id'])->update(['balance' => $sender_new_balance]))) {
        return [
      'response' => 'error',
      'remark'   => 'cannot update sender funds',
    ];
    }

    if (USER::where('account_id', $request['reciver']['account']['id'])->exists()) {
        $reciver_account = USER::where('account_id', $request['reciver']['account']['id'])->first();
        $reciver_new_balance = $reciver_account['balance'] + $request['sender']['amount'];

        if (!(USER::where('account_id', $request['reciver']['account']['id'])->update(['balance' => $reciver_new_balance]))) {
            return [
        'response' => 'error',
        'remark'   => 'cannot update ',
      ];
        }
    } else {
        if (!(BANK::where('bank_id', $request['reciver']['account']['id'])->exists())) {
            $bank = new BANK();
            $bank->bank_id = $request['reciver']['account']['id'];
            $bank->name = $request['reciver']['account']['name'];
            $bank->provider = $request['reciver']['account']['bank'];
            $bank->balance = $request['sender']['amount'];
            $bank->save();
        } else {
            $reciver_account = BANK::where('bank_id', $request['reciver']['account']['id'])->first();
            $reciver_new_balance = $reciver_account['balance'] + $request['sender']['amount'];

            if (!(BANK::where('bank_id', $request['reciver']['account']['id'])->update(['balance', $reciver_new_balance]))) {
                return [
          'response' => 'error',
          'remark'   => 'cannot update ',
        ];
            }
        }
    }

    $transaction_id = str_random(256);

    $last_transaction = TRANSACTION::all()->last();

    $trans = new TRANSACTION();
    $trans->hash = $transaction_id;
    $trans->sender_id = $request['sender']['id'];
    $trans->sender_amount = $request['sender']['amount'];
    $trans->reciver_account_id = $request['reciver']['account']['id'];
    $trans->reciver_account_name = $request['reciver']['account']['name'];
    $trans->reciver_account_bank = $request['reciver']['account']['bank'];
    $trans->note = $request['note'];
    $trans->type = 'bank';
    $trans->prevhash = $last_transaction['hash'];
    $trans->save();

    return [
    'response' => 'success',
    'remark'   => 'transaction created and being in process',
    'data'     => [
      'transaction' => [
        'id' => $transaction_id,
      ],
    ],
  ];
});

Route::get('user/{id}', function ($id) {
    if (empty($id)) {
        return [
      'response' => 'error',
      'remark'   => 'missing some / all payload',
    ];
    }

    if (!(USER::where('user_id', $id)->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'user not found',
    ];
    }

    $user = USER::where('user_id', $id)->first();

    return [
    'response' => 'success',
    'data'     => [
      'user' => [
        'id'        => $user['user_id'],
        'name'      => $user['name'],
        'citizenid' => $user['citizen_id'],
        'phone'     => $user['phone'],
      ],
      'account' => [
        'id'          => $user['account_id'],
        'balance'     => $user['balance'],
        'fingerprint' => $user['fingerprint'],
        'signature'   => $user['signature'],
        'pin'         => $user['pin'],
      ],
    ],
  ];
});

Route::get('transactions/{id}', function ($id) {
    if (empty($id)) {
        return [
      'response' => 'error',
      'remark'   => 'missing some / all payload',
    ];
    }

    if (!(USER::where('user_id', $id)->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'user not found',
    ];
    }

    $user = USER::where('user_id', $id)->first();

    $sends = TRANSACTION::select('hash', 'sender_amount', 'note', 'created_at')->where('sender_id', $id)->orderBy('created_at', 'desc')->get();

    $recives = TRANSACTION::select('hash', 'sender_amount', 'note', 'created_at')->where('reciver_phone', $user['phone'])->orWhere('reciver_account_id', $user['account_id'])->orderBy('created_at', 'desc')->get();

    foreach ($sends as $send) {
        $res_send[] = [
      'id'         => $send['hash'],
      'amount'     => $send['sender_amount'],
      'note'       => $send['note'],
      'created_at' => $send['created_at'],
    ];
    }

    foreach ($recives as $recive) {
        $res_recive[] = [
      'id'         => $recive['hash'],
      'amount'     => $recive['sender_amount'],
      'note'       => $recive['note'],
      'created_at' => $recive['created_at'],
    ];
    }

    return [
    'response' => 'success',
    'data'     => [
      'send'   => isset($res_send) ? $res_send : null,
      'recive' => isset($res_recive) ? $res_recive : null,
    ],
  ];
});

Route::get('transaction/{id}', function ($id) {
    if (empty($id)) {
        return [
      'response' => 'error',
      'remark'   => 'missing some / all payload',
    ];
    }

    if (!(TRANSACTION::where('hash', $id)->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'user not found',
    ];
    }

    $trans = TRANSACTION::where('hash', $id)->first();

    if ($trans['type'] === 'promptpay') {
        $res_reciver = [
      'phone' => $trans['reciver_phone'],
    ];
    } else {
        $res_reciver = [
      'bank' => $trans['reciver_account_bank'],
      'name' => $trans['reciver_account_name'],
      'id'   => $trans['reciver_account_id'],
    ];
    }

    return [
    'response' => 'success',
    'data'     => [
      'id'     => $trans['hash'],
      'sender' => [
        'id'     => $trans['sender_id'],
        'amount' => $trans['sender_amount'],
      ],
      'reciver'    => $res_reciver,
      'note'       => $trans['note'],
      'type'       => $trans['type'],
      'previd'     => $trans['prevhash'],
      'created_at' => $trans['created_at'],
    ],
  ];
});

Route::get('user/{id}', function ($id) {
    if (empty($id)) {
        return [
      'response' => 'error',
      'remark'   => 'missing some / all payload',
    ];
    }

    if (!(USER::where('user_id', $id)->exists())) {
        return [
      'response' => 'error',
      'remark'   => 'user not found',
    ];
    }

    $user = USER::where('user_id', $id)->first();

    return [
    'response' => 'success',
    'data'     => [
      'user' => [
        'id'        => $user['user_id'],
        'name'      => $user['name'],
        'citizenid' => $user['citizen_id'],
        'phone'     => $user['phone'],
      ],
      'account' => [
        'id'          => $user['account_id'],
        'balance'     => $user['balance'],
        'fingerprint' => $user['fingerprint'],
        'signature'   => $user['signature'],
        'pin'         => $user['pin'],
      ],
    ],
  ];
});

Route::get('users', function () {
    $users = USER::all();

    foreach ($users as $user) {
        $res_user[] = [
        'user' => [
          'id'        => $user['user_id'],
          'name'      => $user['name'],
          'citizenid' => $user['citizen_id'],
          'phone'     => $user['phone'],
        ],
        'account' => [
          'id'          => $user['account_id'],
          'balance'     => $user['balance'],
          'fingerprint' => $user['fingerprint'],
          'signature'   => $user['signature'],
          'pin'         => $user['pin'],
        ],
      ];
    }

    return [
      'response' => 'success',
      'data'     => $res_user,
    ];
});
