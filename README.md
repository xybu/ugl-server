# Ugl-server

The server side code of project Ugl.

# Table of Contents

 - [Introduction](#introduction)
  - [Architecture](#architecture)
  - [Files](#files)
  - [Keys](#keys)
  - [Planning](#planning)
 - [Server API Notes](#server-api-notes)
  - [Types of Responses](#types-of-responses)
     - [Success](#1-success)
     - [Error](#2-error)
  - [Encryption](#encryption)
     - [One-way Encryption](#1-one-way-encryption)
     - [Two-way Encryption](#2-two-way-encryption)
 - [API Events](#api)
	 - [User Identities](#1-user-identities)
		 - [login](#1-login)
		 - [logout](#2-logout)
		 - [register](#3-register)
		 - [revokeToken](#4-revoketoken)
		 - [oauth_clientCallback](#5-oauth_clientcallback)
		 - [forgot_password](#6-forgot_password)
		 - [getMyPrefs](#7-getmyprefs)
		 - [setMyPrefs](#8-setmyprefs)
	 - [Groups](#2-groups)
		 - [List the groups a user joins](#1-list-groups-of)
		 - [Create a group](#2-create-a-group)
	 - [News](#3-news)

***

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
 * `app` (0750) stores the libraries, configuration files, controllers and models
	 * `controllers` stores the controllers
	 * `models` saves all the model files
	 * `views` stores the views (templates)
 * `assets` (0750) has the public accessible files like images, css, and javascript
 * `data` (0774) stores logs and files uploaded by users
	 * `log` (0755) system log
	 * `upload` (0774) user upload files
 * `tmp` if exists, will store temporary files like cache
 * `vendor` if exists, stores reference libraries

## Keys
 * Mail Server:
     * Refer to http://windows.microsoft.com/en-us/windows/outlook/send-receive-from-app
	 * Username: ugl@sige.us
	 * Password: Boilermaker!

***

# Server API Notes

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

* S1: Organize the information in the specified way to a string (event-dependent)
* S2: Use **AES-256-ECB** to encrypt the string got from step 1
	 * For android client, the private key to encrypt string should be `2IwehG2VEm3WhjLRMK/1aUPqAdW7KNvvRuskedxuOgOQ2jbO+wkKs5p5qJwh98GM`.
	 * Reference: http://stackoverflow.com/questions/10451068/encryption-mismatch-between-java-and-php
* S3: Encode the data from step 2 with Base64 and URLencode so that it can be sent out via HTTP protocol.
* S4: **If the method to send data is POST and this encryption is used, android client should post field `from=ugl_android`; web client should post field `from=ugl_web`.

In short `$str=urlencode(base64_encode(aes256($str, $key)))`

***

# API

## 1. User Identities

### 1) login
Log a user in. **To be updated to reflect token-based system.**

#### Request

| Name   | Description                        |
| ------ | ---------------------------------- |
| Method | POST                               |
| URL    | `api/login`                        |
| DATA   | `email`=abc@abc.cc&`password`=pass |

**Sanity check:**
* `email` is a valid email address (RFC 2822)
* `password` equals `pass` which is the one-way encrypted string of the original 
   password. The original password must be at least 6 chars long (server cannot check).

#### Response
For example,
```javascript
{
    "status": "success",
    "expiration": "2014-02-16T20:58:30+00:00",
    "data": {
		"user_id": 12345,
		"ugl_token": a_long_hashed_string_token
    }
}
```

The client should save the `user_id` and `ugl_token` and send them back to the server 
so that the server can identify the user as logged in. Think of the `ugl_token` as a 
special, volatile password associated with the user.

#### Associated Errors
* 100 - Email or password not provided
* 101 - Invalid email address
* 102 - User not found, or email and password do not match
* 103 - Password should not be empty
* 104 - Your account is temporarily on held for security concern. Please retry later or use social account to log in (too many failed attempts)

### 2) logout
Logout is a web client-only event. For mobile apps please use [revokeToken](#5-revoketoken) event.

#### Associated Errors

### 3) register
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

#### Response
Same as the log in response.

#### Associated Errors
* 100 - Email, password, or name not provided
* 101 - Invalid email address
* 102 - Password and confirm password do not match
* 103 - First name or last name should be non-empty words (either empty or contain special chars)
* 104 - Email already registered
* 105 - You must agree to the terms of services to sign up (field "agree" != "true")
* 106 - Password should be at least 6 chars

### 4) revokeToken
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

### 5) oauth_clientCallback
**Under Construction**

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

* `signature` should be the SHA-128 code of the string of the `auth` part above.
* `timestamp` has format of `2014-03-04T23:20:28+00:00` (ISO 8601)
* other fields should be self-evident given the information returned by the server.

And then use two-way encryption to encode the json text above. Let $data denote the encrypted data.

* Method: POST
* URL: /api/oauth/callback
* Data: `from`=ugl_android&`data`=$data

#### Response
Same as the log in response.

#### Associated Errors
TBD.

### 6) forgot_password
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

### 7) getMyPrefs
Get the preferences of the requester. He / she cannot see others' preferences.

#### Available User Preferences
* `autoAcceptInvitation`
	 * `1`: accept group invitations automatically
	 * `0`: send me an email when I receive invitations
* `showMyPublicGroups`
	 * `1`: allow others to view the **public** groups I am in
	 * `0`: no one can see what groups I am in

#### Request
| Name   | Description                       |
| ------ | --------------------------------- |
| Method | POST                              |
| URL    | `api/getMyPrefs`                  |
| DATA   | `user_id`=123&`ugl_token`=mytoken |

where the `DATA` is actually the credential to log the user in.

#### Response
TBA.

#### Associated Errors
* 1 - You should log in to perform the request (At least one of POST fields `user_id` and `ugl_token` is missing)
* 2 - Unauthorized request (`user_id` and `ugl_token` do not match the user)

### 8) setMyPrefs
Update the preferences of the requester. He / she cannot modify others' preferences.

#### Request
| Name   | Description                                             |
| ------ | ------------------------------------------------------- |
| Method | POST                                                    |
| URL    | `api/setMyPrefs`                                        |
| DATA   | `user_id`=123&`ugl_token`=mytoken&`autoAcceptInvitation`=1&`showMyPublicGroups`=0         |

POST each preference field in the format of `key=value`. Not necessary to POST fields of default values since server will append the missing fields with their default values.

#### Response
TBA.

#### Associated Errors
* 1 - You should log in to perform the request (At least one of POST fields `user_id` and `ugl_token` is missing)
* 2 - Unauthorized request (`user_id` and `ugl_token` do not match the user)

***

## 2. Group API

The data fields for group model are defined as below:

* **id**: the unique ID for the group.
* **alias**: A user-defined identifier of the group. It is a __non_empty__ string of 
	length no greater than 32, consisting of only *letters*, *digits*, '*-*', and '*_*' 
	(any match of `[^\-_a-zA-Z0-9]` should make the string invalid as group name).
* **description**: The description (brief introduction) of the group. All HTML escape 
	characters should be escaped by the client (when displaying the field, strings inside 
	"<" and ">" must no execute in face of XSS attacks, etc.). and the after-filtering string 
	has a maximum length of *150* chars. (Note: as a result, for example, "&", equivalent to "&amp;", has a length of 5.)
* **visibility**: 
	 * `0`: the group is private (only the group members can access the group)
	 * `1`: non-members can see the group, but cannot join it unless invited
	 * `2`: non-members can see the group, and can apply to join
	 * `64`: open group which everyone can see and join
* **creator_user_id**: The user id of the creator
* **num_of_users**: The number of users in the group
* **avatar_url**: The URL of the group avatar image. Empty means using default one.
* **users**: a list of users in the group, categorized by their roles

### 1) List Groups Of
List the groups a user has joined.

* When the requester is not the "target user" (the one whose groups to be listed), then upon this user's preference, either only his / her public groups are listed, or no groups are shown (Refer to `showMyPublicGroups` preference).
* When the requester is the target user, all his / her groups will be listed.

#### Request
| Name   | Description                                             |
| ------ | ------------------------------------------------------- |
| Method | POST                                                    |
| URL    | `/api/user/listGroup/@target_user_id`                   |
| DATA   | `user_id`=123&`ugl_token`=mytoken                       |

In the URL, `@target_user_id` is the id of the user whose groups are to be listed. For example, to get the groups of user whose id is `444`, the URL should be `api/listGroupsOf/444`.

`user_id` and `ugl_token` are the log in credentials of the requester. No guest can perform the operation.

#### Response
The API response to `/api/listGroupsOf/2` looks like (more fields may be added in the future)

```javascript
{
    "status": "success",
    "expiration": "2014-03-17T07:25:07+00:00",
    "data": {
        "count": 3,
        "groups": [
            {
                "id": "1",
                "visibility": "0",
                "alias": "admin",
                "description": "",
                "avatar_url": null,
                "tags": null,
                "creator_user_id": "3",
                "num_of_users": "1",
                "users": {
                    "admin": [
                        "2"
                    ]
                },
                "created_at": "0000-00-00 00:00:00"
            },
            {
                "id": "2",
                "visibility": "1",
                "alias": "ugl-dev",
                "description": "UGL Developers",
                "avatar_url": null,
                "tags": null,
                "creator_user_id": "2",
                "num_of_users": "1",
                "users": {
                    "admin": [
                        "2"
                    ],
                    "member": [
                        "3"
                    ]
                },
                "created_at": "0000-00-00 00:00:00"
            },
            {
                "id": "5",
                "visibility": "1",
                "alias": "pucs",
                "description": "&lt;script&gt;\r\n\talert(&quot;hello!&quot;)\r\n&lt;\/script&gt;\r\n&lt;b&gt;Abercrombie &amp; Fitch&lt;\/b&gt;",
                "avatar_url": null,
                "tags": "purdue cs test",
                "creator_user_id": "2",
                "num_of_users": "1",
                "users": {
                    "admin": [
                        "2"
                    ]
                },
                "created_at": "2014-03-17 06:07:30"
            }
        ]
    }
}
```

Notes:
* `data.data.count` is the number of groups in the list, and `data.data.groups` is the list of groups.
* Notice how the `description` is HTML encoded for security's sake. A client may need to decode it to display the text.
* `users` is a sub-array whose structure is `role` => `members`.

#### Associated Errors
* 1 - You should log in to perform the request (At least one of POST fields `user_id` and `ugl_token` is missing)
* 2 - Unauthorized request (`user_id` and `ugl_token` do not match the user)
* 3 - User id should be a number (`@target_user_id` is not numerical)
* 4 - The user does not exist (user whose id is `@target_user_id` does not exist)

### 2) Create a Group
Create a new group.

#### Request
| Name   | Description                                             |
| ------ | ------------------------------------------------------- |
| Method | POST                                                    |
| URL    | `/api/group/create`                                     |
| DATA   | `user_id`=123&`ugl_token`=mytoken&`alias`=group_name&`description`=group_description&`tags`=group_tags&`visibility`=1  |

URLEncode the fields when necessary.

#### Response

Upon success, server will return a success message, and the filtered group data:

* `description` will be HTML encoded and cut to the max length `150`.
* `tags` will be removed all neither-alphanumerical-nor-space characters and duplicate words, and then the tags get sorted`

```javascript
{
    "status": "success",
    "expiration": "2014-03-17T07:31:41+00:00",
    "data": {
        "message": "Successfully created a new group",
        "group_data": {
            "id": "12",
            "visibility": "2",
            "alias": "test-animation-effect",
            "description": "hello!\r\nLet's see the bounce!!!",
            "avatar_url": null,
            "tags": "delete notag oops purdue test",
            "creator_user_id": "2",
            "num_of_users": "1",
            "users": {
                "admin": [
                    "2"
                ]
            },
            "created_at": "2014-03-17 07:31:41"
        }
    }
}
```

#### Associated Errors

* 1 - You should log in to perform the request (At least one of POST fields `user_id` and `ugl_token` is missing)
* 2 - Unauthorized request (`user_id` and `ugl_token` do not match the user)
* 3 - Group name is not of the specified format. Plese check
* 4 - Group name "blah" is already taken
* 5 - Please choose a valid visibility option from the list

### 3) deleteGroup

### 4) editGroupProfile

### 5) editGroupMembers

## 3. News API

News model and associated APIs are introduced to cope with the requests when a user wants 
to see what is happening to the whole system, a group, the user him/herself, etc (e.g., 
"Xiangyu created a group named ugl-dev at 2014-03-14T01:22:33."). Think of `News` as events 
taking place in the system.

The data fields are defined as below:
* **id**: the News id
* **user_id**: the user who created the News
* **group_id**: the group associated with the News
* **visibility**: 
	 * `0` means private, user only news
	 * `1` means friend-wide visibility
	 * `2` means group-wide visibility
	 * `63` means public to everyone
* **category** (max length 32 chars):
	 * `group` means `created by group controller or API`
	 * `user` means `created by user controller or API`
	 * `wallet` means `created by wallet controller or API`
	 * `board` means `created by board controller or API`
	 * others TBA.
* **description**: the one-sentence description of the News. max length 384 chars.
* **created_at**: the timestamp when the News was created

News will be cleaned up every two months (or manually, TBD).

