# Ugl-server

The server side code of project Ugl.

## Table of Contents

* [Introduction](#introduction)
 * [Architecture](#architecture)
 * [File Structure](#file-structure)
 * [Planning](#planning)
* [Server API Document](#server-api-document)
 * [Types of Responses](#types-of-responses)
 * [Events](#events)
  * [get_SecurityQuestions](#get_securityQuestions)
  * [login](#login)
  * [logout](#logout)
  * [register](#register)

## Introduction

### Architecture
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
 * Moedel

## Server API Document

### Types of responses

All responses, regardless of types, will have header attribute of HTTP status 200.

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
 | Name   | Description                            |
 | ------ | -------------------------------------- |
 | Method | POST                                   |
 | URL    | `api/login`                            |
 | Data   | `email`=abc@abc.cc&`password`=pass     |

##### Response
TBA.

##### Associated Errors
* 100 - Email or password not provided
* 101 - Invalid email address
* 102 - User not found, or email and password do not match.

#### 3. logout

##### Associated Errors

#### 4. register

##### Associated Errors
* 100 - Email, password, or name not provided
* 101 - Invalid email address
* 102 - Password and confirm password do not match
* 103 - First name or last name is empty
* 104 - Email already registered
