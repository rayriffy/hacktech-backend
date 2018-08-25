<?php

use App\USER;
use App\TRANSACTION;
use App\BANK;
use App\PROMPTPAY;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

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

  if(empty($request["user"]["id"]) || empty($request["user"]["name"]) || empty($request["user"]["citizenid"]) || empty($request["user"]["phone"]) || empty($request["account"]["id"]) || empty($request["account"]["balance"]) || empty($request["account"]["fingerprint"]) || empty($request["account"]["signature"]) || empty($request["account"]["pin"])) {
    return array(
      "response" => "error",
      "remark" => "missing some / all payload"
    );
  }

  if(USER::where('citizen_id', $request["user"]["citizenid"])->exists()) {
    return array(
      "response" => "error",
      "remark" => "citizenid is already exists"
    );
  }
  else if(USER::where('phone', $request["user"]["phone"])->exists()) {
    return array(
      "response" => "error",
      "remark" => "phone number is already exists"
    );
  }

  $userid = str_random(32);

  $number = '1234567890';
  $numberLength = strlen($number);
  $account_id = '';
  for ($i = 0; $i < 10; $i++) {
    $account_id .= $number[rand(0, $numberLength - 1)];
  }

  $user = new USER;
  $user->id = $userid;
  $user->name = $request["user"]["name"];
  $user->citizen_id = $request["user"]["citizenid"];
  $user->phone = $request["user"]["phone"];
  $user->account_id = $account_id;
  $user->balance = $request["account"]["balance"];
  $user->fingerprint = $request["account"]["fingerprint"];
  $user->signature = $request["account"]["signature"];
  $user->pin = $request["account"]["pin"];
  $user->save();

  return array(
    "response" => "success",
    "remark" => "user created at id " . $userid,
    "data" => array(
      "account" => array(
        "id" => $account_id
      ),
      "user" =>array(
        "id" => $userid
      )
    )
  );

});

Route::post('transaction/promptpay', function (Request $request) {
  $request = $request->json()->all();

  if (empty($request["note"]) || empty($request["sender"]["id"]) || empty($request["sender"]["amount"]) || empty($request["reciver"]["phone"])) {
    return array(
      "response" => "error",
      "remark" => "missing some / all payload"
    );
  }

  if (!(USER::where('id', $request["sender"]["id"])->exists())) {
    return array(
      "response" => "error",
      "remark" => "sender id not found"
    );
  }
  else if ($request["sender"]["amount"] <= 0) {
    return array(
      "response" => "error",
      "remark" => "amount must higher than 0"
    );
  }
  
  $sender_account = USER::select('balance', 'account_id')->where('id', $request["sender"]["id"])->first();
  $sender_new_balance = $sender_account["balance"] - $request["sender"]["amount"];

  if ($sender_new_balance < 0) {
    return array(
      "response" => "error",
      "remark" => "insufficient funds"
    );
  }

  if (!(USER::where('id', $request["sender"]["id"])->update(["balance" => $sender_new_balance]))) {
    return array(
      "response" => "error",
      "remark" => "cannot update sender funds"
    );
  }

  if(PROMPTPAY::where('phone', $request["reciver"]["phone"])->exists()) {
    $reciver_account = PROMPTPAY::where('phone', $request["reciver"]["phone"])->first();
    $reciver_new_balance = $reciver_account["balance"] + $request["sender"]["amount"];
  
    if (!(PROMPTPAY::where('phone', $request["reciver"]["phone"])->update(["balance" => $reciver_new_balance]))) {
      return array(
        "response" => "error",
        "remark" => "cannot update reciver funds"
      );
    }
  }
  else {
    $prompt = new PROMPTPAY;
    $prompt->phone = $request["reciver"]["phone"];
    $prompt->balance = $request["sender"]["amount"];
    $prompt->save();
  }


  $transaction_id = str_random(256);

  $last_transaction = TRANSACTION::all()->last();

  $trans = new TRANSACTION;
  $trans->hash = $transaction_id;
  $trans->sender_id = $request["sender"]["id"];
  $trans->sender_amount = $request["sender"]["amount"];
  $trans->reciver_phone = $request["reciver"]["phone"];
  $trans->note = $request["note"];
  $trans->type = "promptpay";
  $trans->prevhash = $last_transaction["hash"];
  $trans->save();

  return array(
    "response" => "success",
    "remark" => "transaction created and being in process",
    "data" => array(
      "transaction" => array(
        "id" => $transaction_id
      )
    )
  );

});

Route::post('transaction/bank', function(Request $request) {
  $request = $request->json()->all();

  if (empty($request["note"]) || empty($request["sender"]["id"]) || empty($request["sender"]["amount"]) || empty($request["reciver"]["account"]["id"]) || empty($request["reciver"]["account"]["bank"]) || empty($request["reciver"]["account"]["name"])) {
    return array(
      "response" => "error",
      "remark" => "missing some / all payload"
    );
  }

  if (!(USER::where('id', $request["sender"]["id"])->exists())) {
    return array(
      "response" => "error",
      "remark" => "sender id not found"
    );
  }
  else if ($request["sender"]["amount"] <= 0) {
    return array(
      "response" => "error",
      "remark" => "amount must higher than 0"
    );
  }
  
  $sender_account = USER::select('balance', 'account_id')->where('id', $request["sender"]["id"])->first();
  $sender_new_balance = $sender_account["balance"] - $request["sender"]["amount"];

  if ($sender_new_balance < 0) {
    return array(
      "response" => "error",
      "remark" => "insufficient funds"
    );
  }

  if (!(USER::where('id', $request["sender"]["id"])->update(["balance" => $sender_new_balance]))) {
    return array(
      "response" => "error",
      "remark" => "cannot update sender funds"
    );
  }

  if (USER::where('account_id', $request["reciver"]["account"]["id"])->exists()) {
    $reciver_account = USER::where('account_id', $request["reciver"]["account"]["id"])->first();
    $reciver_new_balance = $reciver_account["balance"] + $request["sender"]["amount"];

    if (!(USER::where('account_id', $request["reciver"]["account"]["id"])->update(["balance", $reciver_new_balance]))) {
      return array(
        "response" => "error",
        "remark" => "cannot update "
      );
    }
  }
  else {
    if(!(BANK::where('id', $request["reciver"]["account"]["id"])->exists())) {
      $bank = new BANK;
      $bank->id = $request["reciver"]["account"]["id"];
      $bank->name = $request["reciver"]["account"]["name"];
      $bank->provider = $request["reciver"]["account"]["bank"];
      $bank->balance = $request["sender"]["amount"];
    }
    else {
      $reciver_account = BANK::where('id', $request["reciver"]["account"]["id"])->first();
      $reciver_new_balance = $reciver_account["balance"] + $request["sender"]["amount"];
  
      if (!(BANK::where('id', $request["reciver"]["account"]["id"])->update(["balance", $reciver_new_balance]))) {
        return array(
          "response" => "error",
          "remark" => "cannot update "
        );
      }
    }
  }

  $transaction_id = str_random(256);

  $last_transaction = TRANSACTION::all()->last();

  $trans = new TRANSACTION;
  $trans->hash = $transaction_id;
  $trans->sender_id = $request["sender"]["id"];
  $trans->sender_amount = $request["sender"]["amount"];
  $trans->reciver_account_id = $request["reciver"]["account"]["id"];
  $trans->reciver_account_name = $request["reciver"]["account"]["name"];
  $trans->reciver_account_bank = $request["reciver"]["account"]["bank"];
  $trans->note = $request["note"];
  $trans->type = "bank";
  $trans->prevhash = $last_transaction["hash"];
  $trans->save();

  return array(
    "response" => "success",
    "remark" => "transaction created and being in process",
    "data" => array(
      "transaction" => array(
        "id" => $transaction_id
      )
    )
  );

});

Route::get('user/{id}', function ($id) {
  if (empty($id)) {
    return array(
      "response" => "error",
      "remark" => "missing some / all payload"
    );
  }

  if(!(USER::where('id', $id)->exists())) {
    return array(
      "response" => "error",
      "remark" => "user not found"
    );
  }

  $user = USER::where('id', $id)->first();

  return array(
    "response" => "success",
    "data" => array(
      "user" => array(
        "id" => $user["id"],
        "name" => $user["name"],
        "citizenid" => $user["citizen_id"],
        "phone" => $user["phone"]
      ),
      "account" => array(
        "id" => $user["account_id"],
        "balance" => $user["balance"],
        "fingerprint" => $user["fingerprint"],
        "signature" => $user["signature"],
        "pin" => $user["pin"]
      )
    )
  );
});

Route::get('transactions/{id}', function ($id) {
  if (empty($id)) {
    return array(
      "response" => "error",
      "remark" => "missing some / all payload"
    );
  }

  if (!(USER::where('id', $id)->exists())) {
    return array(
      "response" => "error",
      "remark" => "user not found"
    );
  }

  $user = USER::where('id', $id)->first();

  $sends = TRANSACTION::select('hash', 'sender_amount', 'note', 'created_at')->where('sender_id', $id)->orderBy('created_at', 'desc')->get();

  $recives = TRANSACTION::select('hash', 'sender_amount', 'note', 'created_at')->where('phone', $user["reciver_phone"])->orWhere('reciver_phone', $user["phone"])->orWhere('reciver_account_id', $user["account_id"])->orderBy('created_at', 'desc')->get();

  foreach ($sends as $send) {
    $res_send[] = array(
      "id" => $send["hash"],
      "amount" => $send["amount"],
      "note" => $send["note"],
      "created_at" => $send["created_at"]
    );
  }

  foreach ($recives as $recive) {
    $res_recive[] = array(
      "id" => $recive["hash"],
      "amount" => $recive["amount"],
      "note" => $recive["note"],
      "created_at" => $recive["created_at"]
    );
  }

  return array(
    "response" => "success",
    "data" => array(
      "send" => $res_send,
      "recive" => $res_recive
    )
  );

});

Route::get('transaction/{id}', function ($id) {
  if (empty($id)) {
    return array(
      "response" => "error",
      "remark" => "missing some / all payload"
    );
  }

  if (!(TRANSACTION::where('hash', $id)->exists())) {
    return array(
      "response" => "error",
      "remark" => "user not found"
    );
  }

  $trans = TRANSACTION::where('hash', $id)->first();

  if ($trans["type"] === "promptpay") {
    $res_reciver = array(
      "phone" => $trans["reciver_phone"]
    );
  }
  else {
    $res_reciver = array(
      "bank" => $trans["reciver_account_bank"],
      "name" => $trans["reciver_account_name"],
      "id" => $trans["reciver_account_id"]
    );
  }

  return array(
    "response" => "success",
    "data" => array(
      "id" => $trans["hash"],
      "sender" => array(
        "id" => $trans["sender_id"],
        "amount" => $trans["sender_amount"]
      ),
      "reciver" => $res_reciver,
      "note" => $trans["note"],
      "type" => $trans["type"],
      "previd" => $trans["prevhash"],
      "created_at" => $trans["created_at"]
    )
  );

});

Route::get('user/{id}', function ($id) {
  if (empty($id)) {
    return array(
      "response" => "error",
      "remark" => "missing some / all payload"
    );
  }

  if (!(USER::where('id', $id)->exists())) {
    return array(
      "response" => "error",
      "remark" => "user not found"
    );
  }

  $user = USER::where('id', $id)->first();

  return array(
    "response" => "success",
    "data" => array(
      "user" => array(
        "id" => $user["id"],
        "name" => $user["name"],
        "citizenid" => $user["citizen_id"],
        "phone" => $user["phone"]
      ),
      "account" => array(
        "id" => $user["account_id"],
        "balance" => $user["balance"],
        "fingerprint" => $user["fingerprint"],
        "signature" => $user["signature"],
        "pin" => $user["pin"]
      )
    )
  );

});