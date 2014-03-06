# Ugl-server

The server side code of project Ugl.

## Table of Contents

 - [Introduction](#introduction)
  - [Architecture](#architecture)
  - [File Structure](#file-structure)
  - [Planning](#planning)
 - [Server API Document](#server-api-document)
  - [Types of Responses](#types-of-responses)
     - [Success](#1-success)
     - [Error](#2-error)
  - [Encryption](#encryption)
     - [Password Encryption](#1-password-encryption)
  - [Events](#events)
     - [get_SecurityQuestions](#1-get_securityQuestions)
     - [login](#2-login)
     - [logout](#3-logout)
     - [register](#4-register)

## Introduction

### Architecture
Basic features:

 * written in PHP 5.5+ and run on Nginx 1.5+
 * follows MVC model with autoloader
 * generic caching mechanism
 * generic database class

Notes:

* keep an eye on facebook's HipHop VM
* Xiangyu owns the root of the server so the infrastructure can be changed when needed
* Web Client front-end is written in HTML5 and CSS3 built ono top of Bootstrap framework.

### File Structure
 * `app` stores the libraries, configuration files, controllers and models
 * `assets` has the public accessible files like images, css, and javascript
 * `data` stores logs and files uploaded by users
 * `tmp` if exists, will store temporary files like cache
 * `views` stores the view models
 * `vendor` if exists, stores reference libraries

### Planning

| Component           | Status        | Notes                           |
| ------------------- | ------------- | ------------------------------- |
| Autoloader          | Finished      | Part of basic libraries         |
| Dispatcher          | Finished      | Part of basic libraries         |
| Generic Controller  | In dev        | n/a.                            |
| User controller     | In dev        | Working on auth controller part |
| Generic Model       | In dev        |                                 |
| User model          | In dev        | Working on auth model           |
| Auth model          | In dev        | Working on auth model           |
| Group model         | Not started   | n/a                             |

### Next steps

 * Model the encryption mechanism for password

## Server API Document

### Types of responses

All responses, regardless of types, will have header attribute of HTTP status **200**.

There are two types of responses:

#### 1. Success

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

#### 2. Error

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

### Encryption

#### 1. Password Encryption

All non-decipherable passwords are expected to be encrypted in the following way before sending to the server:

* S1: Use SHA-1 to encode the string. Eg., `123456` becomes `7c4a8d09ca3762af61e59520943dc26494f8941b`
* S2: Use SHA-1 to encode the string got from S1. Eg., `69c5fcebaa65b560eaf06c3fbeb481ae44b8d618`
* S3: Base64 the string from S2. Eg., `NjljNWZjZWJhYTY1YjU2MGVhZjA2YzNmYmViNDgxYWU0NGI4ZDYxOA==`
* S4: To save space, use MD5 to encode the string from S3. Eg., `aaf53a928ca9baa6df03a5fe6e3c7b71`

In short, `$str = md5(base64_encode(sha1(sha1($str))));`

### Events

#### 1. get_securityQuestions
Return a list of security questions for user registration. Security questions are needed to reset the user password.

##### Request
| Name   | Description                 |
| ------ | --------------------------- |
| Method | GET                         |
| URL    | `api/get_securityQuestions` |

##### Response
Here is a sample response:
```javascript
{
    "status": "success",
    "expiration": "2014-03-06T03:01:37+00:00",
    "data": {
        "sec_q01": "What was your childhood nickname?",
        "sec_q02": "In what city or town was your first job?",
        "sec_q03": "Where were you when you had your first kiss?"
    }
}
```
Notes:
* This event can be cached for a long period of time.
* There is no error number associated with this event.

#### 2. login
Log a user in.

##### Request
**Format:**

* Method: `POST`
* URL: `api/login`
* Data: `email`=abc@abc.cc&`password`=pass

**Sanity check:**
* `email` is a valid email address (RFC 2822)
* `password` must be at least 6 chars long (server cannot check)

##### Response
TBA.

##### Associated Errors
* 100 - Email or password not provided
* 101 - Invalid email address
* 102 - User not found, or email and password do not match.
* 103 - Password should not be empty

#### 3. logout
Logout is a webclient-only event. For mobile apps please use revokeToken event.

##### Associated Errors

#### 4. register
Register an account.

##### Request
**Format:**

* Method: POST
* URL: `api/register`
* Data: `email`=something&`password`=pass&`confirm_pass`=pass&`first_name`=aaa&`last_name`=bbb&`agree`=true

**Sanity check:**

* `email` is a valid email address (RFC 2822)
* `password` must be at least 6 chars long (server cannot check)
* `password` and `confirm_pass` must match
* `first_name` and `last_name` must not be empty
* `agree` must be `true`

**Encryption:**

refer to the Encryption section.

##### Associated Errors
* 100 - Email, password, or name not provided
* 101 - Invalid email address
* 102 - Password and confirm password do not match
* 103 - First name or last name is empty
* 104 - Email already registered
* 105 - You must agree to the terms of services to sign up (field "agree" != "true")
* 106 - Password should be at least 6 chars

#### 5. revokeToken
Revoke the token used currently.

##### Request
**Format:**

* Method: POST
* URL: `api/revokeToken`
* Data: `user_id`=myid&`ugl_token`=mytoken

**Sanity check:**
none.

##### Response
A typical success message with data->message being "Token has been revoked.".

**Note:**
* This event is used to log out a user.
* Only when the user id and token really match will the token be reset.

##### Associated Errors
* 1 - Authentication fields missing (`user_id` or `ugl_token` is empty or null)
* 2 - User id should be a number (`user_id` is not a numeric value)