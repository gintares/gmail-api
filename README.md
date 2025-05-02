# gmail-api
gmail api to send emails 


I use gmail-api, php and curl to fetch message from gmail, and than to send it to recipient.
It works authentification with gmail, and the messages search.
It does not work sending messsage with multiple files attached.

I am getting this error:
 560___messagaes query response =

 {
  "error": {
    "code": 400,
    "message": "'raw' RFC822 payload message string or uploading message via /upload/* URL required",
    "errors": [
      {
        "message": "'raw' RFC822 payload message string or uploading message via /upload/* URL required",
        "domain": "global",
        "reason": "invalidArgument"
      }
    ],
    "status": "INVALID_ARGUMENT"
  }
}

