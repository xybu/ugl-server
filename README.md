# Ugl-server

Project Ugl is a collective life management web application written in PHP+MySQL.

# Notices
 * Please deploy the Cookie-based authentication method. (March 23, 2014)

# Table of Contents

 - [Introduction](#introduction)
	 - [Architecture](#architecture)
	 - [Files](#files)
	 - [Installation](#installation)
	 - [Planning](#planning)
 - [Server API Notes](#server-api-notes)
	 - [Types of Responses](#types-of-responses)
		 - [Success](#1-success)
		 - [Error](#2-error)
	 - [Encryption](#encryption)
		 - [One-way Encryption](#1-one-way-encryption)
		 - [Two-way Encryption](#2-two-way-encryption)
 - [API Events](#api)
	 - [User API](#1-user-identities)
		 - [login](#1-login)
		 - [logout](#2-logout)
		 - [register](#3-register)
		 - [revokeToken](#4-revoketoken)
		 - [oauth_clientCallback](#5-oauth_clientcallback)
		 - [Forgot password](#6-forgot_password)
		 - [Change password](#9-change_password)
		 - [Get profile of a user](#7-get-profile-of-a-user)
		 - [Set profile for a user](#8-set-profile-for-a-user)
		 - [Upload a user avatar](#10-upload-user-avatar)
	 - [Group API](#2-group-api)
		 - [Get the groups a user joins](#1-list-groups-of)
		 - [Get the profile of a aroup](#6-get-the-profile-of-a-group)
		 - [Create a group](#2-create-a-group)
		 - [Delete, leave a group, or kick members](#3-delete-or-leave-a-group-or-kick-members)
		 - [Transfer ownership](#4-transfer-ownership-of-a-group)
		 - [Edit group profile](#5-edit-group-profile)
		 - [Invite users to join a group](#7-invite-users-to-join-a-group)
		 - [Apply to join a group](#8-apply-to-join-a-group)
		 - [Find groups by a keyword](#9-find-groups-by-a-keyword)
		 - [Manage Users of a Group](#10-manage-users-of-a-group)
	 - [Board and Discussion API](#3-board-and-discussion-api)
		 - [List Boards by User](#1-list-boards-by-user)
		 - [List Boards by Group](#2-list-boards-by-group)
		 - [Create a Board](#3-create-a-board)
		 - [Delete a Board](#4-delete-a-board)
		 - [Edit a Board](#5-edit-a-board)
		 - [Add a Post](#6-add-a-post)
		 - [Edit a Post](#7-edit-a-post)
		 - [Delete a Post](#8-delete-a-post)
	 - [Wallet and Record API](#4-wallet-and-record-api)
		 - [Create a wallet](#1-create-a-wallet)
		 - [Edit a wallet](#2-edit-a-wallet)
		 - [Delete a wallet](#3-delete-a-wallet)
		 - [List wallets of a user](#4-list-wallets-of-a-user)
		 - [List wallets of a group](#5-list-wallets-of-a-group)
		 - [Add one or more records](#6-add-records-to-a-wallet)
		 - [Edit a record](#7-edit-a-record)
		 - [Delete a record](#8-delete-a-record)
		 - [List records of a wallet](#9-list-records-of-a-wallet)
	 - [Shelf and Item API](#5-shelf-and-item-api)

# Introduction

## Functionality

The application aims to help people organize their group life via a "group hub". 
Some basic functionality include:

 * User login and registration, profile
 	 * Also supports logging in from OAuth providers
 * Users can form **groups**
 * Users and groups have public or private **boards** to write down messages and such
 * Users and groups have **bags** to manage their items and borrow and lend records
 * Users and groups have **wallets** to manage the group fund and keep track of the incomes and spendings

## Architecture

 * Generic C/S architecture and MVC model
 * Generic autoload and caching mechanism
 * Generic database class that enables to use more than MySQL database

## Files
 * `app` (0750) stores the libraries, configuration files, controllers and models
	 * `conf` saves the configuration files
	 * `controllers` stores the controllers
	 * `models` saves all the model files
	 * `views` stores the views (templates)
 * `assets` (0750) has the public accessible files like images, css, and javascript
	 * `css`
	 * `js`
	 * `img`
	 * `upload`
 * `data` (0774) stores logs and files uploaded by users
	 * `log` (0755) system log
	 * `upload` (0774) user upload files
 * `tmp` if exists, will store temporary files like cache and pre-uploading files
 * `vendor` if exists, stores reference libraries

## Installation
 
### Pre-requisites

 * Apache 2.2+ Or Nginx 1.5+ (recommended)
 	 * A sample Nginx configuration file is given.
 * A working mail server (MTA) on host OS
 	 * Configure sSMTP and use Hotmail:
 		 * https://wiki.debian.org/sSMTP
 		 * http://windows.microsoft.com/en-us/windows/outlook/send-receive-from-app
 * PHP 5.5+
 	 * PHP `mail()` function should be able to deliver emails via mail daemon of host OS. 
 * It is recommended to have at least one PHP cache extension enabled (`APCu`, `memcache`, `xcache`, `wincache`)
 	 * Otherwise the server app will use file-based caching mechanism.
 * It is strongly recommended to have `opcache` extension, or the equivalent part of `wincache` or `xcache` enabled.

### Set-up

## Licenses of 3rd-Party Components

 * Fat-Free Framework _3.2.1_: GPLv3 license
 * Opauth: MIT License
	 * Source code modified conforming to MIT License
 * Bootstrap _3.1.1_: MIT license by Twitter (c) 2014
 * jQuery:
	 * jQuery _1.11.0_: MIT license by jQuery project
	 * jQuery Address _1.6_: GPLv2 and MIT dual licenses
	 * jQuery Mousewheel _3.1.9_: MIT license by Brandon Aaron (http://brandon.aaron.sh)

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

# API

## 1. User API

The data fields for User model are defined as below:

* __id__ (int): the unique identifier for a user.
* __email__ (string): the email of the user, conforming to RFC 2822 standard but with a maximum length of 200 chars. Required.
* ___password__ (string): the password hash (not available to clients).
* __nickname__ (string): HTML-filtered-out string with maximum length of 100 chars.
* __first_name__ (string): HTML-filtered-out string with maximum length of 100 chars. Required.
* __last_name__ (string): HTML-filtered-out string with maximum length of 100 chars. Required.
* __avatar_url__ (string): The URL of the avatar image with max length of 300 chars.
* __phone__ (string): Phone number. At most 36 chars.
* __description__ (string): HTML-encoded string. At most 150 chars.
* ___preferences__ (array): The preferences of the user
	 * `autoAcceptInvitation`
		 * `true`: accept group invitations automatically
		 * `false` (default): send me an email when I receive invitations
	 * `showMyProfile`
		 * `true` (default): allow others to see my profile
		 * `false`: do not allow
	 * `showMyPublicGroups`
		 * `true` (default): allow others to view the **public** groups I am in
		 * `false`: no one can see what groups I am in

Notes:

* HTML-filtered-out string means removing `<`, `>`, `;`, `"`, `\n`, `\t` from the string.

### 1) login
Log a user in. **To be updated to reflect token-based system.**

#### Request

| Name   | Description                        |
| ------ | ---------------------------------- |
| Method | POST                               |
| URL    | `api/login`                        |
| DATA   | `email`=abc@abc.cc&`password`=pass&`from`=ugl_android |

Note:

* For other clients, `from` should be changed to the corresponding client identifier.

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
		"user_id": 12345
    }
}
```

__As of Mar 30, more information will be provided in the response.__

Notes:

* The client should save the `user_id` for later use. 
* In the returned HTTP response, there is a cookie named `ugl_user`. 
	 * The client must always include this cookie to pass server authentication.
	 * If the cookie fails authentication, an error will be given
	 * If the cookie is missing, the requester will be treated as guests
* After first logging in, client should fetch the profile of the user and cache the profile array.
	* When the profile array data gets changed, server will return a new profile array for the client to update local cache.

#### Associated Errors
* 100 - Email or password not provided
* 101 - Invalid email address
* 102 - User not found, or email and password do not match
* 103 - Password should not be empty
* 104 - Your account is temporarily on held for security concern. Please retry later or use social account to log in (too many failed attempts)

### 2) logout
Logout is a web client-only event. 

For mobile apps please use [revokeToken](#5-revoketoken) event.

#### Associated Errors

### 3) register

Create an account.

#### Request

**Format:**

* Method: POST
* URL: `api/register`
* Data: `from`=ugl_android&`email`=something&`password`=pass&`confirm_pass`=pass&`first_name`=aaa&`last_name`=bbb&`agree`=true

Note:

* For other clients, `from` should be changed to the corresponding client identifier.

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

#### Response

A typical success message with data->message being "Token has been revoked.".

**Note:**
* This event is used to log a user out.
* The `ugl_user` cookie must be valid in order to perform the request.

#### Associated Errors
* 1 - Authentication fields missing (Cookie `ugl_user` is missing)
* 2 - User id should be a number (`user_id` is not a numeric value)

### 5) oauth_clientCallback

Native client-only API used for telling server that a user successfully authenticates the app with an oauth provider.

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

 * 1 - Missing required POST fields (either `data` or `from`)
 * 2 - Unrecognized client
 * 3 - Failed to decrypt the information
 * 4 - `Email` does not exist in the fields

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

### 7) Get Profile of a User

Get the profile of the user.

__Last Update__: March 23, 2014

#### Request
| Name   | Description                       |
| ------ | --------------------------------- |
| Method | POST                              |
| URL    | `api/user/info/@target_user_id`   |
| DATA   | none                              |

* `@target_user_id` in the URL is the ID of the user to fetch profile.
* Don't forget the `ugl_user` cookie.

#### Response

Upon success, server will return something like
```javascript
{
    "status": "success",
    "expiration": "2014-03-21T04:07:32+00:00",
    "data": {
        "id": "5",
        "email": "xb@purdue.edu",
        "nickname": null,
        "first_name": "X",
        "last_name": "B",
        "avatar_url": "",
        "phone": null,
        "description": null,
        "created_at": "2014-03-17 11:13:55",
        "_preferences": {
            "autoAcceptInvitation": false,
            "showMyProfile": true,
            "showMyPublicGroups": true
        }
    }
}
```

* Field `_preferences` will show only if the requester is the target user.
* When fetching others' profile, if the target user does not allow others to view his / her profile, an error will occur.

#### Associated Errors
* 1 - You should log in to perform the request (Cookie `ugl_user` is missing)
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - Invalid user id (`@target_user_id` is not numerical)
* 4 - User not found (`@target_user_id` does not exist)
* 5 - The profile is set private (target user does not allow you to view his / her profile)

### 8) Set profile for a user

Update the profile and preferences for the requester.

To change the password for a user, use the API entry named `change_password`.

__Last Update__: March 23, 2014

#### Request

| Name   | Description                       |
| ------ | --------------------------------- |
| Method | POST                              |
| URL    | `/api/user/edit`                  |
| DATA   | `...`                             |

* `...` stands for `email`=new_email&`first_name`=aaaa&`last_name`=bbbb&`nickname`=nick&`avatar_url`=blah&`phone`=123&`description`=oops&`autoAcceptInvitation`=true|false&`showMyProfile`=true|false&`showMyPublicGroups`=true|false
* Any un-POSTed field will remain unchanged
* `email`, if changed, must be an email that is not registered yet
* `first_name` and `last_name` are non-empty string if POSTed
* empty string for `avatar_url` indicates to use the default one
* `description` will be HTML-encoded (see the description field for Group identity)
* For every preference field, simply POST in the format `key=value`.

#### Response

Upon success, the new user identity will be returned (see section [Get profile of a user](#7-get-profile-of-a-user) with the requester being the target user).

#### Associated Errors
* 1 - You should log in to perform the request (Cookie `ugl_user` is missing)
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - Invalid email address
* 4 - Email already registered
* 5 - First name or last name should be non-empty words

### 9) Change password

Change the password of a user, provided that the user knows the original password.

### 10) Upload User Avatar

Upload an image as the custom avatar.

#### Request

| Name   | Description                           |
| ------ | ------------------------------------- |
| Method | POST                                  |
| URL    | `/api/user/upload_avatar`             |
| DATA   | ...                                   |

* The image must be of `JPG`, `PNG`, or `GIF` format, and the file size must not exceed `100KiB`.
* The name of the file field to POST (`...`) does not matter.
* Server only accepts the FIRST file uploaded.
* Clients may provide crop and preview panels to let the user customize the image before uploading.
* To send POST multipart/data entry in Android, refer to http://stackoverflow.com/questions/2017414/post-multipart-request-with-android-sdk

#### Response

If the uploaded file is a parse-able image, server will transform it to `PNG` format. crop it with a maximum dmiension of `150`x`150`, and compress it as much as possible (note: PNG is loseless). 
(As a result, GIF frames will be lost.)

Upon success, server returns the new avatar url and client should update local cache to reflect it.

For example, this returned data means the new `avatar_url` is `assets/upload/avatars/user_5.png`, a path relative to the server URI.
```javascript
{
    "status": "success",
    "expiration": "2014-03-23T04:59:22+00:00",
    "data": {
        "avatar_url": "assets\/upload\/avatars\/user_5.png"
    }
}
```

#### Associated Errors

* 1 - You should log in to perform the request (Cookie `ugl_user` is missing)
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - File upload failed. Please check if the file is an image of JPEG, PNG, or GIF format with size no more than 100KiB.

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
* **status**: 
	 * `0`: The group is closed (deleted)
	 * `1`: The group is inactive (users can access info but cannot change it; admin can re-active it)
	 * `2`: The group is private (invisible to outsiders)
	 * `3`: The group is public (data accessible to everyone; specific permissions are decided by the role of the requester)
* **creator_user_id**: The user id of the creator
* **num_of_users**: The number of users in the group
* **avatar_url**: The URL of the group avatar image. Empty means using default one.
* **users**: a list of users in the group, categorized by their roles
* **_preferences**: (Private field) the group preference settings
	 * `autoApproveApplication`: if set to `1`, the group will add applicants directly to `member` role. Otherwise he will be added to `pending` role.

### 1) List Groups Of
List the groups a user has joined.

* When the requester is not the "target user" (the one whose groups to be listed), then upon this user's preference, either only his / her public groups are listed, or no groups are shown (Refer to `showMyPublicGroups` preference).
* When the requester is the target user, all his / her groups will be listed.

#### Request
| Name   | Description                                             |
| ------ | ------------------------------------------------------- |
| Method | POST                                                    |
| URL    | `/api/user/listGroup/@target_user_id`                   |
| DATA   | none                                                    |

In the URL, `@target_user_id` is the id of the user whose groups are to be listed. For example, to get the groups of user whose id is `444`, the URL should be `api/listGroupsOf/444`.

`user_id` and `ugl_token` are the log in credentials of the requester. No guest can perform the operation.

#### Response
The API response to `/api/user/listGroup/2` looks like (more fields may be added in the future)

__Example Outdated.__

```javascript
{
    "status": "success",
    "expiration": "2014-03-17T07:25:07+00:00",
    "data": {
        "count": 3,
        "groups": [
            {
                "id": "1",
                "status": "1",
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
                "status": "2",
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
                "status": "3",
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
* `data.count` is the number of groups in the list, and `data.groups` is the list of groups.
* Notice how the `description` is HTML encoded for security's sake. A client may need to decode it to display the text.
* `users` is a sub-array whose structure is `role` => `members`.
* Group preference fields will not list in the array. To get the group preference, the request must be an admin requesting the group info (see getInfo API).

#### Associated Errors
* 1 - You should log in to perform the request (Cookie `ugl_user` is missing)
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - User id should be a number (`@target_user_id` is not numerical)
* 4 - The user does not exist (user whose id is `@target_user_id` does not exist)

### 2) Create a Group
Create a new group.

#### Request
| Name   | Description                                             |
| ------ | ------------------------------------------------------- |
| Method | POST                                                    |
| URL    | `/api/group/create`                                     |
| DATA   | `alias`=group_name&`description`=group_description&`tags`=group_tags&`status`=1  |

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
            "status": "2",
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

* 1 - You should log in to perform the request (Cookie `ugl_user` is missing)
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - Group name is not of the specified format. Plese check
* 4 - Group name "blah" is already taken
* 5 - Please choose a valid status option from the list


### 3) Delete or Leave a Group or Kick Members

This API combines two operations of leaving and deleting a group into one, given the different combinations of parameters.

#### Request
| Name   | Description                               |
| ------ | ----------------------------------------- |
| Method | POST                                      |
| URL    | `/api/group/leave`                        |

* POST data `group_id`=target_group_id
* When the creator leaves the group, the group will be removed (deleted)
	 * He / she can choose to notify all group members of the shutdown by POSTing a parameter "`notify`=true"
* When an admin (or whoever has "manage" permission) wants to kick a group user other than himself, also POST a parameter "`target_user_id`=user_to_kick"
* No one can kick the creator unless he / she is the creator, but the creator doing so means deleting the group
* Uncaught invalid requests will remove the requester from the group if he is in it
* Kicking unregistered user will be ignored by server, but the client will get a success message
* To kick more than one users from the group, POST `target_user_id` parameter like "`target_user_id`=1,2,44,7" (this will kick users whose IDs are in the list of the group)
	 * if the creator's ID is in the list, he / she will NOT get kicked
	 * if an user ID is unregistered or is not in the group, kicking this user makes NO changes to the group
	 * invalid IDs in the list (e.g., `aa` as in `target_user_id=aa,123`) will be ignored, the rest of the operation will be performed
* An admin kicking himself will be considered as a "kick" operation instead of a "leave" operation
	 
#### Response
Upon success, the requester will receive one of the following depending on the particular request

* `The group is now closed` (The creator deleted the group)
* `You have successfully kicked the user` (an admin kicked some user)
* `You have successfully left the group` (a user left the group)

#### Associated Errors

* 1 - You should log in to perform the request (Cookie `ugl_user` is missing)
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - Group id not specified (`group_id` is not POSTed)
* 4 - Invalid group id (`group_id` is not a number)
* 5 - Group not found
* 6 - You are not in the group (A guest cannot perform this operation of course...)
* 7 - You cannot kick the creator (No one but the creator can kick the creator)
* 8 - Target user id should be a number (__deprecated__)
* 9 - You are the creator. Please grant yourself "manage" permission before leaving the group (The creator customized the role system in a wrong way...)

### 4) Transfer Ownership
This API sets a new creator for this group, other information remaining unchanged.

#### Request

| Name   | Description                               |
| ------ | ----------------------------------------- |
| Method | POST                                      |
| URL    | `/api/group/change_creator`               |
| DATA   | `group_id`=123&`new_creator_id`=44        |

#### Response

Upon success, it returns the new group information (the role array and `creator_user_id` are updated).

```javascript
//TODO: To be fetched
```

#### Associated Errors

* 1 - You should log in to perform the request (Cookie `ugl_user` is missing)
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - Group id not specified (`group_id` is not POSTed)
* 4 - Invalid group id (`group_id` is not a number)
* 5 - Group not found
* 6 - Only the creator can transfer ownership of the group
* 7 - The new creator user is unset
* 8 - The new creator user not found
* 9 - The new creator is not in the group


### 5) Edit Group Profile

Edit the profile of the group.

#### Request
| Name   | Description                                             |
| ------ | ------------------------------------------------------- |
| Method | POST                                                    |
| URL    | `/api/group/edit`                                     |
| DATA   | `group_id`=123&`alias`=group_name&`description`=group_description&`tags`=group_tags&`status`=new_vis  |

#### Response

Upon success, server will return a success message with the new group data

```javascript
{
    "status": "success",
    "expiration": "2014-03-19T01:35:53+00:00",
    "data": {
        "message": "You have successfully updated group profile.",
        "group_data": {
            "id": "2",
            "status": "1",
            "alias": "beta-testers",
            "description": "Lorem ipsum Dadipiscing sdfec id lectus vel odio auctor viverra. Pellentesque eu dui nib.\r\n&lt;script&gt;&amp;&amp;test&amp;&amp;&lt;\/script&gt;",
            "avatar_url": null,
            "tags": "cs307 purdue ugl23",
            "creator_user_id": "5",
            "num_of_users": "1",
            "users": {
                "admin": [
                    "5"
                ]
            },
            "created_at": "2014-03-18 22:32:48"
        }
    }
}
```

#### Associated Errors

* 1 - You should log in to perform the request (Cookie `ugl_user` is missing)
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - Group id not specified (`group_id` is not POSTed)
* 4 - Invalid group id (`group_id` is not a number)
* 5 - Group not found
* 6 - Unauthorized request (the role of the requester in the group does not have manage permission)
* 7 - Group name is not of the specified format. Plese check
* 8 - Please choose a valid status option from the list

### 6) Get the Profile of a Group

Return the group information and the role permissions of the requester.

#### Request

| Name   | Description                                           |
| ------ | ----------------------------------------------------- |
| Method | POST                                                  |
| URL    | `/api/group/info`                                     |
| DATA   | `group_id`=123      |

* If `user_id` is missing, then the role will be **guest**
	 * If the group does not allow guests to view its profile, an error message will return
* If `user_id` is provided, `ugl_token` must match to log the user in
	 * If the token does not match, an error message will return instead of treating it as a guest
* `group_id` is REQUIRED. 

#### Response

Upon success, a JSON object like the following will be returned:

```javascript
{
    "status": "success",
    "expiration": "2014-03-19T04:20:09+00:00",
    "data": {
        "my_permissions": {
            "role_name": "admin",
            "view_profile": true,
            "apply": false,
            "view_board": true,
            "new_board": true,
            "edit_board": true,
            "del_board": true,
            "post": true,
            "comment": true,
            "delete": true,
            "edit": true,
            "manage": true
        },
        "group_data": {
            "id": "2",
            "status": "1",
            "alias": "beta-testersZ888",
            "description": "Lorem ipsum Dadipiscing sdfec id\r\n&lt;script&gt;&amp;&amp;test&amp;&amp;&lt;\/script&gt;",
            "avatar_url": null,
            "tags": "bkah cs307 new purdue",
            "creator_user_id": "5",
            "num_of_users": "1",
            "users": {
                "admin": [
                    "5"
                ]
            },
            "created_at": "2014-03-18 22:32:48"
        }
    }
}
```

* `my_permissions` is the permission of the role of the requester in the group
	 * For example, the requester in the data above is an **admin** in the group (Refer to the permission definition for more details)
* `group_data` is the group data as always
* If the requester has `manage` permission
	 * In `group_data` will be listed a private field named `_preferences` which has the group preferences in it.
	 * In `users` field, the array under each role will become an array of user profiles (__EXAMPLE_TO_BE_UPDATED__)
* Be sure to cache the information well since this is a very time-consuming operation

#### Associated Errors

* 1 - You should log in to perform the request (Only when `user_id` is given) 
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - Group id not specified (`group_id` is not POSTed)
* 4 - Invalid group id (`group_id` is not a number)
* 5 - Group not found
* 6 - You cannot access the group (The user cannot access the profile of the group)

### 7) Invite users to join a group

Invite some people to join the group.

#### Request

| Name   | Description                                           |
| ------ | ----------------------------------------------------- |
| Method | POST                                                  |
| URL    | `/api/group/invite`                                   |
| DATA   | `group_id`=12&`invite`=a@b.com,c@d.com,ee@ff.edu      |

Note that `@` will be encoded to `%2c`.

**Sanity Check**

* Clients should get a list of email addresses from the user, remove invalid or duplicate items, and then send the request to API.
* It is recommended that the user invite 10 email addresses per invitation.
* The invitation list must be non-empty.

#### Responses

```javascript
{
    "status": "success",
    "expiration": "2014-03-24T04:48:36+00:00",
    "data": {
        "message": "Invitation sent to xybu.subscription@live.com.",
        "skipped": [
            "xb@purdue.edu (already a member)"
        ]
    }
}
```

* `message` contains the list of emails that will receive the invitation.
* `skipped` is an array of strings of email addresses and their reason to be skipped.

#### Associated Errors

* 1 - You should log in to perform the request (Must provide the authentication cookie `ugl_user`) 
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - Group id not specified (`group_id` is not POSTed)
* 4 - Invalid group id (`group_id` is not a number)
* 5 - Group not found
* 6 - Unauthorized request (the requester must have `manage` permission)
* 7 - Invitation list is not specified
* 8 - Email did not send due to server error
* 9 - Email did not send due to server runtime error

### 8) Apply to Join a Group

Let a user that is not a group member apply to join a group.

#### Request

| Name   | Description                                    |
| ------ | ---------------------------------------------- |
| Method | POST                                           |
| URL    | `/api/group/apply`                             |
| DATA   | `group_id`=12                                  |

`group_id` is the id of the group to join.

Don't forget the cookie `ugl_user` when sending request.

#### Response

* If the group automatically approves of applications, a success message _You have joined the group_ is returned.
* If not, then a success message _You have applied to the group_ is returned.

#### Associated Errors

* 1 - You should log in to perform the request (Must provide the authentication cookie `ugl_user`) 
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - Group id not specified (`group_id` is not POSTed)
* 4 - Invalid group id (`group_id` is not a number)
* 5 - Group not found
* 6 - You already applied to the group or are already a member
* 7 - You cannot apply to join the group (permission denied)

### 9) Find group by a keywords

Get a list of groups that match certain keyword.

#### Request
| Name   | Description                                    |
| ------ | ---------------------------------------------- |
| Method | POST                                           |
| URL    | `/api/group/find`                              |
| DATA   | `keyword`=something                            |

Cookie `ugl_user` is required to perform the request (Currently so. Changes may pend.)

`keyword` must be a HTML-filtered string. Currently the API does not handle keyword combinations.

#### Response

Upon success, an example of searching for keyword `tester` could be

```javascript
{
    "status": "success",
    "expiration": "2014-03-25T03:04:22+00:00",
    "data": {
        "count": 1,
        "groups": [
            {
                "id": "3",
                "status": "3",
                "alias": "public-tester",
                "description": "This is a dummy public tester group.",
                "avatar_url": null,
                "tags": "hoho tester",
                "creator_user_id": "5",
                "num_of_users": "1",
                "users": {
                    "admin": [
                        "5"
                    ]
                },
                "created_at": "2014-03-25 01:00:05"
            }
        ]
    }
}
```

Notes:

* Only public groups will be returned.
* No result is an error instead of returning count of 0.
* Currently it will return at most **15** results.

#### Associated Errors

* 1 - You should log in to perform the request (Must provide the authentication cookie `ugl_user`) 
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - Empty keyword
* 4 - No group match the given keyword

### 10) Manage Users of a Group

## 3. Board and Discussion API

 * **Board** is the container of **discussions**  (i.e., "topics", "posts" in the context of blogs or forums.)
	 * Properties:
		 * `id` (auto_increment)
		 * `title` (required): A user-defined identifier of the board
			 * Regex is `^[\w\d][-\w\d_ ]{0,31}$` and consecutive whitespaces will be combined to one.
			 * No more than 32 chars.
			 * Unique to the group, or unique in the private boards of a user.
		 * `user_id` (required): The owner of the board.
		 * `group_id` (optional): If the board belongs to a group, this value will be the id of the group.
		 * `description` (optional): HTML-escaped string with a max length of 70 chars.
		 * `created_at` (auto generated)
		 * `last_active_at` (auto generated)
 * A **discussion** consists of a root post and zero or more reply posts.
	 * A discussion can be pinned at the top of discussion list.
 
 
### 1) List Boards by User

### 2) List Boards by Group

### 3) Create a Board

Create a new board.

#### Request
| Name   | Description                                      |
| ------ | ------------------------------------------------ |
| Method | POST                                             |
| URL    | `/api/board/create`                              |
| DATA   | `title`=board_title&`description`=description&`group_id`=123 |

Authentication cookie `ugl_user` is required to perform the operation.

Notes:

 * The field `group_id` is needed only when the board is created for a group.
	 * When creating a board for a group, `new_board` permission must be `true` for the user's role in the group.
	 * Omitting `group_id` or setting `group_id` to `0` means the board is private to the user.
 * Don't forget to do sanity check for the data.
 
#### Response

Upon success, something like the following will be returned.

```javascript
{
    "status": "success",
    "expiration": "2014-03-30T06:58:16+00:00",
    "data": {
        "message": "You have successfully created a board.",
        "board_data": {
            "id": "21",
            "user_id": "5",
            "group_id": null,
            "title": "test_createBoard",
            "description": "Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam non.",
            "created_at": "2014-03-30 06:58:16",
            "last_active_at": "2014-03-30 06:58:16",
            "discussion_list": [

            ]
        }
    }
}
```

* Since the board is newly created, `discussion_list` is definitely an empty array.

#### Associated Errors

* 1 - You should log in to perform the request (Must provide the authentication cookie `ugl_user`) 
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - Invalid group id
* 4 - Group not found
* 5 - You are not allowed to create boards for the group
* 6 - Board title is empty or contains invalid chars
* 7 - Board title has been used in the specified visibility scope (the group has a board with the same title, or the user has a private board with the same title)

### 4) Delete a Board

Delete an existing board.

#### Request

| Name   | Description                                      |
| ------ | ------------------------------------------------ |
| Method | POST                                             |
| URL    | `/api/board/delete`                              |
| DATA   | `board_id`=123 |


#### Response

If success, server will return a message saying the board has been successfully deleted.

#### Associated Errors

* 1 - You should log in to perform the request (Must provide the authentication cookie `ugl_user`) 
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - Board does not exist
* 4 - Group not found
* 5 - You are not allowed to delete boards for the group

### 5) Edit a Board

Edit the profile of a board. Note that the group where the board belongs cannot be changed.

#### Request

| Name   | Description                                      |
| ------ | ------------------------------------------------ |
| Method | POST                                             |
| URL    | `/api/board/edit`                              |
| DATA   | `board_id`=123&`title`=board_title&`description`=description |


#### Response

Upon success, server returns a message saying successfully updated board profile, along with 
the new board information.

TO-be-updated.

```javascript
{

}
```

#### Associated Errors

* 1 - You should log in to perform the request (Must provide the authentication cookie `ugl_user`) 
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - Board does not exist
* 4 - Group not found
* 5 - You are not allowed to edit the profile of the board
* 6 - Board title is empty or contains invalid chars
* 7 - Board title has been used

### 6) Add a Post



### 7) Edit a Post

### 8) Delete a Post

## 4. Wallet and Record API

The data structure of a wallet is defined as follows:
 * **name**
 * **description**
 * **balance**
 * **records**

### 1) Create a wallet

Create a new wallet.

#### Request

| Name   | Description                                      |
| ------ | ------------------------------------------------ |
| Method | POST                                             |
| URL    | `/api/wallet/create`                              |
| DATA   | `name`=wallet_name&`description`=description&`group_id`=123 |

Example POST source:
```
name=Secret+Wallet&description=There+is+no+secret+actually.&group_id=2
```

Authentication cookie `ugl_user` is required to perform the operation.

Notes:

 * The field `group_id` is needed only when the board is created for a group.
	 * When creating a board for a group, `create_wallet` permission must be `true` for the user's role in the group.
	 * Omitting `group_id` or setting `group_id` to `0` means the board is private to the user.
 * Don't forget to do sanity check for the data.

#### Response

Upon success, server returns JSON object whose format looks like the following:

```javascript
{
    "status": "success",
    "expiration": "2014-04-04T19:40:03+00:00",
    "data": {
        "message": "You have successfully created a wallet.",
        "wallet_data": {
            "id": "7",
            "user_id": "5",
            "group_id": "2",
            "name": "Secret Wallet Test",
            "description": "There is no secret actually.",
            "balance": null,
            "created_at": "2014-04-04 19:40:03",
            "last_update_at": null
        }
    }
}
```

#### Associated Errors

* 1 - You should log in to perform the request (Must provide the authentication cookie `ugl_user`) 
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - Invalid group id
* 4 - Group not found
* 5 - You are not allowed to create wallets for the group
* 6 - Wallet name is empty or contains invalid chars
* 7 - The wallet name has been used (the group or the user already has such a wallet)

### 2) Edit a wallet

#### Request

| Name   | Description                                      |
| ------ | ------------------------------------------------ |
| Method | POST                                             |
| URL    | `/api/wallet/edit`                              |
| DATA   | `name`=wallet_name&`description`=description |

Once a group wallet is created, one cannot change its group id.

#### Response

Refer to the wallet creation response.

#### Associated Errors

* 1 - You should log in to perform the request (Must provide the authentication cookie `ugl_user`) 
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - Invalid wallet id
* 4 - Wallet not found
* 5 - Group not found
* 6 - You cannot edit the group wallet
* 7 - You cannot edit the wallet information
* 8 - Wallet name is empty or contains invalid chars
* 9 - The wallet name has been used

### 3) Delete a wallet

### 4) List wallets

* List by user
* List by group
* List by certain criteria

### 6) Add records to a wallet

Add a record to a wallet.

#### Request

| Name   | Description                                      |
| ------ | ------------------------------------------------ |
| Method | POST                                             |
| URL    | `/api/wallet/create`                              |
| DATA   | `name`=wallet_name&`description`=description&`group_id`=123 |

Example POST source:
```
name=Secret+Wallet&description=There+is+no+secret+actually.&group_id=2
```

Authentication cookie `ugl_user` is required to perform the operation.

Notes:

 * The field `group_id` is needed only when the board is created for a group.
	 * When creating a board for a group, `create_wallet` permission must be `true` for the user's role in the group.
	 * Omitting `group_id` or setting `group_id` to `0` means the board is private to the user.
 * Don't forget to do sanity check for the data.

#### Response

Upon success, server returns JSON object whose format looks like the following:

```javascript

```

#### Associated Errors

* 1 - You should log in to perform the request (Must provide the authentication cookie `ugl_user`) 
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - Invalid group id
* 4 - Group not found
* 5 - You are not allowed to create wallets for the group
* 6 - Wallet name is empty or contains invalid chars
* 7 - The wallet name has been used (the group or the user already has such a wallet)


### 7) Edit a record

### 8) Delete a record

### 9) List records of a wallet

List a given number of records of a wallet, starting from ID `start_id`.

#### Request

| Name   | Description                                        |
| ------ | -------------------------------------------------- |
| Method | POST                                               |
| URL    | `/api/wallet/list_records`                         |
| DATA   | `wallet_id`=123&`page`=1&`limit`=100 |

Notes:
 * If the wallet belongs to a group, then the user must have `view_wallet` permission
in order to view the records.
 * `wallet_id` is the wallet to list records
 * `page` is the page number, starting from `1`
 * `limit` is the number of records to be fetched
	 * Say, `limit` is `20` and `page` is `1`, server will return the first 20 records of the wallet.
 * One can use the parameters to build pagination in the UI.

#### Response

`count` is the number of records in the array, and `records` is the array of the requested records.

```
{
    "status": "success",
    "expiration": "2014-04-15T03:46:47+00:00",
    "data": {
        "count": 2,
        "records": [
            {
                "id": "3",
                "wallet_id": "1",
                "user_id": "5",
                "category": "occasional",
                "sub_category": "transport",
                "description": "Trip to Mars",
                "amount": "-1234.56",
                "created_at": "2014-04-02 15:48:31",
                "notes": null
            },
            {
                "id": "1",
                "wallet_id": "1",
                "user_id": "5",
                "category": "regular",
                "sub_category": "food",
                "description": "Some panacea",
                "amount": "-10.00",
                "created_at": "2014-03-31 00:00:00",
                "notes": null
            }
        ]
    }
}
```

#### Associated Errors

* 1 - You should log in to perform the request (Must provide the authentication cookie `ugl_user`) 
* 2 - Unauthorized request (Authentication expired. Re-login.)
* 3 - Invalid wallet id
* 4 - Wallet not found
* 5 - Group not found
* 6 - You cannot view the group wallet
* 7 - You cannot view the wallet information

## 5. Shelf and Item API


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

