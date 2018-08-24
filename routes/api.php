<?php

use Illuminate\Http\Request;

use App\BANK;
use App\SHOP;
use App\TRANSACTION;
use App\USER;
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

  if (empty($data["user"]["id"])) {
      return [
        'response' => 'error',
        'remark'   => 'missing some/all payload',
    ];
  }

  if (USER::where('id', $data["user"]["id"])->exists()) {
    return array(
      "response" => "success"
    );
  }
  else {
    $user = new USER;
    $user->id = $data["user"]["id"];
    $user->save();

    return array(
      "response" => "success",
      "remark" => "new user created"
    );
  }

});

Route::post('bank', function (Request $request) {
  $request = $request->json()->all();
  $data = $request['data'];

  if (empty($data["user"]["id"]) || empty($data["bank"]["name"]) || empty($data["bank"]["provider"]) || empty($data["bank"]["accountid"])) {
    return array(
      "response" => "error",
      "remark" => "missing some/all payload"
    );
  }

  if(BANK::where('owner_id', $data["user"]["id"])->where('bank_provider', $data["bank"]["provider"])->where('bank_accountid', $data["bank"]["accountid"])->exists()) {
    return array(
      "response" => "error",
      "remark" => "bank account is already exists"
    );
  }
  else {
    $bank = new BANK;
    $bank->id = str_random(32);
    $bank->owner_id = $data["user"]["id"];
    $bank->bank_name = $data["bank"]["name"];
    $bank->bank_provider = $data["bank"]["provider"];
    $bank->bank_accountid = $data["bank"]["accountid"];
    $bank->save();

    return array(
      "response" => "success"
    );
  }

});

Route::post('money', function (Request $request) {
  $request = $request->json()->all();
  $data = $request['data'];

  if (empty($data["user"]["id"]) || empty($data["bank"]["id"]) || empty($data["bank"]["amount"])) {
    return array(
      "response" => "error",
      "remark" => "missing some/all payload"
    );
  }

  if (!(USER::where('id', $data["user"]["id"])->exists())) {
    return array(
      "response" => "error",
      "remark" => "user does not exist"
    );
  }

  if(!(BANK::where('owner_id', $data["user"]["id"])->where('id', $data["bank"]["id"])->exists())) {
    return array(
      "response" => "error",
      "remark" => "bank does not exist"
    );
  }

  $bank_account = BANK::select('amount')->where('owner_id', $data["user"]["id"])->where('id', $data["bank"]["id"])->first();
  $new_amount = $bank_account["amount"] + $data["bank"]["amount"];
  if(BANK::where('owner_id', $data["user"]["id"])->where('id', $data["bank"]["id"])->update(["amount" => $new_amount])) {
    return array(
      "response" => "success",
      "remark" => $data["bank"]["amount"] . " added to account " . $data["bank"]["id"] . " of " . $data["user"]["id"]
    );
  }
  else {
    return array(
      "response" => "error",
      "remark" => "that's an unexpected one..."
    );
  }

});

Route::post('shop', function (Request $request) {
  $request = $request->json()->all();
  $data = $request['data'];

  if (empty($data["user"]["id"]) || empty($data["shop"]["name"])) {
    return array(
      "response" => "error",
      "remark" => "missing some/all payload"
    );
  }

  if(SHOP::where('id', $data["user"]["id"])->exists()) {
    if(SHOP::where('id', $data["user"]["id"])->update(["name" => $data["shop"]["name"]])) {
      return array(
        "response" => "success"
      );
    }
    else {
      return array(
        "response" => "error",
        "remark" => "that's an unexpected one..."
      );
    }
  }
  else {
    $shop = new SHOP;
    $shop->id = $data["user"]["id"];
    $shop->name = $data["shop"]["name"];
    $shop->save();

    return array(
      "response" => "success"
    );
  }
});

Route::post('transaction', function (Request $request) {
   // TODO 
});

Route::get('bank/{user_id}', function ($user_id) {
  if (empty($user_id)) {
    return array(
      "response" => "error",
      "remark" => "missing some/all payload"
    );
  }

  if(!(BANK::where('owner_id', $user_id)->exists())) {
    return array(
      "response" => "error",
      "remark" => "bank not found"
    );
  }

  $banks = BANK::select('id', 'bank_name', 'bank_provider', 'bank_accountid')->where('owner_id', $user_id)->get();

  foreach($banks as $dat) {
    $bank[] = array(
      "id" => $dat["bank"],
      "name" => $dat["bank_name"],
      "provider" => $dat["bank_provider"],
      "accountid" => $dat["bank_accountid"]
    );
  }

  return array(
    "response" => "success",
    "data" => $bank
  );

});

Route::get('amount/{user_id}/{bank_id}', function ($user_id, $bank_id) {
  if(empty($user_id) || empty($bank_id)) {
    return array(
      "response" => "error",
      "remark" => "missing some/all payload"
    );
  }

  if(!(USER::where('id', $user_id)->exists())) {
    return array(
      "response" => "error",
      "remark" => "user not found"
    );
  }

  if(!(BANK::where('owner_id', $user_id)->where('id', $bank_id)->exists())) {
    return array(
      "response" => "error",
      "remark" => "bank account not found"
    );
  }

  $bank = BANK::select('amount')->where('owner_id', $user_id)->where('id', $bank_id)->first();

  return array(
    "response" => "success",
    "data" => $bank["amount"]
  );

});

Route::get('shop/{user_id}', function ($user_id) {
  if(empty($user_id)) {
    return array(
      "response" => "error",
      "remark" => "missing some/all payload"
    );
  }

  if(!(USER::where('id', $user_id)->exists())) {
    return array(
      "response" => "error",
      "remark" => "user not found"
    );
  }

  $shop = SHOP::select('name')->where('id', $user_id)->first();

  return array(
    "response" => "success",
    "data" => $shop["name"]
  );

});

Route::get('user/{user_id}', function ($user_id) {
  if(empty($user_id)) {
    return array(
      "response" => "error",
      "remark" => "missing some/all payload"
    );
  }

  if(!(USER::where('id', $user_id)->exists())) {
    return array(
      "response" => "error",
      "remark" => "user not found"
    );
  }

  $user = USER::select('id', 'name', 'email', 'phone', 'citizenid')->where('id', $user_id)->first();

  return array(
    "response" => "success",
    "data" => array(
      "id" => $user["id"],
      "name" => $user["name"],
      "email" => $user["email"],
      "phone" => $user["phone"],
      "citizenid" => $user["citizenid"]
    )
  );

});

Route::get('transaction/{trans_id}', function ($trans_id) {
  if(empty($trans_id)) {
    return array(
      "response" => "error",
      "remark" => "missing some/all payload"
    );
  }

  // TODO

});

Route::put('user', function (Request $request) {
  $request = $request->json()->all();
  $data = $request['data'];
  $count = 0;

  if(empty($data["user"]["id"])) {
    return array(
      "response" => "error",
      "remark" => "missing some/all payload"
    );
  }

  if(!(USER::where('id', $data["user"]["id"])->exists())) {
    return array(
      "response" => "error",
      "remark" => "user not found"
    );
  }

  if(!empty($data["user"]["name"])) {
    if(!(USER::where('id', $data["user"]["id"])->update(["name" => $data["user"]["name"]]))) {
      return array(
        "response" => "error",
        "remark" => "that's an unexpected one..."
      );
    }
    else {
      $count++;
    }
  }

  if(!empty($data["user"]["email"])) {
    if(!(USER::where('id', $data["user"]["id"])->update(["email" => $data["user"]["email"]]))) {
      return array(
        "response" => "error",
        "remark" => "that's an unexpected one..."
      );
    }
    else {
      $count++;
    }
  }

  if(!empty($data["user"]["phone"])) {
    if(!(USER::where('id', $data["user"]["id"])->update(["phone" => $data["user"]["phone"]]))) {
      return array(
        "response" => "error",
        "remark" => "that's an unexpected one..."
      );
    }
    else {
      $count++;
    }
  }

  if(!empty($data["user"]["citizenid"])) {
    if(!(USER::where('id', $data["user"]["id"])->update(["citizenid" => $data["user"]["citizenid"]]))) {
      return array(
        "response" => "error",
        "remark" => "that's an unexpected one..."
      );
    }
    else {
      $count++;
    }
  }

  return array(
    "response" => "success",
    "remark" => $count." fields updated"
  );

});

Route::put('bank', function(Request $request) {
  $request = $request->json()->all();
  $data = $request['data'];

  if(empty($data["user"]["id"]) || empty($data["bank"]["id"])) {
    return array(
      "response" => "error",
      "remark" => "missing some/all payload"
    );
  }

  if(!(USER::where('id', $data["user"]["id"])->exists())) {
    return array(
      "response" => "error",
      "remark" => "user not found"
    );
  }

  if(!(BANK::where('owner_id', $data["user"]["id"])->where('id', $data["bank"]["id"])->exists())) {
    return array(
      "response" => "error",
      "remark" => "bank account not found"
    );
  }

  if(empty($data["bank"]["name"])) {
    return array(
      "response" => "success",
      "remark" => "nothing to do"
    );
  }
  else {
    if(BANK::where('owner_id', $data["user"]["id"])->where('id', $data["bank"]["id"])->update(["name" => $data["bank"]["name"]])) {
      return array(
        "response" => "success",
        "remark" => "1 field updated"
      );
    }
    else {
      return array(
        "response" => "error",
        "remark" => "that's an unexpected one..."
      );
    }
  }

});

Route::delete('bank', function (Request $request) {
  $request = $request->json()->all();
  $data = $request['data'];

  if(empty($data["user"]["id"]) || empty($data["bank"]["id"])) {
    return array(
      "response" => "error",
      "remark" => "missing some/all payload"
    );
  }

});