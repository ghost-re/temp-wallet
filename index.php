<?php
/**
 * Your bot token , Strongly recommended to do not change variable name.
 */
$jOaMU8fw = '';
if (!file_exists('BPT.php')) {
    copy('https://dl.bptlib.ir/BPT.php', 'BPT.php');
}
require 'BPT.php';

function saveDataToJsonFile($data, $filename) {
    $json = json_encode($data, JSON_PRETTY_PRINT);
    if (file_put_contents($filename, $json)) {
        return true;
    } else {
        return false;
    }
}

function readDataFromJsonFile($filename) {
    if (file_exists($filename)) {
        $json = file_get_contents($filename);
        return json_decode($json, true);
    } else {
        return null;
    }
}

function doesUserIdExist($array, $userId) {
    foreach ($array as $item) {
        if (isset($item['userId']) && $item['userId'] == $userId) {
            return true;
        }
    }
    return false;
}

function setUserStep($array, $userId, $step) {
    foreach ($array as $key => $item) {
        if (isset($item['userId']) && $item['userId'] == $userId) {
            $array[$key]['step'] = $step;
            $json = json_encode($array, JSON_PRETTY_PRINT);
            if (file_put_contents('userList.json', $json)) {
                return true;
            } else {
                return false;
            }
        }
    }
    return false;
}

function setUserWallet($array, $userId, $wallet) {
    foreach ($array as $key => $item) {
        if (isset($item['userId']) && $item['userId'] == $userId) {
            $array[$key]['wallet'] = $wallet;
            $json = json_encode($array, JSON_PRETTY_PRINT);
            if (file_put_contents('userList.json', $json)) {
                return true;
            } else {
                return false;
            }
        }
    }
    return false;
}

function getUserStep($array, $userId) {
    foreach ($array as $key => $item) {
        if (isset($item['userId']) && $item['userId'] == $userId) {
            return $array[$key]['step'];
        }
    }
    return false;
}

function getUserWallet($array, $userId) {
    foreach ($array as $key => $item) {
        if (isset($item['userId']) && $item['userId'] == $userId) {
            return $array[$key]['wallet'];
        }
    }
    return false;
}

function isMoreThanMin($amount, $minAmount)
{
    $checkPoint = false;
    if (stripos($amount, ".")) {
        $textArray = explode(".", $amount);
        $charCount = strlen($textArray[1]);
    } else {
        $charCount = 0;
    }
    if (stripos(strval($minAmount), ".")) {
        $minAmountArray = explode(".", strval($minAmount));
        $minAmountCharCount = strlen($minAmountArray[1]);
    } else {
        $minAmountCharCount = 0;
    }
    if ($charCount >= $minAmountCharCount) {
        $newText = intval(floatval($amount) * (10 ^ $charCount));
        $newMinAmount = intval($minAmount * (10 ^ $charCount));
        if ($newText >= $newMinAmount) {
            $checkPoint = true;
        } else {
            $checkPoint = false;
        }
    } else {
        $newText = intval(floatval($amount) * (10 ^ $minAmountCharCount));
        $newMinAmount = intval($minAmount * (10 ^ $minAmountCharCount));
        if ($newText >= $newMinAmount) {
            $checkPoint = true;
        } else {
            $checkPoint = false;
        }
    }

    return $checkPoint;
}

function getTrxMinAmount() {
  $changeNowApiKey = ""; // changenow.io api key
  $minAmount = json_decode(file_get_contents("https://api.changenow.io/v1/min-amount/trx_trx?api_key=" . $changeNowApiKey), true )["minAmount"];
  return $minAmount;
}

function generateWallet($fromCurrency, $fromNetwork, $donateAmount, $trxWallet)
{
    $coinType = "TRX"; 
    $changeNowApiKey = ""; // changenow.io api key
    
    $minAmountResponse = file_get_contents("https://api.changenow.io/v1/min-amount/" . strtolower($coinType) . "_trx?api_key=" . $changeNowApiKey);
    $minAmountData = json_decode($minAmountResponse, true);
    $minAmount = $minAmountData["minAmount"];

    
    if ($donateAmount >= $minAmount) {
        
        $url = "https://api.changenow.io/v2/exchange";
        $data = [
            "fromCurrency" => $fromCurrency, 
            "toCurrency" => "trx",
            "fromNetwork" => $fromNetwork,
            "toNetwork" => "trx",
            "fromAmount" => $donateAmount,
            "toAmount" => "",
            "address" => $trxWallet,
            "flow" => "standard",
            "type" => "direct",
            "useRateId" => false,
        ];
        $headers = [
            "Content-Type: application/json",
            "x-changenow-api-key: " . $changeNowApiKey,
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        curl_close($ch);
        $responseData = json_decode($response, true);

        
        if (isset($responseData["payinAddress"])) {
            $payinAddress = $responseData["payinAddress"];
            $transactionId = $responseData["id"];
            $resultOutput = [
                "payinAddress" => $payinAddress,
                "transactionId" => $transactionId,
                "donateAmount" => $donateAmount
            ];
            return $resultOutput;
        } else {
            
            return false;
            
        }
    } else {
        return false;
    }
}

function checkTransactionStatus($transactionId) {
  $changeNowApiKey = ""; // Your ChangeNow API key
  $url = "https://api.changenow.io/v1/transactions/$transactionId/$changeNowApiKey";
  $response = file_get_contents($url);
  $responseData = json_decode($response, true);

  // Return the transaction status
  return $responseData['status'] === "finished" ? true : false;
}

function isValidTRXAddress($address) {
    // Check if the address starts with 'T' and is 34 characters long
    if (preg_match('/^T[a-zA-Z1-9]{33}$/', $address)) {
        return true;
    } else {
        return false;
    }
}

class BPT_handler extends BPT {

    public function __construct(array $settings) {
        parent::__construct($settings);
    }

    public function callback_query($update){
        $callback = $update;
        $chatId = $callback["message"]["chat"]["id"];
        $chatType = $update["message"]["chat"]["type"];
        $userId = $callback['from']['id'];
        $firstName = $callback['from']['first_name'];
        $lastName = $callback['from']['last_name'] ?? null;
        $username = $callback['from']['username'] ?? null;
        $messageId = $update["message"]["message_id"];

        if (readDataFromJsonFile('userList.json')) {
            $userList = readDataFromJsonFile('userList.json');
        } else {
            $userList = [];
        }

        if (isset($callback["data"])) {
            $callbackData = $callback["data"];
            if ($callbackData === "mainMenu") {
                setUserStep($userList, $userId, "");
                $this->editMessageText([
                    "chat_id" => $userId,
                    "message_id" => $messageId,
                    'text' => 'Welcome back, ' . $firstName . '!',
                    "reply_markup" => $this->eKey([
                        "inline" =>
                        [
                            [
                              [
                                "💵 Generate Wallet",
                                "generateWallet"
                              ]
                            ],
                            [
                              [
                                "🔧 Settings",
                                "settings"
                              ]
                            ]
                        ],
                    ]),
                ]);
            } elseif (in_array($callbackData, ["generateWallet"])) {
                $userWallet = getUserWallet($userList, $userId);
                if (!empty($userWallet) && !is_null($userWallet)) {
                    setUserStep($userList, $userId, $callbackData);
                    $minAmount = getTrxMinAmount();
                    $this->editMessageText([
                        "chat_id" => $userId,
                        "message_id" => $messageId,
                        "text" => "🔦 Current Position: 💵 Generate Wallet\n\nEnter desired amount of TRX you want to recieve and press '✅ Submit' (Min: $minAmount trx)",
                        "parse_mode" => "html",
                        "disable_web_page_preview" => true,
                        "reply_markup" => $this->eKey([
                            "inline" => [
                                [
                                    ["✅ Submit", "trx_amount_submit"]
                                ],
                                [
                                    [
                                        "🏠 Main Menu", 
                                        "mainMenu"
                                    ]
                                ],
                            ],
                        ]),
                    ]);
                } else {
                    $this->editMessageText([
                        "chat_id" => $userId,
                        "message_id" => $messageId,
                        'text' => 'Welcome back, ' . $firstName . "!\n\n⚠️ First set your TRX wallet in '💸 Settings' and then try again!",
                        "reply_markup" => $this->eKey([
                            "inline" =>
                            [
                                [
                                  [
                                    "💵 Generate Wallet",
                                    "generateWallet"
                                  ]
                                ],
                                [
                                  [
                                    "🔧 Settings",
                                    "settings"
                                  ]
                                ]
                            ],
                        ]),
                    ]);
                }
            } elseif (in_array($callbackData, ["trx_amount_submit"])) {
                $userWallet = getUserWallet($userList, $userId);
                $userStep = getUserStep($userList, $userId);
                if (!empty(explode("_", $userStep)[1]) && explode("_", $userStep)[0] === "generateWallet") {
                        $amount = explode("_", $userStep)[1];
                        $minAmount = getTrxMinAmount();
                        if (isMoreThanMin($amount, $minAmount)) {
                            setUserStep($userList, $userId, "");
                            $payinData = generateWallet("trx", "trx", $amount, $userWallet);
                            $payinAddress = $payinData['payinAddress'];
                            $transactionId = $payinData['transactionId'];
                            $this->editMessageText([
                              "chat_id" => $userId,
                              "message_id" => $messageId,
                              "text" => "Now you can send following wallet for your customer to and ask him to send you $amount TRX:\n\n<code>$payinAddress</code>\n\nYou can press'✅ Check Transaction' button to verify if the transaction has been succesfully processed.\n⚠️ This wallet will be destructed after 30 minutes!\n\nIf you wish to return to Main Menu at any time, simply type the command /start.\n\nThank you for your cooperation!\n\n⚠️ Transaction state: Waiting...",
                              "parse_mode" => "html",
                              "disable_web_page_preview" => true,
                              "reply_markup" => $this->eKey([
                                  "inline" => [
                                      [
                                          ["✅ Check Transaction", "check_$transactionId" . "_" . "$amount"]
                                      ]
                                  ],
                              ]),
                          ]);
                        }
                }
            } elseif (in_array(explode("_", $callbackData)[0], ["check"])) {
                $transactionId = explode("_", $callbackData)[1];
                if (checkTransactionStatus($transactionId)) {
                    $amount = explode("_", $callbackData)[2];
                    $this->editMessageText([
                      "chat_id" => $userId,
                      "message_id" => $messageId,
                      "text" => "Dear $firstName,\n\nWe sincerely thank you for using our service!\n\nWe want to inform that you recived $amount TRX in your wallet.\n\nWarm regards, Ghost Resister!",
                      "parse_mode" => "html",
                      "disable_web_page_preview" => true,
                      "reply_markup" => $this->eKey([
                          "inline" => [
                              [
                                  ["🏠 Main Menu", "mainMenu"]
                              ]
                          ],
                      ]),
                  ]);
                }
                setUserStep($userList, $userId, $callbackData);
                $currency = explode("_", $callbackData)[1];
            } elseif (in_array(explode("_", $callbackData)[0], ["settings"])) {
                setUserStep($userList, $userId, "");
                $this->editMessageText([
                    "chat_id" => $userId,
                    "message_id" => $messageId,
                    "text" => '🔦 Current Position: 🔧 Settings',
                    "parse_mode" => "html",
                    "disable_web_page_preview" => true,
                    "reply_markup" => $this->eKey([
                        "inline" => [
                            [
                                ["💵 Set TRX Wallet", "setWallet"]
                            ],
                            [
                                [
                                    "🏠 Main Menu", 
                                    "mainMenu"
                                ]
                            ]
                        ],
                    ]),
                ]);
            } elseif (in_array(explode("_", $callbackData)[0], ["setWallet"])) {
                setUserStep($userList, $userId, $callbackData);
                $userWallet = getUserWallet($userList, $userId) ?? null;
                if (!is_null($userWallet)) {
                    $this->editMessageText([
                        "chat_id" => $userId,
                        "message_id" => $messageId,
                        "text" => "🔦 Current Position: 💵 Set TRX Wallet\n\n💵 Current Wallet: <code>$userWallet</code>\n\nEnter your TRX wallet and press '✅ Submit'",
                        "parse_mode" => "html",
                        "disable_web_page_preview" => true,
                        "reply_markup" => $this->eKey([
                            "inline" => [
                                [
                                    ["✅ Submit", "wallet_submit"]
                                ],
                                [
                                    [
                                        "🏠 Main Menu", 
                                        "mainMenu"
                                    ]
                                ],
                            ],
                        ]),
                    ]);
                } else {
                    $this->editMessageText([
                        "chat_id" => $userId,
                        "message_id" => $messageId,
                        "text" => "🔦 Current Position: 💵 Set TRX Wallet\n\nEnter your TRX wallet and press '✅ Submit'",
                        "parse_mode" => "html",
                        "disable_web_page_preview" => true,
                        "reply_markup" => $this->eKey([
                            "inline" => [
                                [
                                    ["✅ Submit", "wallet_submit"]
                                ],
                                [
                                    [
                                        "🏠 Main Menu", 
                                        "mainMenu"
                                    ]
                                ],
                            ],
                        ]),
                    ]);
                }
                
            } elseif (in_array(explode("_", $callbackData)[0], ["wallet"])) {
                $userStep = getUserStep($userList, $userId);
                $wallet = explode("_", $userStep)[1];
                if (isValidTRXAddress($wallet)) {
                    setUserWallet($userList, $userId, $wallet);
                    $this->editMessageText([
                        "chat_id" => $userId,
                        "message_id" => $messageId,
                        "text" => "Dear $firstName,\n\nWe sincerely thank you for using our service!\n\nWe want to inform that your TRX wallet checked and its valid.\nFrom now on you can easily generate temp-wallets!\n\nWarm regards, Ghost Resister!",
                        "parse_mode" => "html",
                        "disable_web_page_preview" => true,
                        "reply_markup" => $this->eKey([
                            "inline" => [
                                [
                                    ["🏠 Main Menu", "mainMenu"]
                                ]
                            ],
                        ]),
                    ]);
                }
            }
          }
        }

    public function message($update) {
        $message = $update;
        $chatId = $message['chat']['id'];
        $userId = $message['from']['id'];
        $messageId = $update["message_id"];
        $firstName = $message['from']['first_name'];
        $lastName = $message['from']['last_name'] ?? null;
        $username = $message['from']['username'] ?? null;
        if (readDataFromJsonFile('userList.json')) {
            $userList = readDataFromJsonFile('userList.json');
        } else {
            $userList = [];
        }

        if (isset($message['text'])) {
            $text = $message['text'];
            $command = explode(' ', $text)[0];
    
            if ($command === '/start') {
                setUserStep($userList, $userId, "");
                if (doesUserIdExist($userList, $userId)) {
                    // User is already registered
                    $this->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Welcome back, ' . $firstName . '!',
                        "reply_markup" => $this->eKey([
                            "inline" =>
                            [
                                [
                                  [
                                    "💵 Generate Wallet",
                                    "generateWallet"
                                  ]
                                ],
                                [
                                  [
                                    "🔧 Settings",
                                    "settings"
                                  ]
                                ]
                            ],
                        ]),
                    ]);
                } else {
                    $userList[] = [
                        "userId" => $userId,
                        "step" => ""
                    ];
                    // Register new user
                    if (saveDataToJsonFile($userList, 'userList.json')) {
                        $this->sendMessage([
                            'chat_id' => $chatId,
                            'text' => 'Welcome, ' . $firstName . '!',
                            "reply_markup" => $this->eKey([
                                "inline" =>
                                [
                                    [
                                      [
                                        "💵 Generate Wallet",
                                        "generateWallet"
                                      ]
                                    ],
                                    [
                                      [
                                        "🔧 Settings",
                                        "settings"
                                      ]
                                    ]
                                ],
                            ]),
                        ]);
                    } else {
                        $this->sendMessage([
                            'chat_id' => $chatId,
                            'text' => 'An error occurred while registering your user.'
                        ]);
                    }
                }
            } elseif (explode("_", getUserStep($userList, $userId))[0] === "setWallet" && empty(explode("_", getUserStep($userList, $userId))[1])) {
                setUserStep($userList, $userId, getUserStep($userList, $userId) . "_" . $text);
                $this->deleteMessage(['chat_id' => $chatId, 'message_id' => $messageId]);
            } elseif (explode("_", getUserStep($userList, $userId))[0] === "generateWallet" && empty(explode("_", getUserStep($userList, $userId))[1])) {
                setUserStep($userList, $userId, getUserStep($userList, $userId) . "_" . $text);
                $this->deleteMessage(['chat_id' => $chatId, 'message_id' => $messageId]);
            }
        }
    }

}
$BPT = new BPT_handler([
    "token" => $jOaMU8fw,
    "logger" => true,
    "log_size" => 10,
    "security" => false,
    "secure_folder" => true,
    "multi" => true,
    "split_update" => true,
    "array_update" => true,
    "db" => [
        "type" => "json",
        "file_name" => "BPT-DB.json",
    ],
    "auto_update" => true,
    "max_connection" => 40,
    "certificate" => null,
    "base_url" => "https://api.telegram.org/bot",
    "down_url" => "https://api.telegram.org/file/bot",
    "forgot_time" => 100,
    "receive" => "webhook",
    "handler" => true,
    "allowed_updates" => [
        "message",
        "edited_channel_post",
        "callback_query",
        "inline_query",
        "chat_member",
        "my_chat_member",
        "chat_join_request",
    ],
    "debug" => true,
]);
