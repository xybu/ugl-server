# Ugl-server

The server side code of project Ugl.

# Table of Contents

 - [Introduction](#introduction)
  - [Architecture](#architecture)
  - [Files](#files)
  - [Keys](#keys)
  - [Planning](#planning)
 - [Server API Document](#server-api-document)
  - [Types of Responses](#types-of-responses)
     - [Success](#1-success)
     - [Error](#2-error)
  - [Encryption](#encryption)
     - [One-way Encryption](#1-one-way-encryption)
     - [Two-way Encryption](#2-two-way-encryption)
  - [Events](#events)
     - [login](#1-login)
     - [logout](#2-logout)
     - [register](#3-register)
     - [revokeToken](#4-revoketoken)
     - [oauth_clientCallback](#5-oauth_clientcallback)
	 - [forgot_password](#6-forgot_password)

# Introduction

## Architecture
Basic features:

* written in PHP 5.5+ and run on Nginx 1.5+
* follows MVC model with autoloader
* generic caching mechanism
* generic database class

Notes:

* keep an eye on facebook's HipHop VM
* Xiangyu owns the root of the server so the infrastructure can be changed when needed
* Web Client front-end is written in HTML5 and CSS3 built ono top of Bootstrap framework.

## Files
 * `app` stores the libraries, configuration files, controllers and models
 	* `controllers` stores the controllers
 	* `models` saves all the model files
 	* `views` stores the views (templates)
 * `assets` has the public accessible files like images, css, and javascript
 * `data` stores logs and files uploaded by users
 * `tmp` if exists, will store temporary files like cache
 * `vendor` if exists, stores reference libraries

## Keys
 * Mail Server:
     * Refer to http://windows.microsoft.com/en-us/windows/outlook/send-receive-from-app
	 * Username: ugl@sige.us
	 * Password: Boilermaker!
  

## Planning

| Component           | Status        | Notes                           |
| ------------------- | ------------- | ------------------------------- |
| Autoloader          | Finished      | Part of basic libraries         |
| Dispatcher          | Finished      | Part of basic libraries         |
| Cacher              | Finished      | Part of basic libraries         |
| Generic Controller  | In dev        | n/a.                            |
| User controller     | In dev        | Working on auth controller part |
| Group contoller     | Not started   | n/a.                            |
| Generic Model       | In dev        |                                 |
| User model          | In dev        | Working on auth model           |
| Auth model          | In dev        | Working on auth model           |
| Group model         | Not started   | n/a                             |

# Server API Document

## Types of responses

All responses, regardless of types, will have header attribute of HTTP status **200**.

There are two types of responses:

### 1. Success

Server will respond to the client with json text of structure like:
```javascript
{
    "status": "success",
    "expiration": "2014-02-16T20:58:30+00:00",
    "data": {
    }
}
```
where

* `status` will always be `success` for successful responses.
* `expiration` is the time when this response becomes obsolete and must be re-fetched. It can be used for constructing client caching mechanism. The datetime format conforms to ISO-8601 date standard (see the example).
* The `data` property contains the actual data requested, which differs from request to request.

### 2. Error

```javascript
{
    "status": "error",
    "error": 1,
    "message": "Invalid email"
}
```
where

* `status` is always `error` for this type.
* `error` is the error number associated. One can refer to the document of a particular event for what the error number stands for.
* `message` is the message (pre-defined description) returned by the server.

The client should be able to distinguish between these two types of messages by the `status` field.

## Encryption

### 1. One-way Encryption

All non-decipherable passwords are expected to be encrypted in the following way before sending to the server:

* S1: Use SHA-1 to encode the string. Eg., `123456` becomes `7c4a8d09ca3762af61e59520943dc26494f8941b`
* S2: Use SHA-1 to encode the string got from S1. Eg., `69c5fcebaa65b560eaf06c3fbeb481ae44b8d618`
* S3: Base64 the string from S2. Eg., `NjljNWZjZWJhYTY1YjU2MGVhZjA2YzNmYmViNDgxYWU0NGI4ZDYxOA==`
* S4: To save space, use MD5 to encode the string from S3. Eg., `aaf53a928ca9baa6df03a5fe6e3c7b71`

In short, `$str = md5(base64_encode(sha1(sha1($str))));`

Client can make this a function so that it can be called conveniently.

### 2. Two-way encryption

All data that contains critical information and needs to be decoded should be encrypted in the following way before sending out:

* S1: Organize the information in the specified way to a string
* S2: Use **AES-256-ECB** to encrypt the string got from step 1
 * Private key undecided or to be defined (event specific?).
 * Reference: http://stackoverflow.com/questions/10451068/encryption-mismatch-between-java-and-php
* S3: Encode the data from step 2 with Base64 so that it can be sent out via HTTP protocol.

In short `$str=base64_encode(aes256($str, $key))`

## Events

### 1. login
Log a user in. **To be updated to reflect token-based system.**

#### Request
**Format:**

* Method: `POST`
* URL: `api/login`
* Data: `email`=abc@abc.cc&`password`=pass

**Sanity check:**
* `email` is a valid email address (RFC 2822)
* `password` must be at least 6 chars long (server cannot check)

#### Response
TBA.

#### Associated Errors
* 100 - Email or password not provided
* 101 - Invalid email address
* 102 - User not found, or email and password do not match.
* 103 - Password should not be empty

### 2. logout
Logout is a web client-only event. For mobile apps please use [revokeToken](#5-revoketoken) event.

#### Associated Errors

### 3. register
Register an account. **To be updated to reflect token-based system.**

#### Request
**Format:**

* Method: POST
* URL: `api/register`
* Data: `email`=something&`password`=pass&`confirm_pass`=pass&`first_name`=aaa&`last_name`=bbb&`agree`=true

**Sanity check:**

* `email` is a valid email address (RFC 2822)
* `password` must be at least 6 chars long (server cannot check)
* `password` and `confirm_pass` must match
* `first_name` and `last_name` must not be empty
* `first_name` and `last_name` must not contain any one of the following chars: `<`, `>`, `;`, `"`, `\n`, `\t`, TBA.
* `agree` must be `true`

**Encryption:**

refer to the Encryption section.

#### Associated Errors
* 100 - Email, password, or name not provided
* 101 - Invalid email address
* 102 - Password and confirm password do not match
* 103 - First name or last name should be non-empty words (either empty or contain special chars)
* 104 - Email already registered
* 105 - You must agree to the terms of services to sign up (field "agree" != "true")
* 106 - Password should be at least 6 chars

### 4. revokeToken
Revoke the token used currently.

#### Request
**Format:**

* Method: POST
* URL: `api/revokeToken`
* Data: `user_id`=myid&`ugl_token`=mytoken

**Sanity check:**
none.

#### Response
A typical success message with data->message being "Token has been revoked.".

**Note:**
* This event is used to log out a user.
* Only when the user id and token really match will the token be reset.

#### Associated Errors
* 1 - Authentication fields missing (`user_id` or `ugl_token` is empty or null)
* 2 - User id should be a number (`user_id` is not a numeric value)

### 5. oauth_clientCallback
native client-only API used for telling server that a user successfully authenticates 
the app with the oauth provider.

#### Request

Organize the oauth response information to json text of the following format:

```javascript
{
    "auth": {
        "provider": "The provider with which the user authenticated, eg., \"Facebook\"",
        "uid": "An identifier unique to the given provider, such as a Twitter user ID.",
        "info": {
            "name": "The display name of the user given by the provider. Usually a nickname or firstname",
            "email": "The email of the authenticating user.",
            "nickname": "nickname of the user returned by the provider",
            "first_name": "",
            "last_name": "",
            "location": "",
            "description": "",
            "image": "",
            "phone": "",
            "urls": {
                "facebook": "Eg., http:\/\/facebook.com\/uzyn.chua returned by Facebook",
                "website": "Eg., http:\/\/xybu.me"
            }
        },
        "credentials": {
            "token": "Supplied by OAuth and OAuth 2.0 providers, the access token.",
            "secret": "Supplied by OAuth providers, the access token secret."
        }
    },
    "timestamp": "Time (in ISO 8601 format) when this auth was prepared.",
    "signature": "see below"
}
```

where

* `signature` should be the string sha1("ugl_android" + `timestamp` above + `provider` above + `uid` above + `email` above)
* `timestamp` has format of `2014-03-04T23:20:28+00:00` (ISO 8601)
* other fields should be self-evident given the information returned by the server.

And then use two-way encryption to encode the json text above. Let $data denote the encrypted data.

* Method: POST
* URL: api/oauth_clientCallback
* Data: $data

#### Response
TBD.

#### Associated Errors
TBD.


### 6. forgot_password
Send an email to the user who requests to reset the password. 
When the user clicks the link in the email, web client will send this user a new password with email.
The UI will be handled by web client.
Android client should go to log in activity.

#### Request
| Name   | Description                 |
| ------ | --------------------------- |
| Method | POST                        |
| URL    | `api/resetPassword`         |
| DATA   | `email`=abc@def.com         |

#### Response
```javascript
{
    "status": "success",
    "expiration": "2014-03-06T03:01:37+00:00",
    "data": {
        "message": "An email containing the steps to reset password has been sent to your email account."    
    }
}
```

#### Associated Errors
* 1 - Please enter your email address
* 2 - Please try this operation later (too many requests in one session period)
* 3 - Invalid email address
* 4 - Email not registered
* 5 - Email did not send due to server error
* 6 - Email did not send due to server runtime error