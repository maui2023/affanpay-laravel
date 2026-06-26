POST Create Bill https://app.affanpay.my/api/v1/bill
This API is used to create a bill. The generated bill can be directly used to initiate and receive payment collections.

Authorization
Token : generated from Token API
Request header
Accept: application/json
Parameter
Type
Description
category
string
(Optional) Category ID for the bill. This can be obtained in the Category section after logging into your account.
name
string
(Required) The name or title of the bill.
description
string
(Optional) A short description of the bill (maximum 200 characters).
html
string
(Optional) HTML content for the bill’s description. Must contain valid HTML tags.
amount
decimal
(Required) The total amount for the bill. Accepts up to 2 decimal places. Minimum value is 5.00.
fee_charge_payer
boolean
(Optional) Indicates whether the payer will bear the transaction fee. Value true/false
redirect_url
string
(Optional) URL to redirect the customer after payment completion.
callback_url
string
(Optional) Endpoint to receive asynchronous payment status updates (webhook).
external_ref
string
(Required) External reference or unique identifier for the bill in your system.
customer_name
string
(Required) Full name of the customer. Maximum 60 characters.
customer_email
(Required) Customer’s email address. Maximum 50 characters.
customer_phone
string
(Optional) Customer’s phone number. Maximum 20 characters.
source
(Optional) Source identifier for the bill creation. Maximum 40 characters.
expiry_date
string
(Optional) Expiry date of the bill. Must be today or later. Format: YYYY-MM-DD HH:MM:SS.
Example Code

    <?php

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://app.affanpay.my/api/v1/bill',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{            
            "name": "",
            "amount": "",
            "customer_name": "",
            "customer_email": ""            
        }',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer [token generated from Token API]'
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    echo $response;

                
Example Response

    {
        "success": true,
        "id": "pujsSUNLeIYo",
        "url": "https://app.affanpay.my/pujsSUNLeIYo"
    }
                    


#requirung letter
   <?php

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://app.affanpay.my/api/v1/requery?bill_id=wxUyT47ko3v',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Authorization: Bearer [token generated from Token API]'
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    echo $response;

                
Example Response

    {
        "data": {
            "id": "wxUyT47ko3v",
            "name": "Multi open amount",
            "payments": [
                {
                    "payment_ref": "AP01384263015313050521",
                    "status_code": 4,
                    "status": "No Action",
                    "nett_amount": " 0.00",
                    "payment_method": null
                },
                {
                    "payment_ref": "AP01384355115313050521",
                    "status_code": 3,
                    "status": "Failed",
                    "nett_amount": " 0.00",
                    "payment_method": "fpx"
                },
                {
                    "payment_ref": "AP01384088585413050521",
                    "status_code": 1,
                    "status": "Success",
                    "nett_amount": " 31.20",
                    "payment_method": "fpx"
                }
            ]
        }
    }