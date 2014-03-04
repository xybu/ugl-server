Ugl-server
==========
The server side of project "Ugl".

### Architecture
 * written in PHP and run on Nginx
 * follows MVC model with autoloader
 * generic caching mechanism
 * generic database class

### Structure
 * `app` stores the libraries, configuration files, controllers and models
 * `assets` has the public accessible files like images, css, and javascript
 * `data` stores logs and files uploaded by users
 * `tmp` if exists, will store temporary files like cache
 * `views` stores the view models
 * `vendor` if exists, stores reference libraries

### Planning

| Component     | Status        | Notes         |
| ------------- | ------------- | ------------- |
| Autoloader | Finished  | Part of basic libraries |
| Dispatcher | Finished  | Part of basic libraries |
| Generic Controller | Designing | |
| User controller | In dev | Working on auth controller part |
| Generic Model | Designing | |
| User model | In dev | Working on auth model |
| Auth model | In dev | Working on auth model |
| Group model | Not started | n/a |
